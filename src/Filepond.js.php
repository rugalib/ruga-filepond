<?php
/**
 * @var \Ruga\Filepond\Filepond $this
 */
?>
(function ($, window, document) {
    $(function () {
        var pond_<?=$this->getId()?> = $('#<?=$this->getId()?>').filepond({
            credits: false,
            allowMultiple: true,
            files: <?=json_encode($this->getFilesProperty())?>,
            chunkUploads: true,
            chunkSize: 1024 * 1024 * 5,
            allowRemove: true,
            allowRevert: true,
            server: {
                url: '<?=$this->url?>',
                process: {
                    headers: (file, metadata) => {
                        return {
                            'Upload-Length': file.size,
                            'Upload-Type': file.type,
                            'Upload-Name': file.name,
                            'X-Ruga-Component': 'ruga-filepond'
                        };
                    }
                },
                revert: {
                    headers: {
                        'X-Ruga-Component': 'ruga-filepond'
                    }
                },
                restore: {
                    headers: {
                        'X-Ruga-Component': 'ruga-filepond'
                    }
                },
                load: {
                    headers: {
                        'X-Ruga-Component': 'ruga-filepond'
                    }
                },
                fetch: {
                    headers: {
                        'X-Ruga-Component': 'ruga-filepond'
                    }
                }
            }
        });
    });
}(window.jQuery, window, document));
