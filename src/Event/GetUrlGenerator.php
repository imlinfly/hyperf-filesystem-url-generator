<?php

/**
 * Created by PhpStorm.
 * User: LinFei
 * Created time 2023/12/12 12:42:44
 * E-mail: fly@eyabc.cn
 */
declare (strict_types=1);

namespace Lynnfly\HyperfFilesystemUrlGenerator\Event;

use Dotenv\Repository\Adapter\AdapterInterface;
use Hyperf\Event\Stoppable;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\EventDispatcher\StoppableEventInterface;

class GetUrlGenerator implements StoppableEventInterface
{
    use Stoppable;

    /**
     * @param AdapterInterface|FilesystemAdapter $adapter
     * @param array $config
     * @param PublicUrlGenerator|TemporaryUrlGenerator|null $urlGenerator
     */
    public function __construct(
        public AdapterInterface|FilesystemAdapter            $adapter,
        public array                                         $config,
        public PublicUrlGenerator|TemporaryUrlGenerator|null $urlGenerator = null
    )
    {
    }
}
