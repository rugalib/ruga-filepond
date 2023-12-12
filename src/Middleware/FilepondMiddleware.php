<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ruga\Filepond\Filepond;
use Ruga\Filepond\FilesystemPlugin\FilesystemPluginInterface;
use Ruga\Filepond\FilesystemPlugin\FilesystemPluginManager;


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
    private FilesystemPluginManager $filesystemPluginManager;
    private FilesystemPluginInterface $filesystemPlugin;
    
    
    
    public function __construct(FilesystemPluginManager $filesystemPluginManager, array $config)
    {
        $this->filesystemPluginManager = $filesystemPluginManager;
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
                \Ruga\Log::addLog(
                    "Processing {$filepondRequest->getRequestRoute()->getName()} request",
                    \Ruga\Log\Severity::INFORMATIONAL
                );
                
                $this->filesystemPlugin = $this->filesystemPluginManager->get($filepondRequest->getPluginAlias());
                $this->filesystemPlugin->preProcess($filepondRequest);
                
                if (($filepondRequest->getRequestRoute() != FilepondRequestRoute::UNKNOWN())
                    && (count($filepondRequest->getFileUploads()) == 0)) {
                    // No file uploads
                    \Ruga\Log::addLog("FileUpload not found", \Ruga\Log\Severity::ERROR);
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND); // Not Found
                }
                
                
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
                    
                    case FilepondRequestRoute::LOAD_LOCAL_FILE():
                        return $this->processLoadRequest($filepondRequest);
                }
            }
            
            throw new \Exception("Request not implemented", StatusCodeInterface::STATUS_NOT_IMPLEMENTED);
        } catch (\Throwable $e) {
            \Ruga\Log::addLog($e);
            $status = (($e->getCode() >= 400) && ($e->getCode() < 600)) ? $e->getCode() : StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
            return new TextResponse($e->getMessage(), $status);
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
        
        // test if server had trouble copying files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if ($fileUpload->hasError()) {
                throw new \RuntimeException("Error in upload processing");
            }
        }
        
        // test if upload size is allowed
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if (($uploadLength = $fileUpload->getUploadLength()) <= 0) {
                $uploadLength = intval($request->getRequest()->getHeaderLine('Upload-Length'));
            }
            $contentLength = intval($request->getRequest()->getHeaderLine('Content-Length'));
            if (!$this->filesystemPlugin->isUploadSizeAllowed($contentLength, $uploadLength)) {
                return new EmptyResponse(413); // Payload Too Large
            }
        }
        
        // test if files are of invalid format
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if (!$this->filesystemPlugin->isFileTypeAllowed($fileUpload, $request)) {
                return new EmptyResponse(415); // Unsupported Media Type
            }
        }
        
        // test if files are of invalid format
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if (!$this->filesystemPlugin->isUploadAllowed($fileUpload, $request)) {
                return new EmptyResponse(400); // Unsupported Media Type
            }
        }
        
        
        // Store files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if ($fileUpload->isUploadedFileComplete()) {
                $fileUpload->storeUploadDataFromUploadFile();
                $fileUpload->storeUploadTempMetafile();
                // File complete
                return $this->filesystemPlugin->uploadTempfileComplete($fileUpload, $fileUpload->getTextResponse());
            }
            
            $fileUpload->storeUploadTempMetafile();
            // transfer id created => client may send chunks with PATCH request
            return $this->filesystemPlugin->uploadTempfileStarted($fileUpload, $fileUpload->getTextResponse());
        }
        
        // There was no file upload. This should never happen.
        throw new \Exception("revert failed");
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
        
        /** @var FileUpload $fileUpload */
        foreach ($request->getFileUploads() as $fileUpload) {
            if ($this->filesystemPlugin->isRevertAllowed($fileUpload, $request)) {
                $fileUpload->deleteUploadTempDir();
                return $this->filesystemPlugin->revertComplete($fileUpload, new EmptyResponse(204));
            } else {
                return new EmptyResponse(403); // Forbidden
            }
        }
        
        // There was no file upload. This should never happen.
        throw new \Exception("revert failed");
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
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];
        
        
        // test if restore is allowed
        if (!$this->filesystemPlugin->isRestoreAllowed($fileUpload, $request)) {
            return new EmptyResponse(403); // Forbidden
        }
        
        
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
//            return $fileUpload->getHeadResponse(200);
            return $this->filesystemPlugin->restoreComplete($fileUpload, $fileUpload->getHeadResponse());
        }
        
        return $this->filesystemPlugin->restoreComplete($fileUpload, $fileUpload->getFileResponse());
    }
    
    
    
    /**
     * Process the load process.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#load
     *
     * @param FilepondRequest $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    private function processLoadRequest(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];
        
        
        $this->filesystemPlugin->loadFileInformation($fileUpload, $request);
        
        
        // test if LOAD is allowed
        if (!$this->filesystemPlugin->isLoadAllowed($fileUpload, $request)) {
            return new EmptyResponse(403); // Forbidden
        }
        
        $fileUpload->storeUploadTempMetafile();
        
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
            return $this->filesystemPlugin->loadComplete($fileUpload, $fileUpload->getHeadResponse());
        }
        
        $stream = $this->filesystemPlugin->getStreamFromForeignKey($fileUpload, $request);
        return $this->filesystemPlugin->loadComplete($fileUpload, $fileUpload->getStreamResponse($stream));
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
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];
        
        // test if server had trouble fetching file headers
        if ($fileUpload->hasError()) {
            return new EmptyResponse($fileUpload->getError());
        }
        
        // test if upload size is allowed
        $contentLength = $uploadLength = $fileUpload->getUploadLength();
        if (!$this->filesystemPlugin->isUploadSizeAllowed($contentLength, $uploadLength)) {
            return new EmptyResponse(413); // Payload Too Large
        }
        
        // test if fetch url is allowed
        if (!$this->filesystemPlugin->isFetchUrlAllowed($fileUpload->getFetchUrl())) {
            return new EmptyResponse(403); // Forbidden
        }
        
        
        // test if file type is allowed
        if (!$this->filesystemPlugin->isFileTypeAllowed($fileUpload, $request)) {
            return new EmptyResponse(415); // Unsupported Media Type
        }
        
        
        // Now, fetch the file
        $fileUpload->fetchFile();
        
        
        // test if server had trouble fetching file
        if ($fileUpload->hasError()) {
            return new EmptyResponse($fileUpload->getError());
        }
        
        
        if ($fileUpload->isDataFile()) {
            if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
                return $this->filesystemPlugin->fetchComplete($fileUpload, $fileUpload->getHeadResponse());
            }
            
            return $this->filesystemPlugin->fetchComplete($fileUpload, $fileUpload->getFileResponse());
        }
        throw new \Exception("fetch failed");
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
        
        /** @var FileUpload $fileUpload */
        $fileUpload = $request->getFileUploads()[0];

