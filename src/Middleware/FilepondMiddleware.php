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


/**
 * RugaformMiddleware creates a RugaformRequest from a form request and tries to find the desired plugin.
 * If found, the process method is executed and returns a RugaformResponse, which is returned to the client form.
 *
 * @see     RugaformMiddlewareFactory
 */
class FilepondMiddleware implements MiddlewareInterface
{
    
    
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
                $filepondRequest = new FilepondRequest($request->withAttribute('fieldname', $fieldname));
                
                if ($filepondRequest->isFileTransfer()) {
                    return $this->processFileTransfer($filepondRequest);
                }
                
                if ($filepondRequest->isRevertFileTransfer()) {
                    return $this->processRevertFileTransfer($filepondRequest);
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
        
        if (count($request->getFiles()) == 0) {
            return new EmptyResponse(400);
        }
        
        // test if server had trouble copying files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFiles() as $fileUpload) {
            if ($fileUpload->hasError()) {
                return new EmptyResponse(500);
            }
        }
        
        // test if files are of invalid format
        /** @var FileUpload $fileUpload */
        foreach ($request->getFiles() as $fileUpload) {
            if (false) {
                return new EmptyResponse(415);
            }
        }
        
        // Store files
        /** @var FileUpload $fileUpload */
        foreach ($request->getFiles() as $fileUpload) {
            $fileUpload->prepareDirectory();
            $fileUpload->storeMetadata();
            $fileUpload->storeFile();
            
            return new TextResponse($fileUpload->getTransferId(), 201);
        }
        
        return new EmptyResponse(400);
    }
    
    
    
    private function processRevertFileTransfer(FilepondRequest $request): ResponseInterface
    {
        \Ruga\Log::functionHead();
        
        
        if (count($request->getFiles()) == 0) {
            return new EmptyResponse(400);
        }
        
        /** @var FileUpload $fileUpload */
        foreach ($request->getFiles() as $fileUpload) {
            $fileUpload->deleteDirectory();
        }
        return new EmptyResponse(204);
    }
    
    
}