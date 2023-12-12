<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/12/12 12:00:06
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace Lynnfly\HyperfFilesystemUrlGenerator;

use Dotenv\Repository\Adapter\AdapterInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Filesystem\Adapter\LocalAdapterFactory;
use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Lynnfly\HyperfFilesystemUrlGenerator\Event\GetUrlGenerator;

class RewriteFilesystemFactory extends FilesystemFactory
{
    protected array $adapters = [];

    public function __construct(
        protected ContainerInterface       $container,
        protected ConfigInterface          $config,
        protected EventDispatcherInterface $event,
    )
    {
        parent::__construct($container, $config);
    }

    public function get($adapterName): Filesystem
    {
        $options = $this->config->get('file', [
            'default' => 'local',
            'storage' => [
                'local' => [
                    'driver' => LocalAdapterFactory::class,
                    'root' => BASE_PATH . '/runtime',
                ],
            ],
        ]);

        $config = $options['storage'][$adapterName] ?? [];
        $cacheKey = md5(json_encode($config));

        $adapter = &$this->adapters[$adapterName];
        if (isset($adapter[$cacheKey])) {
            return $adapter[$cacheKey];
        }

        $instance = $this->getAdapter($options, $adapterName);

        $urlGenerator = $this->getUrlGenerator($instance, $config);

        $filesystem = new Filesystem(
            $instance, $config,
            publicUrlGenerator: $urlGenerator instanceof PublicUrlGenerator ? $urlGenerator : null,
            temporaryUrlGenerator: $urlGenerator instanceof TemporaryUrlGenerator ? $urlGenerator : null,
        );

        $adapter = [$cacheKey => $filesystem];

        return $filesystem;
    }

    /**
     * 获取 UrlGenerator
     * @param AdapterInterface|FilesystemAdapter $adapter
     * @param array $config
     * @return PublicUrlGenerator|TemporaryUrlGenerator|null
     */
    protected function getUrlGenerator(AdapterInterface|FilesystemAdapter $adapter, array $config): PublicUrlGenerator|TemporaryUrlGenerator|null
    {
        /** @var GetUrlGenerator $generate */
        $generate = $this->event->dispatch(new GetUrlGenerator($adapter, $config));
        return $generate->urlGenerator;
    }
}
