<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\FilesystemPlugin;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Ruga\Dms\Document\DocumentType;
use Ruga\Dms\Library\LibraryInterface;
use Ruga\Filepond\Middleware\FilepondRequest;
use Ruga\Filepond\Middleware\FileUpload;

class RugaDms extends AbstractFilesystemPlugin implements FilesystemPluginInterface
{
    private \Ruga\Dms\Library\LibraryManager $libraryManager;
    private LibraryInterface $library;
    
    
    
    public function __construct(\Ruga\Dms\Library\LibraryManager $libraryManager)
    {
        $this->libraryManager = $libraryManager;
    }
    
    
    
    /**
     * @inheritDoc
     */
    public function preProcess(FilepondRequest $request)
    {
        \Ruga\Log::functionHead();
        
        $a = $request->getRequestPathParts();
        $libraryName = $a[1];
        
        $this->library = $this->libraryManager->createLibraryFromName($libraryName);
    }
    
    
    
    public function isUploadAllowed(FileUpload $fileUpload, FilepondRequest $request): bool
    {
        $linkTo = $fileUpload->getMetadata()['linkTo'] ?? '';
        if (empty($linkTo)) {
            throw new \InvalidArgumentException("Parameter 'linkTo' is missing in metadata", 400);
        }
        return true;
    }
    
    
    
    public function uploadTempfileComplete(FileUpload $fileUpload, ResponseInterface $response): ResponseInterface
    {
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' upload completed to temporary folder '{$fileUpload->getTransferDirectory()}'",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        
        $documentType = new DocumentType($fileUpload->getMetadata()['documentType'] ?? DocumentType::GENERIC);
        $document = $this->library->createDocument($fileUpload->getName(), $documentType);
        $document->setFilename($fileUpload->getName());
        $document->setContentFromFile($fileUpload->getTempDataFileName());
        
        $linkTo = $fileUpload->getMetadata()['linkTo'] ?? '';
        if (empty($linkTo)) {
            throw new \InvalidArgumentException("Parameter 'linkTo' is missing in metadata", 400);
        }
        
        $document->linkTo($linkTo);
        $document->save();
        \Ruga\Log::addLog(
            "File '{$fileUpload->getName()}' stored to library in '{$document->getMetaStorageContainer()->getDataUniqueKey()}'",
            \Ruga\Log\Severity::INFORMATIONAL
        );
        
        return $response;
    }
    
    
}