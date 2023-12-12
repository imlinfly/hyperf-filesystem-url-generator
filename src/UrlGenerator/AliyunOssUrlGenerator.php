<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/12/21 21:03:05
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace Lynnfly\HyperfFilesystemUrlGenerator\UrlGenerator;

use DateTimeInterface;
use Hyperf\Contract\ConfigInterface;
use League\Flysystem\Config;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use OSS\Core\OssException;
use OSS\OssClient;

class AliyunOssUrlGenerator implements PublicUrlGenerator, TemporaryUrlGenerator
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
        $domain = $this->getUrl($config);

        return $domain . ltrim($path, '/');
    }

    /**
     * 获取本地私有文件的临时url
     * @param string $path
     * @param DateTimeInterface $expiresAt
     * @param Config $config
     * @return string
     * @throws OssException
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        $client = $this->getOssClient($config);

        // 存储桶
        $bucket = $config->get('bucket', '');

        // 有效时间
        $expireTime = $expiresAt->getTimestamp() - time();

        // 获取签名的URL
        $url = $client->signUrl($bucket, ltrim($path, '/'), $expireTime);
        // 删除域名中的协议 + 域名
        $url = preg_replace('/^(https|http)?:\/\/[^\/]+/', '', $url);

        return $this->getUrl($config) . ltrim($url, '/');
    }

    /**
     * 获取URL
     * @param Config $config
     * @return string
     */
    public function getUrl(Config $config): string
    {
        $url = $config->get('url', '');
        return (str_starts_with($url, 'http') ? '' : 'https://') . rtrim($url, '/') . '/';
    }

    /**
     * getOssClient
     * @param Config $config
     * @return OssClient
     */
    public function getOssClient(Config $config): OssClient
    {
        $accessId = $config->get('accessId');
        $accessSecret = $config->get('accessSecret');
        $endpoint = $config->get('endpoint', 'oss-cn-hangzhou.aliyuncs.com');
        $timeout = $config->get('timeout', 3600);
        $connectTimeout = $config->get('connectTimeout', 10);
        $isCName = $config->get('isCName', false);
        $token = $config->get('token');
        $proxy = $config->get('proxy');

        $client = new OssClient(
            $accessId,
            $accessSecret,
            $endpoint,
            $isCName,
            $token,
            $proxy,
        );

        $client->setTimeout($timeout);
        $client->setConnectTimeout($connectTimeout);

        return $client;
    }
}
