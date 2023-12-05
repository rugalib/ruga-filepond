<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);


namespace Ruga\Filepond\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;

class FileUpload
{
    private string $transferId;
    private string $name;
    private string $type;
    private int $size;
    private string $tmp_name;
    private int $error;
    private array $metadata;
    private string $basepath;
    
    
    
    public function __construct(array $fileGlobalVar, array $postGlobalVar, string $basepath)
    {
        $this->setBasepath($basepath);
        
        $this->name = strval($fileGlobalVar['name']);
        $this->type = strval($fileGlobalVar['type']);
        $this->size = intval($fileGlobalVar['size']);
        $this->tmp_name = strval($fileGlobalVar['tmp_name']);
        $this->error = intval($fileGlobalVar['error']);
        
        $this->metadata = $postGlobalVar;
    }
    
    
    
    /**
     * Create a FileUpload object from a serialized (previous) upload.
     *
     * @param string $transferId
     * @param string $basepath
     *
     * @return self
     */
    public static function createFromTransferId(string $transferId, string $basepath): self
    {
        $path = $basepath . DIRECTORY_SEPARATOR . $transferId . DIRECTORY_SEPARATOR . '.fileupload';
        if (!is_file($path)) {
            throw new \InvalidArgumentException("Transfer not found");
        }
        /** @var FileUpload $fileUpload */
        $fileUpload = unserialize(file_get_contents($path));
        $fileUpload->setBasepath($basepath);
        return $fileUpload;
    }
    
    
    
    protected function setBasepath(string $basepath)
    {
        $this->basepath = $basepath;
    }
    
    
    
    public function getTransferId(): string
    {
        if (empty($this->transferId)) {
            $this->transferId = md5(uniqid(dechex(intval(date('U'))), true));
        }
        return $this->transferId;
    }
    
    
    
    public function hasError(): bool
    {
        return ($this->error !== 0);
    }
    
    
    
    /**
     * Return the upload temporary directory for this transfer.
     *
     * @return string
     */
    public function getTransferDirectory(): string
    {
        $path = $this->basepath . DIRECTORY_SEPARATOR . $this->getTransferId();
        return $path;
    }
    
    
    
    /**
     * Prepare the upload temporary directory for this transfer.
     *
     * @return void
     */
    protected function prepareDirectory()
    {
        if (!is_dir($this->getTransferDirectory())) {
            mkdir($this->getTransferDirectory(), 0755, true);
        }
    }
    
    
    
    public function getTempDataFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . 'data.bin';
    }
    
    
    
    public function getTempMetaFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.metadata';
    }
    
    
    
    public function getTempFileUploadObjectFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.fileupload';
    }
    
    
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    
    
    public function storeUploadTempMetafile()
    {
        $a = [
            'transferId' => $this->getTransferId(),
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'meta' => $this->getMetadata(),
        ];
        $this->prepareDirectory();
        file_put_contents($this->getTempMetaFileName(), json_encode($a));
        file_put_contents($this->getTempFileUploadObjectFileName(), serialize($this));
    }
    
    
    
    /**
     * Store the upload temporary data file.
     *
     * @return void
     */
    public function storeUploadTempFile()
    {
        if (!is_uploaded_file($this->tmp_name)) {
            throw new \InvalidArgumentException("'{$this->tmp_name}' is not an uploaded file");
        }
        $this->prepareDirectory();
        if (!move_uploaded_file($this->tmp_name, $this->getTempDataFileName())) {
            throw new \RuntimeException("Error moving '{$this->tmp_name}'");
        }
    }
    
    
    
    /**
     * Remove the upload temporary directory.
     *
     * @return void
     */
    public function deleteUploadTempDir()
    {
        $files = glob($this->getTransferDirectory() . DIRECTORY_SEPARATOR . '{.,}*', GLOB_BRACE);
        @array_map('unlink', $files);
        @rmdir($this->getTransferDirectory());
    }
    
    
    
    /**
     * Return a response containing all headers and stream for file download.
     *
     * @return ResponseInterface
     */
    public function getFileResponse(): ResponseInterface
    {
        $fileStream = new Stream($this->getTempDataFileName(), 'r');
        $response = new Response($fileStream);
        $response = $response->withHeader(
            'Access-Control-Expose-Headers',
            'Content-Disposition, Content-Length, X-Content-Transfer-Id'
        );
        $response = $response->withHeader('X-Content-Transfer-Id', $this->getTransferId());
        $response = $response->withHeader('Content-Type', $this->type);
        $response = $response->withHeader('Content-Length', $this->size);
        $response = $response->withHeader('Content-Disposition', "inline; filename=\"{$this->name}\"");
        return $response;
    }
    
}