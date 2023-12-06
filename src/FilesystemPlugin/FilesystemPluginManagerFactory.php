<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Psr\Container\ContainerInterface;
use Ruga\Filepond\Filepond;

/**
 * @see     FilesystemPluginManager
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class FilesystemPluginManagerFactory
{
    public function __invoke(ContainerInterface $container): FilesystemPluginManager
    {
        $config = ($container->get('config') ?? [])[Filepond::class][Filepond::CONF_FS_PLUGIN] ?? [];
        return new FilesystemPluginManager($container, $config);
    }
}
