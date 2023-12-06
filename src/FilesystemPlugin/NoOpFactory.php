<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Psr\Container\ContainerInterface;

/**
 * @see     NoOp
 * @author  Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class NoOpFactory
{
    public function __invoke(ContainerInterface $container): FilesystemPluginInterface
    {
        return new NoOp();
    }
}
