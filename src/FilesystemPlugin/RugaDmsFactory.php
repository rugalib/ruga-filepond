<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Psr\Container\ContainerInterface;
use Ruga\Dms\Library\LibraryInterface;

/**
 * @see     RugaDms
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class RugaDmsFactory
{
    public function __invoke(ContainerInterface $container): FilesystemPluginInterface
    {
        $libraryManager = $container->get(\Ruga\Dms\Library\LibraryManager::class);
        return new RugaDms($libraryManager);
    }
}
