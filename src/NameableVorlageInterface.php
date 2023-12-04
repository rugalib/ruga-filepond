<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Interface to a nameable template.
 */
interface NameableVorlageInterface
{
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function getName(): string;
    
    
    
    /**
     * Set the name of this template.
     *
     * @param string $name
     */
    public function setName(string $name);
}
