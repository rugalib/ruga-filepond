<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond;

use Ruga\Filepond\FilesystemPlugin\FilesystemPluginManager;
use Ruga\Filepond\FilesystemPlugin\FilesystemPluginManagerFactory;
use Ruga\Filepond\FilesystemPlugin\NoOp;
use Ruga\Filepond\FilesystemPlugin\NoOpFactory;
use Ruga\Filepond\FilesystemPlugin\RugaDms;
use Ruga\Filepond\FilesystemPlugin\RugaDmsFactory;
use Ruga\Filepond\Middleware\FilepondMiddleware;
use Ruga\Filepond\Middleware\FilepondMiddlewareFactory;

/**
 * ConfigProvider.
 *
 * @see    https://docs.mezzio.dev/mezzio/v3/features/container/config/
 */
class ConfigProvider
{
    public function __invoke()
    {
        return [
            Filepond::class => [
                Filepond::CONF_UPLOAD_TEMP_DIR => __DIR__ . '/../tmp',
                Filepond::CONF_FS_PLUGIN => [
                    'aliases' => [
                        'noop' => NoOp::class,
                        'ruga-dms' => RugaDms::class,
                    ],
                    'factories' => [
                        NoOp::class => NoOpFactory::class,
                        RugaDms::class => RugaDmsFactory::class,
                    ],
                ],
            ],
            'dependencies' => [
                'services' => [],
                'aliases' => [],
                'factories' => [
                    FilepondMiddleware::class => FilepondMiddlewareFactory::class,
                    FilesystemPluginManager::class => FilesystemPluginManagerFactory::class,
                ],
                'invokables' => [],
                'delegators' => [],
            ],
        ];
    }
}
