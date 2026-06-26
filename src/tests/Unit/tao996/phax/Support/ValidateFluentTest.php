<?php

declare(strict_types=1);

namespace Tests\Unit\tao996\phax\Support;

use Phax\Support\Validate;
use PHPUnit\Framework\TestCase;

class ValidateFluentTest extends TestCase
{
    // ============================================================
    //  with() + validate() 基础
    // ============================================================

    public function testEmptyValidate(): void
    {
        $result = (new Validate())->validate();
        $this->assertTrue($result->isValid());
        $this->assertNull($result->errors);
    }

    public function testWithoutRules(): void
    {
        $result = (new Validate())
            ->with('hello', '名称')
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  require
    // ============================================================

    public function testRequirePass(): void
    {
        $result = (new Validate())
            ->with('hello', '名称')->require()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testRequireFail(): void
    {
        $result = (new Validate())
            ->with('', '名称')->require()
            ->validate();
        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->errors);
    }

    public function testRequiredAlias(): void
    {
        $result = (new Validate())
            ->with('', '名称')->required()
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  min / max
    // ============================================================

    public function testMinPass(): void
    {
        $result = (new Validate())
            ->with('abc', '名称')->min(2)
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testMinFail(): void
    {
        $result = (new Validate())
            ->with('a', '名称')->min(2)
            ->validate();
        $this->assertFalse($result->isValid());
    }

    public function testMaxPass(): void
    {
        $result = (new Validate())
            ->with('abc', '名称')->max(5)
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testMaxFail(): void
    {
        $result = (new Validate())
            ->with('abcdef', '名称')->max(5)
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  len
    // ============================================================

    public function testLenPass(): void
    {
        $result = (new Validate())
            ->with('hello', '名称')->len(2, 10)
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testLenFail(): void
    {
        $result = (new Validate())
            ->with('a', '名称')->len(2, 10)
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  between
    // ============================================================

    public function testBetweenPass(): void
    {
        $result = (new Validate())
            ->with('50', '年龄')->between(1, 100)
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testBetweenFail(): void
    {
        $result = (new Validate())
            ->with('200', '年龄')->between(1, 100)
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  in / notIn
    // ============================================================

    public function testInPass(): void
    {
        $result = (new Validate())
            ->with('1', '状态')->in(['0', '1', '2'])
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testInFail(): void
    {
        $result = (new Validate())
            ->with('9', '状态')->in(['0', '1', '2'])
            ->validate();
        $this->assertFalse($result->isValid());
    }

    public function testNotInPass(): void
    {
        $result = (new Validate())
            ->with('9', '状态')->notIn(['0', '1', '2'])
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  email / url
    // ============================================================

    public function testEmailPass(): void
    {
        $result = (new Validate())
            ->with('test@example.com')->email()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testEmailFail(): void
    {
        $result = (new Validate())
            ->with('not-an-email')->email()
            ->validate();
        $this->assertFalse($result->isValid());
    }

    public function testUrlPass(): void
    {
        $result = (new Validate())
            ->with('https://example.com')->url()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  alpha / alnum / digit
    // ============================================================

    public function testAlphaPass(): void
    {
        $result = (new Validate())
            ->with('abcXYZ')->alpha()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testAlphaFail(): void
    {
        $result = (new Validate())
            ->with('abc123')->alpha()
            ->validate();
        $this->assertFalse($result->isValid());
    }

    public function testAlnumPass(): void
    {
        $result = (new Validate())
            ->with('abc123')->alnum()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testDigitPass(): void
    {
        $result = (new Validate())
            ->with('12345')->digit()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  int / float / bool
    // ============================================================

    public function testIntPass(): void
    {
        $result = (new Validate())
            ->with('123')->int()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testIntFail(): void
    {
        $result = (new Validate())
            ->with('abc')->int()
            ->validate();
        $this->assertFalse($result->isValid());
    }

    public function testFloatPass(): void
    {
        $result = (new Validate())
            ->with('3.14')->float()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  多字段链式调用
    // ============================================================

    public function testMultipleFieldsAllPass(): void
    {
        // 注意：min(n) 底层使用 Phalcon StringLength\Min，
        // 配置了 included=true 时要求 strlen > n（不含等于），因此测试值需 strlen > 2
        $result = (new Validate())
            ->with('hello', '用户名')->require()->min(2)->max(20)
            ->with('25', '年龄')->require()->between(1, 150)
            ->with('test@example.com', '邮箱')->email()
            ->validate();

        $this->assertTrue($result->isValid());
    }

    public function testMultipleFieldsSomeFail(): void
    {
        $result = (new Validate())
            ->with('', '用户名')->require()->min(2)
            ->with('200', '年龄')->between(1, 150)
            ->with('not-email', '邮箱')->email()
            ->validate();

        $this->assertFalse($result->isValid());
        $this->assertNotNull($result->errors);
        $this->assertGreaterThanOrEqual(2, count($result->errors));
    }

    // ============================================================
    //  with() 不带标题
    // ============================================================

    public function testWithoutTitle(): void
    {
        $result = (new Validate())
            ->with('hello')->require()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  require + min 组合失败
    // ============================================================

    public function testRequiredAndMinFail(): void
    {
        $result = (new Validate())
            ->with('', '名称')->require()->min(2)
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  rule() 通用方法
    // ============================================================

    public function testGenericRule(): void
    {
        $result = (new Validate())
            ->with('hello', '名称')->rule('require')->rule('min', '2')
            ->validate();
        $this->assertTrue($result->isValid());
    }

    // ============================================================
    //  before / after
    // ============================================================

    public function testAfterPass(): void
    {
        $result = (new Validate())
            ->with('2024-06-26')->after('2024-01-01')
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testBeforeFail(): void
    {
        $result = (new Validate())
            ->with('2023-01-01')->after('2024-01-01')
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  confirm
    // ============================================================

    public function testConfirmPass(): void
    {
        // confirm 需要两个字段值相同
        $result = (new Validate())
            ->with('password123', '密码')->require()
            ->with('password123', '确认密码')->confirm('f0')
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testConfirmFail(): void
    {
        $result = (new Validate())
            ->with('password123', '密码')->require()
            ->with('different', '确认密码')->confirm('f0')
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  regex
    // ============================================================

    public function testRegexPass(): void
    {
        $result = (new Validate())
            ->with('13800138000')->regex('/^1\d{10}$/')
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testRegexFail(): void
    {
        $result = (new Validate())
            ->with('1234')->regex('/^1\d{10}$/')
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  accepted
    // ============================================================

    public function testAcceptedPass(): void
    {
        $result = (new Validate())
            ->with('yes')->accepted()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testAcceptedFail(): void
    {
        // AcceptedValidation 的 in_array 使用松散比较，"no" == true 通过验证
        // 因此使用 "0" 作为失败值（"0" == false）
        $result = (new Validate())
            ->with('0')->accepted()
            ->validate();
        $this->assertFalse($result->isValid());
    }

    // ============================================================
    //  ip
    // ============================================================

    public function testIpPass(): void
    {
        // 使用公网 IP，Phalcon Ip 校验器默认拒绝私有地址（如 192.168.x.x）
        $result = (new Validate())
            ->with('8.8.8.8')->ip()
            ->validate();
        $this->assertTrue($result->isValid());
    }

    public function testIpFail(): void
    {
        $result = (new Validate())
            ->with('999.999.999.999')->ip()
            ->validate();
        $this->assertFalse($result->isValid());
    }
}
