<?php

namespace App\Modules\tao\sdk;

interface OssDriverInterface
{
    public function uploadFile(string $objectName, string $filePath);
}