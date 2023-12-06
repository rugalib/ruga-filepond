<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * The FilesystemPluginManager loads plugin classes based on the first component of the serverSide uri.
 *
 * @author Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
class FilesystemPluginManager extends AbstractPluginManager
{
    /**
     * An object type that the created instance must be instanced of
     *
     * @var null|string
     */
    protected $instanceOf = FilesystemPluginInterface::class;
    
}