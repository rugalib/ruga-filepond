<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * This factory creates a FilepondMiddleware. FilepondMiddleware is responsible for handling all the requests for
 * Filepond file processing.
 *
 * @see     FilepondMiddleware
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class FilepondMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        return new FilepondMiddleware(/* $container->get(DatasourcePluginManager::class) */);
    }
}
