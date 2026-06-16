<?php

declare(strict_types=1);

namespace Tests\Unit\tao996\phax\Utils;

use Phax\Utils\MyDatetime;

class MyDatetimeTest extends \PHPUnit\Framework\TestCase
{
    // ============================================================
    //  parseDate — 万能日期解析
    // ============================================================

    public function testParseDateEmpty(): void
    {
        $this->assertEquals('', MyDatetime::parseDate(''));
        $this->assertEquals('', MyDatetime::parseDate(null));
        $this->assertEquals('', MyDatetime::parseDate(0));
    }

    public function testParseDateChineseFull(): void
    {
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026年5月22日'));
        $this->assertEquals('2026-12-01', MyDatetime::parseDate('2026年12月1日'));
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026年05月22日'));
    }

    public function testParseDateChineseShort(): void
    {
        $year = date('Y');
        $this->assertEquals($year . '-05-22', MyDatetime::parseDate('5月22日'));
        $this->assertEquals($year . '-12-01', MyDatetime::parseDate('12月1日'));
    }

    public function testParseDateSlash(): void
    {
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026/05/22'));
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026/5/22'));
    }

    public function testParseDateDot(): void
    {
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026.05.22'));
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026.5.22'));

        // 注意：5.10 会被 strtr('.')->'-' 变成 5-10，再匹配 \d{1,2}-\d{1,2} 补年份
        $year = date('Y');
        $this->assertEquals($year . '-05-10', MyDatetime::parseDate('5.10'));
        $this->assertEquals($year . '-05-22', MyDatetime::parseDate('5.22'));
    }

    public function testParseDateShortDash(): void
    {
        $year = date('Y');
        $this->assertEquals($year . '-05-22', MyDatetime::parseDate('5-22'));
        $this->assertEquals($year . '-12-01', MyDatetime::parseDate('12-1'));
    }

    public function testParseDateFullDash(): void
    {
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026-5-22'));
        $this->assertEquals('2026-12-01', MyDatetime::parseDate('2026-12-1'));
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('2026-05-22'));
    }

    public function testParseDateExcelSerial(): void
    {
        // Excel 日期序列号：46164 = 2026-05-22
        $this->assertEquals('2026-05-22', MyDatetime::parseDate(46164));
        // 46165 = 2026-05-23
        $this->assertEquals('2026-05-23', MyDatetime::parseDate(46165));
        // 字符串形式的数字
        $this->assertEquals('2026-05-22', MyDatetime::parseDate('46164'));
    }

    public function testParseDateWithDefaultYear(): void
    {
        $this->assertEquals('2025-05-22', MyDatetime::parseDate('5月22日', '2025'));
        $this->assertEquals('2024-12-01', MyDatetime::parseDate('12月1日', '2024'));
        $this->assertEquals('2023-05-22', MyDatetime::parseDate('5-22', '2023'));
        $this->assertEquals('2022-05-22', MyDatetime::parseDate('5.22', '2022'));
    }

    public function testParseDateExcelPrecisionLoss(): void
    {
        // Excel 中 "6.10" 存储为 float 6.1，修复后应正确解析为 06-10
        $year = date('Y');
        $this->assertEquals($year . '-06-10', MyDatetime::parseDate(6.10));
        // "5.10" → 5.10 (float) → "5.10" → 05-10
        $this->assertEquals($year . '-05-10', MyDatetime::parseDate(5.10));
        // float 5.22 → 5.22 (保持精度)
        $this->assertEquals($year . '-05-22', MyDatetime::parseDate(5.22));
    }

    public function testParseDateInvalid(): void
    {
        $this->assertEquals('', MyDatetime::parseDate('abc'));
        $this->assertEquals('', MyDatetime::parseDate('not-a-date'));
        $this->assertEquals('', MyDatetime::parseDate('13月32日')); // 无效日期
    }

    public function testParseDateBoundary(): void
    {
        // 边界：1月1日、12月31日
        $year = date('Y');
        $this->assertEquals($year . '-01-01', MyDatetime::parseDate('1月1日'));
        $this->assertEquals($year . '-12-31', MyDatetime::parseDate('12月31日'));

        // 数字小于等于 20000 不会触发 Excel 分支
        // 注意：1900-01-01 的 Excel 序列号是 1，但此处不处理
        $this->assertEquals(date('Y') . '-01-01', MyDatetime::parseDate('1.1'));
    }

    // ============================================================
    //  isDate / mustDate
    // ============================================================

    public function testIsDate(): void
    {
        $this->assertTrue(MyDatetime::isDate('2026-05-22'));
        $this->assertTrue(MyDatetime::isDate('2026-1-1'));
        $this->assertFalse(MyDatetime::isDate('2026/05/22'));
        $this->assertFalse(MyDatetime::isDate('2026年5月22日'));
        $this->assertFalse(MyDatetime::isDate(''));
        $this->assertFalse(MyDatetime::isDate('abc'));
    }

    public function testMustDatePass(): void
    {
        MyDatetime::mustDate('2026-05-22');
        $this->assertTrue(true);
    }

    public function testMustDateThrows(): void
    {
        $this->expectException(\Exception::class);
        MyDatetime::mustDate('2026/05/22');
    }

    // ============================================================
    //  isMonth / mustMonth
    // ============================================================

    public function testIsMonth(): void
    {
        $this->assertTrue(MyDatetime::isMonth('2026-05'));
        $this->assertTrue(MyDatetime::isMonth('2026-5'));
        $this->assertTrue(MyDatetime::isMonth('2026-05-22')); // 子串匹配 2026-05
        $this->assertFalse(MyDatetime::isMonth(''));
    }

    // ============================================================
    //  isYear / mustYear
    // ============================================================

    public function testIsYear(): void
    {
        $this->assertTrue(MyDatetime::isYear('2026'));
        $this->assertTrue(MyDatetime::isYear('2026-05')); // 子串匹配 2026
        $this->assertFalse(MyDatetime::isYear(''));
    }

    // ============================================================
    //  monthDateRange
    // ============================================================

    public function testMonthDateRange(): void
    {
        $range = MyDatetime::monthDateRange('2026-05');
        $this->assertEquals(['2026-05-01', '2026-05-31'], $range);

        $rangeWithTime = MyDatetime::monthDateRange('2026-05', true);
        $this->assertEquals(['2026-05-01 00:00:00', '2026-05-31 23:59:59'], $rangeWithTime);

        // 2月（平年）
        $range = MyDatetime::monthDateRange('2025-02');
        $this->assertEquals(['2025-02-01', '2025-02-28'], $range);
    }

    // ============================================================
    //  dateGte
    // ============================================================

    public function testDateGte(): void
    {
        $this->assertTrue(MyDatetime::dateGte('2026-05-22', '2026-05-21'));
        $this->assertTrue(MyDatetime::dateGte('2026-05-22', '2026-05-22'));
        $this->assertFalse(MyDatetime::dateGte('2026-05-22', '2026-05-23'));
    }
}
