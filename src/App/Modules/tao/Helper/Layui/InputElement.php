<?php

namespace App\Modules\tao\Helper\Layui;

class InputElement
{
    public function __construct(
        public string $title,
        public string $name,
        public mixed  $value = '',
        public bool   $required = false,
        public string $type = 'text',
        public string $aux = ''
    )
    {
    }
}