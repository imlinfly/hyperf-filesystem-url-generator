<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/12/11 11:53:29
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace Lynnfly\HyperfFilesystemUrlGenerator\Listener;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Flysystem\OSS\Adapter as AliyunOssAdapterFactory;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Lynnfly\HyperfFilesystemUrlGenerator\Event\GetUrlGenerator;
use Lynnfly\HyperfFilesystemUrlGenerator\UrlGenerator\AliyunOssUrlGenerator;
use Lynnfly\HyperfFilesystemUrlGenerator\UrlGenerator\LocalFilesystemUrlGenerator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class GetUrlGeneratorListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container)
    {

    }

    public function listen(): array
    {
        return [
            GetUrlGenerator::class,
        ];
    }

    /**
     * @param object $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(object $event): void
    {
        /** @var GetUrlGenerator $event */

        $adapters = [
            LocalFilesystemAdapter::class => LocalFilesystemUrlGenerator::class,
            AliyunOssAdapterFactory::class => AliyunOssUrlGenerator::class,
        ];

        $adapter = $event->adapter::class;

        if (isset($adapters[$adapter])) {
            $event->urlGenerator = $this->container->get($adapters[$adapter]);
            $event->setPropagation(false);
        }
    }
}