//        $contentlength = intval($request->getRequest()->getHeaderLine('Content-Length'));
//        $uploadlength = intval($request->getRequest()->getHeaderLine('Upload-Length'));
        $offset = intval($request->getRequest()->getHeaderLine('Upload-Offset'));
        $name = strval($request->getRequest()->getHeaderLine('Upload-Name'));
        
        // Save the client (real) file name
        $fileUpload->setName($name);
        
        
        // test if upload size is allowed
        if (($uploadLength = $fileUpload->getUploadLength()) <= 0) {
            $uploadLength = intval($request->getRequest()->getHeaderLine('Upload-Length'));
        }
        $contentLength = intval($request->getRequest()->getHeaderLine('Content-Length'));
        if (!$this->filesystemPlugin->isUploadSizeAllowed($contentLength, $uploadLength)) {
            return new EmptyResponse(413); // Payload Too Large
        }
        
        
        // HEAD request: return the offset
        if ($request->getRequest()->getMethod() == RequestMethodInterface::METHOD_HEAD) {
            $fileUpload->storeUploadTempMetafile();
            return $fileUpload->getHeadResponse();
        }
        
        
        // Save chunk to the temp temp file
        $fileUpload->storeUploadChunk(new Stream('php://input', 'r'), $offset);
        
        
        // test if file is of invalid format
        if (!$this->filesystemPlugin->isFileTypeAllowed($fileUpload, $request)) {
            return new EmptyResponse(415); // Unsupported Media Type
        }
        
        
        if ($fileUpload->isUploadedFileComplete()) {
            $fileUpload->storeUploadDataFromFile(true);
            $fileUpload->storeUploadTempMetafile();
            
            // File upload is complete
            return $this->filesystemPlugin->uploadTempfileComplete($fileUpload, $fileUpload->getHeadResponse());
        }
        
        // File upload is not yet complete
        $fileUpload->storeUploadTempMetafile();
        return $this->filesystemPlugin->uploadTempfileChunk($fileUpload, $fileUpload->getHeadResponse());
    }
    
    
}