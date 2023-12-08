<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Ruga\Filepond\Middleware\FilepondRequest;
use Ruga\Filepond\Middleware\FileUpload;

interface FilesystemPluginInterface
{
    /**
     * Called before the actual processing begins.
     *
     * @param FilepondRequest $request
     *
     * @return mixed
     */
    public function preProcess(FilepondRequest $request);
    
    
    
    /**
     * Check, if content size and upload size are allowed.
     *
     * @param int $contentLength length of this request
     * @param int $uploadLength  total file size
     *
     * @return bool
     */
    public function isUploadSizeAllowed(int $contentLength, int $uploadLength): bool;
    
    
    
    /**
     * Checks if the file type is allowed for upload.
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isFileTypeAllowed(FileUpload $fileUpload, FilepondRequest $request): bool;
    
    
    
    /**
     * Checks if uploads are allowed
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isUploadAllowed(FileUpload $fileUpload, FilepondRequest $request): bool;
    
    
    
    /**
     * Checks if the REVERT request is allowed
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isRevertAllowed(FileUpload $fileUpload, FilepondRequest $request): bool;
    
    
    
    /**
     * Checks if the RESTORE request is allowed
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isRestoreAllowed(FileUpload $fileUpload, FilepondRequest $request): bool;
    
    
    
    /**
     * Checks if the LOAD request is allowed
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isLoadAllowed(FileUpload $fileUpload, FilepondRequest $request): bool;
    
    
    
    /**
     * Checks if the fetch url is allowed.
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return bool
     */
    public function isFetchUrlAllowed(string $fetchUrl): bool;
    
    
    
    /**
     * Called, when file is completely uploaded.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    public function uploadTempfileComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called, when transfer id is created for chunked upload.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function uploadTempfileStarted(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called every time a chunk of a file has been received and stored to the temp temp file.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function uploadTempfileChunk(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called every time an upload has been reverted.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function revertComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called every time a FETCH request is complete.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function fetchComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called every time a RESTORE request is complete.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function restoreComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Called every time a LOAD request is complete.
     *
     * @param FileUpload        $fileUpload
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function loadComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface;
    
    
    
    /**
     * Populate FileUpload with information about the external file.
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     */
    public function loadFileInformation(FileUpload $fileUpload, FilepondRequest $request): void;
    
    
    
    /**
     * Return a stream containing the data from an external source.
     *
     * @param FileUpload      $fileUpload
     * @param FilepondRequest $request
     *
     * @return mixed
     */
    public function getStreamFromForeignKey(FileUpload $fileUpload, FilepondRequest $request): StreamInterface;
    
}