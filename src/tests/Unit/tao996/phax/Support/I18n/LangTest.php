<?php

namespace Tests\Unit\tao996\phax\Support\I18n;

use Phax\Support\I18n\Lang;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    public function testInterpolate()
    {
        $rst = Lang::interpolate(':date (YYYY-MM-DD)', ['date' => '2020-09-09']);
        $this->assertEquals('2020-09-09 (YYYY-MM-DD)',$rst);
    }
}