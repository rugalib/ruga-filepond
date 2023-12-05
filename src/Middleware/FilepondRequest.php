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
                throw new \BadFunctionCallException("NOT IMPLEMENTED");
                break;
            
            
            case FilepondRequestRoute::PATCH_FILE_TRANSFER():
                throw new \BadFunctionCallException("NOT IMPLEMENTED");
                break;
            
        }
    }
    
    
    
    public function getRequestRoute(): FilepondRequestRoute
    {
        switch ($this->getRequest()->getMethod()) {
            case RequestMethodInterface::METHOD_POST:
                if (isset($_FILES[$this->fieldname]) || isset($_POST[$this->fieldname])) {
                    return FilepondRequestRoute::FILE_TRANSFER();
                }
                break;
            
            case RequestMethodInterface::METHOD_DELETE:
                return FilepondRequestRoute::REVERT_FILE_TRANSFER();
            
            case RequestMethodInterface::METHOD_GET:
            case RequestMethodInterface::METHOD_HEAD:
            case RequestMethodInterface::METHOD_PATCH:
                if ($this->getRequest()->getQueryParams()['fetch'] ?? false) {
                    return FilepondRequestRoute::FETCH_REMOTE_FILE();
                }
                if ($this->getRequest()->getQueryParams()['restore'] ?? false) {
                    return FilepondRequestRoute::RESTORE_FILE_TRANSFER();
                }
                if ($this->getRequest()->getQueryParams()['load'] ?? false) {
                    return FilepondRequestRoute::LOAD_LOCAL_FILE();
                }
                if ($this->getRequest()->getQueryParams()['patch'] ?? false) {
                    return FilepondRequestRoute::PATCH_FILE_TRANSFER();
                }
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
    
    
    
    public function getFileUploads(): array
    {
        return $this->fileUploads;
    }
    
    
    
    public function getMetadata(): array
    {
        if (!isset($_POST[$this->fieldname])) {
            return [];
        }
        
        $metadatas = [$_POST[$this->fieldname]];
        return json_decode($metadatas[0], true);
    }
    
    
    
    public function isFileTransfer(): bool
    {
        return ($this->getRequestRoute() == FilepondRequestRoute::FILE_TRANSFER());
    }
    
    
    
    public function isRevertFileTransfer(): bool
    {
        return ($this->getRequestRoute() == FilepondRequestRoute::REVERT_FILE_TRANSFER());
    }
    
    
    
    public function isRestoreRequest(): bool
    {
        return ($this->getRequestRoute() == FilepondRequestRoute::RESTORE_FILE_TRANSFER());
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
    
    
    
    /**
     * Return true, if current data for the form is requested.
     *
     * @return bool
     */
    public function isFormGetRequest(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_GET);
    }
    
    
    
    /**
     * Return true, if the request asks to delete the row.
     *
     * @return bool
     */
    public function isFormDeleteRequest(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_DELETE);
    }
    
    
    
    /**
     * Return true, if the request asks to set the favourite value.
     *
     * @return bool
     */
    public function isFormSetFavourite(): bool
    {
        return (($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_POST)
                || ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_PUT))
            && array_key_exists(Rugaform::FAVOURITE, $this->data);
    }
    
    
    
    /**
     * Return true, if the request asks to create a row.
     *
     * @return bool
     */
    public function isFormCreateRow(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_POST) && !$this->isFormSetFavourite(
            );
    }
    
    
    
    /**
     * Return true, if the request asks to update a row.
     *
     * @return bool
     */
    public function isFormUpdateRow(): bool
    {
        return ($this->getRequest()->getMethod() == RequestMethodInterface::METHOD_PUT) && !$this->isFormSetFavourite();
    }
    
    
}