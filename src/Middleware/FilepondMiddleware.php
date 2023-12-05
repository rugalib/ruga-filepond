<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ruga\Filepond\Filepond;


/**
 * FilepondMiddleware creates a FilepondRequest from a request and tries to find the desired plugin.
 * If found, the process method is executed and returns a FilepondResponse, which is returned to the client.
 *
 * @see     FilepondMiddlewareFactory
 */
class FilepondMiddleware implements MiddlewareInterface
{
    private array $config;
    private string $uploadTempDir;
    
    
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $uploadTempDir = $this->config[Filepond::CONF_UPLOAD_TEMP_DIR] ?? '';
        $this->setUploadTempDir($uploadTempDir);
    }
    
    
    
    /**
     * Check and store the temporary upload directory.
     *
     * @param string $uploadTempDir
     *
     * @return void
     */
    private function setUploadTempDir(string $uploadTempDir)
    {
        if (empty($uploadTempDir)) {
            throw new \InvalidArgumentException("Config option '" . Filepond::CONF_UPLOAD_TEMP_DIR . "' is empty");
        }
        
        if (!($realbasepath = realpath($uploadTempDir)) || !is_dir($realbasepath)) {
            throw new \InvalidArgumentException("'{$uploadTempDir}' is not found or is not a directory");
        }
        $this->uploadTempDir = $realbasepath;
    }
    
    
    
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        \Ruga\Log::functionHead($this);
        $entries = ['filepond'];
        
        try {
            foreach ($entries as $fieldname) {
                $filepondRequest = new FilepondRequest($request, $fieldname, $this->uploadTempDir);
                
                if ($filepondRequest->isFileTransfer()) {
                    return $this->processFileTransfer($filepondRequest);
                }
                
                if ($filepondRequest->isRevertFileTransfer()) {
                    return $this->processRevertFileTransfer($filepondRequest);
                }
                
                if ($filepondRequest->isRestoreRequest()) {
                    return $this->processRestoreRequest($filepondRequest);
                }
            }
            
            
            $filepondResponse = new FilepondResponse();
            return new TextResponse("NO RESPONSE", 500);
        } catch (\Exception $e) {
            \Ruga\Log::addLog($e);
            return new TextResponse($e->getMessage(), 500);
        }
    }
    
    
    
    private function processFileTransfer(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(400);
        }
        
        // test if server had trouble copying files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if ($fileUpload->hasError()) {
                return new EmptyResponse(500);
            }
        }
        
        // test if files are of invalid format
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if (false) {
                return new EmptyResponse(415);
            }
        }
        
        // Store files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            $fileUpload->storeUploadTempMetafile();
            $fileUpload->storeUploadTempFile();
            
            return new TextResponse($fileUpload->getTransferId(), 201);
        }
        
        return new EmptyResponse(400);
    }
    
    
    
    private function processRevertFileTransfer(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(404);
        }
        
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            $fileUpload->deleteUploadTempDir();
        }
        return new EmptyResponse(204);
    }
    
    
    
    private function processRestoreRequest(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(404);
        }
        
        /** @var FileUpload $file */
        $file=$request->getFileUploads()[0];
        
        return $file->getFileResponse();
        
//        return new EmptyResponse(500);
    }
    
    
}