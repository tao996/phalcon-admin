<?php

namespace App\Modules\tao\A0\cms\Utils;

class ShareUrl
{
    /**
     * 获取抖音地址信息
     * @param string $url
     * @return void
     */
    public static function getTiktokInfo(string $url)
    {

    }

    public static function getYoutubeInfo(string $url)
    {

    }

    /**
     * 是否是一个 youtube 地址
     * @param string $url
     * @return bool
     */
    public static function isYoutubeURL(string $url):bool
    {
        return self::matchYouTubeLink($url) !== '';
    }

    /**
     * 获取 youtube 地址中的视频 ID
     * @param string $link
     * @return string
     */
    public static function matchYouTubeLink(string $link): string
    {
        preg_match('|https?://(?:www\.)?youtube\.com/watch\?v=(.+)|', $link, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }

        preg_match('|https?://youtu\.be/(.+)\?|', $link, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        return '';
    }
}