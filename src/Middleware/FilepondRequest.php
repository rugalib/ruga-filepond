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
        
        
        switch ($this->getRequest()->getMethod()) {
            case RequestMethodInterface::METHOD_POST:
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
            
            case RequestMethodInterface::METHOD_DELETE:
                $transferId = file_get_contents('php://input');
                $this->fileUploads[] = FileUpload::createFromTransferId($transferId, $uploadTempDir);
                break;
                
            case RequestMethodInterface::METHOD_GET:
            case RequestMethodInterface::METHOD_HEAD:
            case RequestMethodInterface::METHOD_PATCH:
                $transferId = $this->getRequest()->getQueryParams()['fetch']
                    ?? $this->getRequest()->getQueryParams()['restore']
                    ?? $this->getRequest()->getQueryParams()['load']
                    ?? $this->getRequest()->getQueryParams()['patch']
                    ?? '';
                $this->fileUploads[] = FileUpload::createFromTransferId($transferId, $uploadTempDir);
                break;
        }
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
        if ($this->getRequest()->getMethod() === RequestMethodInterface::METHOD_POST) {
            return isset($_FILES[$this->fieldname]) || isset($_POST[$this->fieldname]);
        }
        return false;
    }
    
    
    
    public function isRevertFileTransfer(): bool
    {
        if ($this->getRequest()->getMethod() === RequestMethodInterface::METHOD_DELETE) {
            return true;
        }
        return false;
    }
    
    
    
    public function isRestoreRequest(): bool
    {
        if (($this->getRequest()->getMethod() === RequestMethodInterface::METHOD_GET)
            && ($this->getRequest()->getQueryParams()['restore'] ?? false)) {
            return true;
        }
        return false;
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