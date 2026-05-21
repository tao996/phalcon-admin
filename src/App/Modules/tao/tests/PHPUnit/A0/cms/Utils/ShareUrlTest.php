<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Utils;

use App\Modules\tao\A0\cms\Utils\ShareUrl;
use PHPUnit\Framework\TestCase;

class ShareUrlTest extends TestCase
{
    public function testMatchYouTubeLink()
    {
        $link = 'https://youtu.be/JTxsNm9IdYU?si=cNkeJX-rOkRJMQqr';
        $id = ShareUrl::matchYouTubeLink($link);
        $this->assertEquals('JTxsNm9IdYU',$id);

        $link = 'https://www.youtube.com/watch?v=JTxsNm9IdYU';
        $id = ShareUrl::matchYouTubeLink($link);
        $this->assertEquals('JTxsNm9IdYU',$id);
    }
}