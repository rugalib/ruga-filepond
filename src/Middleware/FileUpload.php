<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);


namespace Ruga\Filepond\Middleware;

class FileUpload
{
    private string $transferId;
    private string $name;
    private string $type;
    private int $size;
    private string $tmp_name;
    private int $error;
    private array $metadata;
    
    
    
    public function __construct(array $fileGlobalVar, array $postGlobalVar = [])
    {
        $this->name = strval($fileGlobalVar['name']);
        $this->type = strval($fileGlobalVar['type']);
        $this->size = intval($fileGlobalVar['size']);
        $this->tmp_name = strval($fileGlobalVar['tmp_name']);
        $this->error = intval($fileGlobalVar['error']);
        
        $this->metadata = $postGlobalVar;
    }
    
    
    
    public static function createFromTransferId($transferId, $basepath): self
    {
        $path = $basepath . DIRECTORY_SEPARATOR . $transferId . DIRECTORY_SEPARATOR . '.serialize';
        return unserialize(file_get_contents($path));
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
    
    
    
    public function getTransferDirectory(): string
    {
        $path = __DIR__;
        $path = $path . DIRECTORY_SEPARATOR . $this->getTransferId();
        return $path;
    }
    
    
    
    public function prepareDirectory()
    {
        if (!is_dir($this->getTransferDirectory())) {
            mkdir($this->getTransferDirectory(), 0755, true);
        }
    }
    
    
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    
    
    public function storeMetadata()
    {
        $a = [
            'transferId' => $this->getTransferId(),
            'name' => $this->name,
            'type' => $this->type,
            'size' => $this->size,
            'meta' => $this->getMetadata(),
        ];
        file_put_contents($this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.metadata', json_encode($a));
        file_put_contents($this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.serialize', serialize($this));
    }
    
    
    
    public function storeFile()
    {
        if (!is_uploaded_file($this->tmp_name)) {
            throw new \InvalidArgumentException("'{$this->tmp_name}' is not an uploaded file");
        }
        if (!move_uploaded_file($this->tmp_name, $this->getTransferDirectory() . DIRECTORY_SEPARATOR . "file")) {
            throw new \RuntimeException("Error moving '{$this->tmp_name}'");
        }
    }
    
    
    
    public function deleteDirectory()
    {
        $files = glob($this->getTransferDirectory() . DIRECTORY_SEPARATOR . '{.,}*', GLOB_BRACE);
        @array_map('unlink', $files);
        @rmdir($this->getTransferDirectory());
    }
    
}