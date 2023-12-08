<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Filepond;

/**
 * Filepond main class.
 */
class Filepond
{
    const CONF_UPLOAD_TEMP_DIR = 'upload-temp-dir';
    const CONF_FS_PLUGIN = 'filesystem-plugin';
    
    
    private string $id;
    private string $url;
    private array $filesProperty = [];
    private object $defaultMetadata;
    
    
    
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->defaultMetadata = new \stdClass();
    }
    
    
    
    /**
     * Returns an id for the html element.
     * If no id is set, a random id is created.
     *
     * @param string $suffix
     *
     * @return string
     */
    public function getId($suffix = ''): string
    {
        if (!isset($this->id)) {
            $this->id = 'rugalib_filepond_' . preg_replace(
                    '#[^A-Za-z0-9\-_]+#',
                    '',
                    md5('rugalib_filepond_' . uniqid('', true) . date('U'))
                );
        }
        return $this->id . ($suffix ? '-' . $suffix : '');
    }
    
    
    
    public function getFilesProperty(): array
    {
        return $this->filesProperty;
    }
    
    
    public function getDefaultMetadata(): object
    {
        return $this->defaultMetadata;
    }
    
    
    public function setFilesProperty(array $filesProperty): void
    {
        $this->filesProperty = $filesProperty;
    }
    
    
    
    public function renderHtml(): string
    {
        $str = '<input type="file" id="' . $this->getId() . '" name="filepond" />';
        return $str;
    }
    
    
    
    public function renderJavascript(): string
    {
        ob_start();
        require(__DIR__ . '/Filepond.js.php');
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }
    
    
}
