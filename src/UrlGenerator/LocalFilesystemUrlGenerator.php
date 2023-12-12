<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/12/13 13:49:05
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace Lynnfly\HyperfFilesystemUrlGenerator\UrlGenerator;

use DateTimeInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use League\Flysystem\Config;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use function Hyperf\Support\env;

class LocalFilesystemUrlGenerator implements PublicUrlGenerator, TemporaryUrlGenerator
{
    public function __construct(protected ConfigInterface $config)
    {
    }

    /**
     * 获取本地公开文件的url
     * @throws UnableToGeneratePublicUrl
     */
    public function publicUrl(string $path, Config $config): string
    {
        return $this->getUrl($config->toArray(), $path);
    }

    /**
     * 获取本地私有文件的临时url
     * @throws UnableToGenerateTemporaryUrl
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        $config = $config->toArray();

        $url = $this->getUrl($config, $path);
        $expires = $expiresAt->getTimestamp();

        $params = [
            'expires' => $expires,
        ];
        $secret = $this->getAccessSecret($config);
        $params['signature'] = $this->getSign($path, $params, $expires, $secret);

        return $url . '?' . http_build_query($params);
    }

    /**
     * 本地私有文件调度
     * @param string $storage 本地存储配置名称
     * @param string $path 文件路径
     * @param RequestInterface $request 请求对象
     * @param ResponseInterface $response 响应对象
     * @return PsrResponseInterface
     */
    public function localPrivateFilesystemDispatch(
        string            $storage,
        string            $path,
        RequestInterface  $request,
        ResponseInterface $response,
    ): PsrResponseInterface
    {
        $config = $this->config->get('file.storage.' . $storage, []);
        $secret = $this->getAccessSecret($config);

        if (empty($secret)) {
            return env('APP_ENV') === 'dev' ? $this->result('Access secret is empty', 403, $response) : $response->withStatus(403);
        }

        $params = $request->getQueryParams();
        $expires = intval($params['expires'] ?? 0);
        $signature = $params['signature'] ?? '';

        if ($signature !== $this->getSign($path, $params, $expires, $secret)) {
            return $this->result('Signature is invalid', 403, $response);
        }

        if ($expires < time()) {
            return $this->result('Signature has expired', 403, $response);
        }

        $filepath = $this->getStorageDir($config, $path);

        if (!file_exists($filepath)) {
            return $this->result('404 Not Found', 404, $response);
        }

        return $response->download($filepath);
    }

    /**
     * 本地公共文件调度
     * @param string $storage 本地存储配置名称
     * @param string $path 文件路径
     * @param ResponseInterface $response 响应对象
     * @return PsrResponseInterface
     */
    public function localPublicFilesystemDispatch(
        string            $storage,
        string            $path,
        ResponseInterface $response,
    ): PsrResponseInterface
    {
        $config = $this->config->get('file.storage.' . $storage, []);

        $filepath = $this->getStorageDir($config, $path);

        if (!file_exists($filepath)) {
            return $this->result('404 Not Found', 404, $response);
        }

        return $response->download($filepath);
    }

    protected function getSign(string $path, array $params, int $expires, string $secret): string
    {
        $str = ltrim($path, '/') . $expires;

        foreach ($params as $key => $value) {
            if ($key === 'signature' || $key === '' || $key === null) {
                continue;
            }
            $str .= $key . '=' . urldecode((string)$value);
        }

        return sha1($str . $secret);
    }

    protected function getAccessSecret(array $config): string
    {
        return $config['accessSecret'] ?? '';
    }

    protected function getStorageDir(array $config, string $path = ''): string
    {
        $root = $config['root'] ?? '';
        return rtrim($root, '/') . ($path === '' ? '' : '/') . ltrim($path, '/');
    }

    protected function getUrl(array $config, string $path): string
    {
        $url = $config['url'] ?? '';
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    protected function result(string $message, int $code, ResponseInterface $response): PsrResponseInterface
    {
        return $response->html('<center><h1>' . $message . '</h1></center><hr><center>private-storage-service</center>')->withStatus($code);
    }
}
