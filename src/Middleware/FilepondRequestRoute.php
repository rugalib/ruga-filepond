<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Ruga\Std\Enum\AbstractEnum;
use Ruga\Std\Enum\EnumInterface;

/**
 * @method static self FILE_TRANSFER()
 * @method static self PATCH_FILE_TRANSFER()
 * @method static self REVERT_FILE_TRANSFER()
 * @method static self RESTORE_FILE_TRANSFER()
 * @method static self LOAD_LOCAL_FILE()
 * @method static self REMOVE_LOCAL_FILE()
 * @method static self FETCH_REMOTE_FILE()
 * @method static self UNKNOWN()
 */
class FilepondRequestRoute extends AbstractEnum implements EnumInterface
{
    const FILE_TRANSFER = 'FILE_TRANSFER';
    const PATCH_FILE_TRANSFER = 'PATCH_FILE_TRANSFER';
    const REVERT_FILE_TRANSFER = 'REVERT_FILE_TRANSFER';
    const RESTORE_FILE_TRANSFER = 'RESTORE_FILE_TRANSFER';
    const LOAD_LOCAL_FILE = 'LOAD_LOCAL_FILE';
    const REMOVE_LOCAL_FILE = 'REMOVE_LOCAL_FILE';
    const FETCH_REMOTE_FILE = 'FETCH_REMOTE_FILE';
    const UNKNOWN = 'UNKNOWN';
}