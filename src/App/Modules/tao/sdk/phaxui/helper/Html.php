<?php

namespace App\Modules\tao\sdk\phaxui\helper;

class Html
{
    public static function placeholderMerge(array $data, $separator = '/'): string
    {
        $text = [];
        foreach ($data as $t => $v) {
            if (!empty($v)) {
                $text[] = $t;
            }
        }
        return join($separator, $text);
    }
}