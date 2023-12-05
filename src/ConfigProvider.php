<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond;


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
            ],
            'dependencies' => [
                'services' => [],
                'aliases' => [],
                'factories' => [
                    FilepondMiddleware::class => FilepondMiddlewareFactory::class,
                ],
                'invokables' => [],
                'delegators' => [],
            ],
        ];
    }
}
