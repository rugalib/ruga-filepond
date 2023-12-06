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

use function FilePond\move_file;

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
    private string $fetchUrl;
    
    
    
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
     * This factory creates a FileUpload from meta data.
     *
     * @param array  $postGlobalVar
     * @param string $basepath
     *
     * @return FileUpload
     */
    public static function createFromMetadata(array $postGlobalVar, string $basepath)
    {
        $fileGlobalVar = [
            'name' => '',
            'type' => '',
            'size' => -1,
            'tmp_name' => '',
            'error' => 0,
        ];
        return new FileUpload($fileGlobalVar, $postGlobalVar, $basepath);
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
        if (empty($transferId)) {
            throw new \InvalidArgumentException("transferId is empty");
        }
        $path = $basepath . DIRECTORY_SEPARATOR . $transferId . DIRECTORY_SEPARATOR . '.fileupload';
        if (!is_file($path)) {
            throw new \InvalidArgumentException("Transfer not found");
        }
        /** @var FileUpload $fileUpload */
        $fileUpload = unserialize(file_get_contents($path));
        $fileUpload->setBasepath($basepath);
        return $fileUpload;
    }
    
    
    
    /**
     * This factory creates a FileUpload from a fetch url. The file is downloaded from an external source and stored
     * in a upload directory.
     *
     * @param string $fetchUrl
     * @param string $basepath
     *
     * @return self
     * @throws \Exception
     */
    public static function createFromFetchUrl(string $fetchUrl, string $basepath): self
    {
        $fileUpload = FileUpload::createFromMetadata([], $basepath);
        $fileUpload->setFetchUrl($fetchUrl);
        $fileUpload->fetchFile(true);
        return $fileUpload;
    }
    
    
    
    public function fetchFile(bool $headOnly = false)
    {
        // go!
        $ch = curl_init(str_replace(' ', '%20', $this->getFetchUrl()));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        
        if ($headOnly) {
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
            curl_setopt($ch, CURLOPT_NOBODY, true);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        } else {
            $tempfile = tmpfile();
            curl_setopt($ch, CURLOPT_FILE, $tempfile);
        }
        
        if (false === ($response=curl_exec($ch))) {
            throw new \Exception(curl_error($ch), curl_errno($ch));
        }
        
        $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        
        $this->name = pathinfo($this->getFetchUrl())['basename'];
        $this->type = strval($type);
        $this->size = intval($size);
        $this->error = intval(($code >= 200 && $code < 300) ? 0 : $code);
        
        if (!$headOnly) {
            $this->tmp_name = stream_get_meta_data($tempfile)['uri'];
            $this->storeUploadDataFromFile();
            $this->storeUploadTempMetafile();
        }
    }
    
    
    
    /**
     * Set the temporary directory base path.
     *
     * @param string $basepath
     *
     * @return void
     */
    protected function setBasepath(string $basepath)
    {
        $this->basepath = $basepath;
    }
    
    
    
    /**
     * Return the transfer id, or create a new one.
     *
     * @return string
     */
    public function getTransferId(): string
    {
        if (empty($this->transferId)) {
            $this->transferId = md5(uniqid(dechex(intval(date('U'))), true));
        }
        return $this->transferId;
    }
    
    
    
    /**
     * Return true, if upload has an error.
     *
     * @return bool
     */
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
    
    
    
    /**
     * Return the filename of the data file in the temporary upload folder.
     *
     * @return string
     */
    public function getTempDataFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . 'data.bin';
    }
    
    
    
    /**
     * Return the filename, where chunks are stored. AKA The temporary temp. file.
     *
     * @return string
     */
    public function getTempChunkFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . 'data.tmp';
    }
    
    
    
    /**
     * Return the filename, where metadata ist stored.
     *
     * @return string
     */
    public function getTempMetaFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.metadata';
    }
    
    
    
    /**
     * Return the filename, where serialized FileUpload is stored.
     *
     * @return string
     */
    public function getTempFileUploadObjectFileName(): string
    {
        return $this->getTransferDirectory() . DIRECTORY_SEPARATOR . '.fileupload';
    }
    
    
    
    /**
     * Return metadata.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    
    
    /**
     * Store the metadata to the temporary upload folder.
     *
     * @return void
     * @throws \Exception
     */
    public function storeUploadTempMetafile()
    {
        \Ruga\Log::functionHead();
        
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
     * Store the upload to the temporary data file.
     *
     * @return void
     * @throws \Exception
     */
    public function storeUploadDataFromUploadFile()
    {
        \Ruga\Log::functionHead();
        
        if (!is_uploaded_file($this->tmp_name)) {
            throw new \InvalidArgumentException("'{$this->tmp_name}' is not an uploaded file");
        }
        $this->prepareDirectory();
        touch($this->getTempDataFileName());
        if (!move_uploaded_file($this->tmp_name, $this->getTempDataFileName())) {
            throw new \RuntimeException("Error moving '{$this->tmp_name}'");
        }
        $this->updateContentType();
    }
    
    
    
    /**
     * Copy or move the file in tmp_name to the temporary upload folder.
     *
     * @param $moveNotCopy
     *
     * @return void
     * @throws \Exception
     */
    public function storeUploadDataFromFile($moveNotCopy = false)
    {
        \Ruga\Log::functionHead();
        
        if (!file_exists($this->tmp_name)) {
            throw new \InvalidArgumentException("'{$this->tmp_name}' not found");
        }
        $this->prepareDirectory();
//        touch($this->getTempDataFileName());
        if ($moveNotCopy) {
            if (!rename($this->tmp_name, $this->getTempDataFileName())) {
                throw new \RuntimeException("Error renaming '{$this->tmp_name}'");
            }
        } else {
            if (!copy($this->tmp_name, $this->getTempDataFileName())) {
                throw new \RuntimeException("Error copying '{$this->tmp_name}'");
            }
        }
        $this->updateContentType();
    }
    
    
    
    /**
     * Stores the given chunk in the temporary temp. file when processing chunked upload.
     *
     * @param Stream $chunk
     * @param int    $offset
     *
     * @return void
     */
    public function storeUploadChunk(Stream $chunk, int $offset)
    {
        $this->prepareDirectory();
        $this->tmp_name = $this->getTempChunkFileName();
        touch($this->tmp_name);
        
        if (filesize($this->tmp_name) != $offset) {
            throw new \OutOfRangeException("Chunk mismatch");
        }
        
        if (false === file_put_contents($this->tmp_name, $chunk, FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException("Error writing chunk to '{$this->tmp_name}'");
        }
    }
    
    
    
    /**
     * Update the type from data file, if it is empty.
     *
     * @return void
     */
    private function updateContentType()
    {
        if (empty($this->type) && $this->isDataFile()) {
            $this->type = mime_content_type($this->getTempDataFileName());
        }
    }
    
    
    
    /**
     * Checks, if upload (chunked) file is complete.
     *
     * @return bool
     * @throws \Exception
     */
    public function isUploadedFileComplete(): bool
    {
        clearstatcache();
        return is_file($this->tmp_name) && (filesize($this->tmp_name) == $this->size);
    }
    
    
    
    /**
     * Checks, if data file exists.
     *
     * @return bool
     */
    public function isDataFile(): bool
    {
        clearstatcache();
        return is_file($this->getTempDataFileName()) && (filesize($this->getTempDataFileName()) == $this->size);
    }
    
    
    
    /**
     * Checks, if chunk (temp temp) file exists.
     *
     * @return bool
     */
    public function isChunkFile(): bool
    {
        clearstatcache();
        return is_file($this->getTempChunkFileName()) && (filesize($this->getTempChunkFileName()) > 0);
    }
    
    
    
    /**
     * Remove the upload temporary directory.
     *
     * @return void
     * @throws \Exception
     */
    public function deleteUploadTempDir()
    {
        \Ruga\Log::functionHead();
        
        $files = glob($this->getTransferDirectory() . DIRECTORY_SEPARATOR . '{.,}*', GLOB_BRACE);
        @array_map('unlink', $files);
        @rmdir($this->getTransferDirectory());
    }
    
    
    
    /**
     * Return a response containing all headers and stream for file download.
     *
     * @return ResponseInterface
     */
    public function getFileResponse(int $status = 200): ResponseInterface
    {
        $this->updateContentType();
        $fileStream = new Stream($this->getTempDataFileName(), 'r');
        $response = new Response($fileStream, $status);
        $response = $response->withHeader(
            'Access-Control-Expose-Headers',
            'Content-Disposition, Content-Length, Content-Type, X-Content-Transfer-Id'
        );
        $response = $response->withHeader('Content-Type', $this->type);
        $response = $response->withHeader('Content-Length', $this->size);
        $response = $response->withHeader('Content-Disposition', "inline; filename=\"{$this->name}\"");
        return $response->withHeader('X-Content-Transfer-Id', $this->getTransferId());
    }
    
    
    
    /**
     * Return a response containing all headers for a HEAD request.
     *
     * @param int $status
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getHeadResponse(int $status = 204): ResponseInterface
    {
        $response = new Response\EmptyResponse($status);
        if (is_file($this->getTempChunkFileName()) && !$this->isUploadedFileComplete()) {
            $response = $response->withHeader('Upload-Offset', filesize($this->getTempChunkFileName()));
        }
        
        $response = $response->withHeader(
            'Access-Control-Expose-Headers',
            'Content-Disposition, Content-Length, Content-Type, X-Content-Transfer-Id'
        );
        
        $this->updateContentType();
        $response = $response->withHeader('Content-Type', $this->type);
        $response = $response->withHeader('Content-Length', $this->size);
//        $response = $response->withHeader('Accept-Ranges', 'bytes');
        $response = $response->withHeader('Content-Disposition', "attachment; filename=\"{$this->name}\"");
        return $response->withHeader('X-Content-Transfer-Id', $this->getTransferId());
    }
    
    
    
    /**
     * Return a response containing the transfer id as text.
     *
     * @param int $status
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function getTextResponse(int $status = 201): ResponseInterface
    {
        $response = new Response\TextResponse($this->getTransferId(), $status);
        return $response->withHeader('X-Content-Transfer-Id', $this->getTransferId());
    }
    
    
    
    /**
     * Set upload length. Used by chunked process.
     *
     * @param int $length
     *
     * @return void
     */
    public function setUploadLength(int $length)
    {
        if ($this->size < 0) {
            $this->size = $length;
        }
    }
    
    
    
    /**
     * Get upload length.
     *
     * @return int
     */
    public function getUploadLength(): int
    {
        return $this->size;
    }
    
    
    
    /**
     * Set the client (real) file name.
     *
     * @param string $name
     *
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
    
    
    
    /**
     * Get the client (real) file name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    
    
    /**
     * Return the type of the uploaded file.
     *
     * @return string
     */
    public function getType(): string
    {
        $this->updateContentType();
        if (empty($this->type) && $this->isChunkFile()) {
            $this->type = mime_content_type($this->getTempChunkFileName());
        }
        
        return $this->type;
    }
    
    
    
    public function getFetchUrl(): string
    {
        return $this->fetchUrl;
    }
    
    
    
    private function setFetchUrl(string $fetchUrl): void
    {
        $this->fetchUrl = $fetchUrl;
    }
    
    
    
    public function getError(): int
    {
        return $this->error;
    }
    
    
}