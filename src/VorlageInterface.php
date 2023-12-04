<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Skeleton;


/**
 * Interface to a template.
 */
interface VorlageInterface
{
    /**
     * Return the name of this template.
     *
     * @return string
     */
    public function myName(): string;
}
