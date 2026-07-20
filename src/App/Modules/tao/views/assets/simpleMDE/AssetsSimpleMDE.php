<?php

namespace App\Modules\tao\views\assets\simpleMDE;

use App\Modules\tao\views\HtmlAssets;
use Phax\Foundation\AppService;

class AssetsSimpleMDE
{

    public static function render(array $config = []): void
    {
        if (HtmlAssets::isLocal()) {
            AppService::html()
                ->addHeaderFile(__DIR__ . DIRECTORY_SEPARATOR . 'simplemde.min.css', local: true)
                ->addFooterFile(__DIR__ . DIRECTORY_SEPARATOR . 'simplemde.min.js', local: true);
        } else {
            AppService::html()
                ->addHeaderFile('https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css')
                ->addFooterFile('https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js');
        }
        AppService::html()->addFooterFile(__DIR__ . DIRECTORY_SEPARATOR . 'content.js', local: true);
    }
}