<?php

namespace Tests\Unit\tao996\phax\Support\I18n;


use Phax\Support\I18n\Translate;
use PHPUnit\Framework\TestCase;

class TranslateTest extends TestCase
{
    public function testI18n()
    {
        $t = new Translate();
        $t->load();

        $message = $t::get('cn', 'accepted', ['field' => '协议']);

        $this->assertEquals('协议 的值必须为 yes,on 或者 1', $message);

        $message = $t::get('en', 'accepted', ['field' => 'agreement']);

        $this->assertEquals('agreement must be yes,on or 1', $message);

        try {
            $message = $t::get('hk', 'accepted', ['field' => 'agreement']);
            $this->assertEquals('xxx', $message);
        } catch (\Exception $e) {
            $this->assertStringContainsString('could not find', $e->getMessage());
        }
    }
}