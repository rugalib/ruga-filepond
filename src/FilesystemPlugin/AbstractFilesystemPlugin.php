<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Ruga\Filepond\Middleware\FilepondRequest;
use Ruga\Filepond\Middleware\FileUpload;

abstract class AbstractFilesystemPlugin implements FilesystemPluginInterface
{
    
    /**
     * @inheritDoc
     */
    public function preProcess(FilepondRequest $request)
    {
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isUploadSizeAllowed(int $contentLength, int $uploadLength): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isFileTypeAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isRevertAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isRestoreAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isLoadAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function isFetchUrlAllowed(string $fetchUrl): bool
    {
        return true;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function uploadTempfileComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function uploadTempfileStarted(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function uploadTempfileChunk(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function revertComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function fetchComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function restoreComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function loadComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function loadFileInformation(FileUpload $fileUpload, FilepondRequest $request): void
    {
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function getStreamFromForeignKey(FileUpload $fileUpload, FilepondRequest $request): StreamInterface
    {
        return new Stream('php://temp', 'wb+');
    }
}