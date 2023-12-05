<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Stream;
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
                
                switch ($filepondRequest->getRequestRoute()) {
                    case FilepondRequestRoute::FILE_TRANSFER():
                        return $this->processFileTransfer($filepondRequest);
                    
                    case FilepondRequestRoute::REVERT_FILE_TRANSFER():
                        return $this->processRevertFileTransfer($filepondRequest);
                    
                    case FilepondRequestRoute::RESTORE_FILE_TRANSFER():
                        return $this->processRestoreRequest($filepondRequest);
                    
                    case FilepondRequestRoute::FETCH_REMOTE_FILE():
                        return $this->processFetchRequest($filepondRequest);
                    
                    case FilepondRequestRoute::PATCH_FILE_TRANSFER():
                        return $this->processPatchRequest($filepondRequest);
                }
            }
            
            return new TextResponse("NO RESPONSE", 500);
        } catch (\Exception $e) {
            \Ruga\Log::addLog($e);
            return new TextResponse($e->getMessage() . PHP_EOL . $e->getTraceAsString(), 500);
        }
    }
    
    
    
    /**
     * Process the file transfer request.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#process
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
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
            if ($fileUpload->isUploadedFileComplete()) {
                $fileUpload->storeUploadDataFromUploadFile();
            }
            
            $fileUpload->storeUploadTempMetafile();
            return new TextResponse($fileUpload->getTransferId(), 201);
        }
        
        return new EmptyResponse(400);
    }
    
    
    
    /**
     * Process the revert request.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#revert
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
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
    
    
    
    /**
     * Process the restore process.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#restore
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    private function processRestoreRequest(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(404);
        }
        
        /** @var FileUpload $file */
        $file = $request->getFileUploads()[0];
        
        
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
            return $file->getHeadResponse(200);
        }
        
        return $file->getFileResponse();
    }
    
    
    
    /**
     * Process the fetch request.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#fetch
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    private function processFetchRequest(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(404);
        }
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];
        
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
            return $fileUpload->getHeadResponse();
        }
        
        return $fileUpload->getFileResponse();
    }
    
    
    
    /**
     * Process the patch request.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#process-chunks
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    private function processPatchRequest(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        if (count($request->getFileUploads()) == 0) {
            return new EmptyResponse(404);
        }
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];

//        $contentlength = intval($request->getRequest()->getHeaderLine('Content-Length'));
//        $uploadlength = intval($request->getRequest()->getHeaderLine('Upload-Length'));
        $offset = intval($request->getRequest()->getHeaderLine('Upload-Offset'));
        $name = strval($request->getRequest()->getHeaderLine('Upload-Name'));
        
        $fileUpload->setName($name);
        
        
        // HEAD request: return the offset
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
            $fileUpload->storeUploadTempMetafile();
            return $fileUpload->getHeadResponse();
        }
        
        $fileUpload->storeUploadChunk(new Stream('php://input', 'r'), $offset);
        
        if ($fileUpload->isUploadedFileComplete()) {
            $fileUpload->storeUploadDataFromFile(true);
        }
        
        $fileUpload->storeUploadTempMetafile();
        return $fileUpload->getHeadResponse();
    }
    
    
}