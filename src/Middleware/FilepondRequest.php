<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Class RugaformRequest
 */
class FilepondRequest
{
    /** @var ServerRequestInterface */
    private ServerRequestInterface $request;
    
    private string $fieldname;
    
    private array $fileUploads = [];
    
    private ?string $transferId = null;
    
    
    
    public function __construct(ServerRequestInterface $request, string $fieldname, string $uploadTempDir)
    {
        $this->fieldname = $fieldname;
        $this->request = $request;
        \Ruga\Log::addLog('_POST=' . print_r($_POST[$fieldname] ?? null, true));
        \Ruga\Log::addLog('_FILES=' . print_r($_FILES[$fieldname] ?? null, true));
        
        
        switch ($this->getRequestRoute()) {
            case FilepondRequestRoute::FILE_TRANSFER():
                // Create a FileUpload object for each uploaded file
                if (isset($_FILES[$fieldname])) {
                    // Real upload (without chunks)
                    if (is_array($_FILES[$fieldname]['tmp_name'])) {
                        // input type=file is an array input
                        foreach ($_FILES[$fieldname]['tmp_name'] as $key => $value) {
                            $file = [
                                'tmp_name' => $_FILES[$fieldname]['tmp_name'][$key],
                                'name' => $_FILES[$fieldname]['name'][$key],
                                'size' => $_FILES[$fieldname]['size'][$key],
                                'error' => $_FILES[$fieldname]['error'][$key],
                                'type' => $_FILES[$fieldname]['type'][$key],
                            ];
                            $this->fileUploads[] = new FileUpload(
                                $file,
                                @json_decode($_POST[$fieldname][$key], true) ?? [],
                                $uploadTempDir
                            );
                        }
                    } else {
                        $this->fileUploads[] = new FileUpload(
                            $_FILES[$fieldname],
                            @json_decode($_POST[$fieldname], true) ?? [],
                            $uploadTempDir
                        );
                    }
                } elseif (isset($_POST[$fieldname])) {
                    // Chunked upload
                    if (is_array($_POST[$fieldname])) {
                        foreach ($_POST[$fieldname] as $key => $metajson) {
                            $this->fileUploads[] = FileUpload::createFromMetadata(
                                @json_decode($_POST[$fieldname][$key], true) ?? [],
                                $uploadTempDir
                            );
                        }
                    } else {
                        $this->fileUploads[] = FileUpload::createFromMetadata(
                            @json_decode($_POST[$fieldname], true) ?? [],
                            $uploadTempDir
                        );
                        if ($this->request->hasHeader('Upload-Length') && (($length = intval(
                                    $this->request->getHeaderLine('Upload-Length')
                                )) > 0)) {
                            $this->fileUploads[0]->setUploadLength($length);
                        }
                    }
                }
                break;
            
            
            case FilepondRequestRoute::REVERT_FILE_TRANSFER():
                $transferId = file_get_contents('php://input');
                $this->fileUploads[] = FileUpload::createFromTransferId($transferId, $uploadTempDir);
                break;
            
            
            case FilepondRequestRoute::FETCH_REMOTE_FILE():
                $fetchUrl = $this->getRequest()->getQueryParams()['fetch'] ?? '';
                $this->fileUploads[] = FileUpload::createFromFetchUrl($fetchUrl, $uploadTempDir);
                break;
            
            
            case FilepondRequestRoute::RESTORE_FILE_TRANSFER():
                $transferId = $this->getRequest()->getQueryParams()['restore'] ?? '';
                $this->fileUploads[] = FileUpload::createFromTransferId($transferId, $uploadTempDir);
                break;
            
            
            case FilepondRequestRoute::LOAD_LOCAL_FILE():
                $foreignKey = $this->getRequest()->getQueryParams()['load'] ?? '';
                $this->fileUploads[] = FileUpload::createFromForeignKey($foreignKey, $uploadTempDir);
                break;
            
            
            case FilepondRequestRoute::REMOVE_LOCAL_FILE():
                $transferId = $this->getRequest()->getQueryParams()['remove'] ?? '';
                throw new \BadFunctionCallException("NOT IMPLEMENTED");
                break;
            
            
            case FilepondRequestRoute::PATCH_FILE_TRANSFER():
                $transferId = $this->getRequest()->getQueryParams()['patch'] ?? '';
                $this->fileUploads[] = FileUpload::createFromTransferId($transferId, $uploadTempDir);
                break;
        }
    }
    
    
    
    /**
     * Return the route by analyzing the request.
     *
     * @return FilepondRequestRoute
     */
    public function getRequestRoute(): FilepondRequestRoute
    {
        if ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_POST
            && (isset($_FILES[$this->fieldname]) || isset($_POST[$this->fieldname]))) {
            return FilepondRequestRoute::FILE_TRANSFER();
        }
        
        if ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_DELETE) {
            return FilepondRequestRoute::REVERT_FILE_TRANSFER();
        }
        
        if (in_array(
                $this->getRequest()->getMethod(),
                [RequestMethodInterface::METHOD_GET, RequestMethodInterface::METHOD_HEAD]
            )
            && ($this->getRequest()->getQueryParams()['fetch'] ?? false)) {
            return FilepondRequestRoute::FETCH_REMOTE_FILE();
        }
        
        if (in_array($this->getRequest()->getMethod(), [RequestMethodInterface::METHOD_GET])
            && ($this->getRequest()->getQueryParams()['restore'] ?? false)) {
            return FilepondRequestRoute::RESTORE_FILE_TRANSFER();
        }
        
        if (in_array($this->getRequest()->getMethod(), [RequestMethodInterface::METHOD_GET])
            && ($this->getRequest()->getQueryParams()['load'] ?? false)) {
            return FilepondRequestRoute::LOAD_LOCAL_FILE();
        }
        
        if (in_array(
                $this->getRequest()->getMethod(),
                [RequestMethodInterface::METHOD_PATCH, RequestMethodInterface::METHOD_HEAD]
            )
            && ($this->getRequest()->getQueryParams()['patch'] ?? false)) {
            return FilepondRequestRoute::PATCH_FILE_TRANSFER();
        }
        
        return FilepondRequestRoute::UNKNOWN();
    }
    
    
    
    /**
     * Returns the original request.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
    
    
    
    /**
     * Return the FileUpload objects.
     *
     * @return array
     */
    public function getFileUploads(): array
    {
        return $this->fileUploads;
    }
    
    
    
    /**
     * Return an array containing all the path components.
     *
     * @return array
     */
    public function getRequestPathParts(): array
    {
        $uriPath = trim($this->request->getUri()->getPath(), " /\\");
        return explode('/', $uriPath);
    }
    
    
    
    /**
     * Returns the alias name of the desired datasource plugin.
     *
     * @return string
     */
    public function getPluginAlias(): string
    {
        return $this->getRequestPathParts()[0] ?? '';
    }
    
    
}