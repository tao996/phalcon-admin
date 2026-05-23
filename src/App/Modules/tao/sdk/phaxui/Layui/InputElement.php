<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

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