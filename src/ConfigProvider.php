<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Lynnfly\HyperfFilesystemUrlGenerator;

use Hyperf\Filesystem\FilesystemFactory;
use Lynnfly\HyperfFilesystemUrlGenerator\Listener\GetUrlGeneratorListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                FilesystemFactory::class => RewriteFilesystemFactory::class
            ],
            'listeners' => [
                GetUrlGeneratorListener::class,
            ],
        ];
    }
}
