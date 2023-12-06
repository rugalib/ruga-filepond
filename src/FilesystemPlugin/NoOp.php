<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Psr\Http\Message\ResponseInterface;
use Ruga\Filepond\Middleware\FilepondRequest;
use Ruga\Filepond\Middleware\FilepondRequestRoute;
use Ruga\Filepond\Middleware\FileUpload;

class NoOp implements FilesystemPluginInterface
{
    /**
     * @inheritdoc
     */
    public function preProcess(FilepondRequest $request)
    {
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function isUploadSizeAllowed(int $contentLength, int $uploadLength): bool
    {
        \Ruga\Log::addLog(
            "contentLength={$contentLength} | uploadLength={$uploadLength} allowed?",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return true;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function isFileTypeAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        \Ruga\Log::addLog(
            "file type='{$fileUpload->getType()}' allowed?",
            \Ruga\Log\Severity::INFORMATIONAL
        );

//        if ($request->getRequestRoute() == FilepondRequestRoute::FETCH_REMOTE_FILE()) {
//            if ($fileUpload->getType() == 'image/jpeg') {
//                return false;
//            }
//        }
        
        return true;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function isRevertAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        \Ruga\Log::addLog(
            "REVERT request for file '{$fileUpload->getName()}' allowed?",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return true;
    }
    
    
    /**
     * @inheritdoc
     */
    public function isRestoreAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        \Ruga\Log::addLog(
            "RESTORE request for file '{$fileUpload->getName()}' allowed?",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        
//        if($fileUpload->getName() == 'rufus-3.20.exe') {
//            return false;
//        }
        
        return true;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function isFetchUrlAllowed(string $fetchUrl): bool
    {
        \Ruga\Log::addLog(
            "FETCH from URL '{$fetchUrl}' allowed?",
            \Ruga\Log\Severity::INFORMATIONAL
        );

//        if(strpos($fetchUrl, 'easy') !== false) {
//            return false;
//        }
        
        return true;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function uploadTempfileComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' upload completed to temporary folder '{$fileUpload->getTransferDirectory()}'",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function uploadTempfileStarted(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' client may upload chunks with PATCH method to temporary folder '{$fileUpload->getTransferDirectory()}'",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function uploadTempfileChunk(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' chunk received to temporary folder '{$fileUpload->getTransferDirectory()}'",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function revertComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' has been reverted and temporary folder '{$fileUpload->getTransferDirectory()}' is deleted",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function fetchComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' has been fetched from '{$fileUpload->getFetchUrl()}' and stored in temporary folder '{$fileUpload->getTransferDirectory()}' is deleted",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
    
    /**
     * @inheritdoc
     */
    public function restoreComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' has been restored from temporary folder '{$fileUpload->getTransferDirectory()}' is deleted",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        return $response;
    }
    
    
}