<?php

declare(strict_types=1);

namespace Tests\Unit\tao996\phax\Support;

use Phax\Support\Validate;
use Phax\Utils\MyAssert;

class ValidateTest extends \PHPUnit\Framework\TestCase
{
    private static ?Validate $v = null;

    public static function setUpBeforeClass(): void
    {
        self::$v = new Validate();
    }

    // ============================================================
    //  rules() — 规则解析
    // ============================================================

    public function testRulesParsing(): void
    {
        $rules = ['name|用户名' => 'require|min:2|max:10'];
        $parsed = self::$v->rules($rules);

        $this->assertCount(1, $parsed);
        $this->assertEquals('name', $parsed[0]['name']);
        $this->assertEquals('用户名', $parsed[0]['title']);
        $this->assertCount(3, $parsed[0]['rules']);

        // require
        $this->assertEquals('require', $parsed[0]['rules'][0][0]);
        $this->assertEquals([], $parsed[0]['rules'][0][1]);

        // min:2
        $this->assertEquals('min', $parsed[0]['rules'][1][0]);
        $this->assertEquals(['2'], $parsed[0]['rules'][1][1]);

        // max:10
        $this->assertEquals('max', $parsed[0]['rules'][2][0]);
        $this->assertEquals(['10'], $parsed[0]['rules'][2][1]);
    }

    public function testRulesWithMultiParams(): void
    {
        $rules = ['age|年龄' => 'between:1,100'];
        $parsed = self::$v->rules($rules);

        $this->assertCount(1, $parsed);
        $this->assertEquals('age', $parsed[0]['name']);
        // between → params = ['1', '100']
        $this->assertEquals('between', $parsed[0]['rules'][0][0]);
        $this->assertEquals(['1', '100'], $parsed[0]['rules'][0][1]);
    }

    public function testRulesWithoutTitle(): void
    {
        $rules = ['email' => 'email'];
        $parsed = self::$v->rules($rules);

        $this->assertCount(1, $parsed);
        $this->assertEquals('email', $parsed[0]['name']);
        $this->assertEquals('', $parsed[0]['title']);
    }

    public function testRulesEmptyThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('validate rules must not empty');
        self::$v->rules([]);
    }

    // ============================================================
    //  getCallerValidation() — 规则映射
    // ============================================================

    public function testCallerValidationStandardRules(): void
    {
        // [rule => [expectedClassNamePart, expectedKey]]
        $cases = [
            'require' => ['PresenceOf', 'require'],
            'required' => ['PresenceOf', 'require'],
            'email' => ['Email', 'email'],
            'int' => ['IntValidation', 'int'],
            'integer' => ['IntValidation', 'int'],
            'float' => ['Numericality', 'float'],
            'double' => ['Numericality', 'float'],
            'price' => ['Numericality', 'float'],
            'alnum' => ['Alnum', 'alnum'],
            'alpha' => ['Alpha', 'alpha'],
            'digit' => ['Digit', 'digit'],
            'number' => ['Digit', 'digit'],
            'url' => ['Url', 'url'],
            'ip' => ['Ip', 'ip'],
            'creditcard' => ['CreditCard', 'creditCard'],
            'date' => ['Date', 'date'],
            'accepted' => ['AcceptedValidation', 'accepted'],
            'accept' => ['AcceptedValidation', 'accepted'],
            'bool' => ['BoolValidation', 'bool'],
            'boolean' => ['BoolValidation', 'bool'],
            'id' => ['IdValidation', 'id'],
        ];
        foreach ($cases as $rule => [$expectedCls, $expectedKey]) {
            $rst = self::$v->getCallerValidation($rule, []);
            $this->assertEquals($expectedKey, $rst[0], "key mismatch for $rule");
            $this->assertStringContainsString($expectedCls, $rst[1], "class mismatch for $rule");
        }
    }

    public function testCallerValidationMin(): void
    {
        // min 映射到 StringLength\Min
        $rst = self::$v->getCallerValidation('min', ['2']);
        $this->assertEquals('strlenMin', $rst[0]);
        $this->assertStringContainsString('StringLength\\Min', $rst[1]);
        $this->assertEquals(['min' => '2', 'included' => true], $rst[2]);
    }

    public function testCallerValidationMax(): void
    {
        // max 映射到 StringLength\Max
        $rst = self::$v->getCallerValidation('max', ['10']);
        $this->assertEquals('strlenMax', $rst[0]);
        $this->assertStringContainsString('StringLength\\Max', $rst[1]);
        $this->assertEquals(['max' => '10', 'included' => true], $rst[2]);
    }

    public function testCallerValidationBetween(): void
    {
        $rst = self::$v->getCallerValidation('between', ['1', '100']);
        $this->assertEquals('between', $rst[0]);
        $this->assertArrayHasKey('minimum', $rst[2]);
        $this->assertArrayHasKey('maximum', $rst[2]);
        $this->assertEquals('1', $rst[2]['minimum']);
        $this->assertEquals('100', $rst[2]['maximum']);

        // 参数不足时不崩溃
        $rst2 = self::$v->getCallerValidation('between', ['5']);
        $this->assertEquals([], $rst2[2] ?? []);
    }

    public function testCallerValidationStrlen(): void
    {
        $rst = self::$v->getCallerValidation('strlen', ['2', '10']);
        $this->assertStringContainsString('StringLength', $rst[1]);
        $this->assertEquals('2', $rst[2]['min']);
        $this->assertEquals('10', $rst[2]['max']);

        // len 别名
        $rst2 = self::$v->getCallerValidation('len', ['1', '5']);
        $this->assertStringContainsString('StringLength', $rst2[1]);
    }

    public function testCallerValidationUnique(): void
    {
        // 无 attribute 不崩溃
        $rst = self::$v->getCallerValidation('unique', ['App\Modules\demo\Models\Article']);
        $this->assertStringContainsString('Uniqueness', $rst[1]);
        $this->assertArrayHasKey('model', $rst[2]);
        $this->assertArrayNotHasKey('attribute', $rst[2]);
    }

    public function testCallerValidationExpire(): void
    {
        $rst = self::$v->getCallerValidation('expire', ['20230101', '20231231']);
        $this->assertArrayHasKey('min', $rst[2]);
        $this->assertArrayHasKey('max', $rst[2]);

        // 参数不足不崩溃
        $rst2 = self::$v->getCallerValidation('expire', ['20230101']);
        $this->assertEquals([], $rst2[2] ?? []);
    }

    public function testCallerValidationDifferent(): void
    {
        $rst = self::$v->getCallerValidation('different', ['password']);
        $this->assertEquals(['with' => 'password'], $rst[2]);
    }

    public function testCallerValidationUnknownRule(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('不支持的验证规则');
        self::$v->getCallerValidation('nonexistent_rule_x', []);
    }

    // ============================================================
    //  check() / getCheckMessages() — 实际验证
    // ============================================================

    public function testCheckRequiredPass(): void
    {
        $data = ['name' => 'hello'];
        $rules = ['name|名称' => 'required'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckRequiredFail(): void
    {
        $data = ['name' => ''];
        $rules = ['name|名称' => 'required'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
        $this->assertGreaterThan(0, count($messages));
    }

    public function testCheckMinPass(): void
    {
        $data = ['name' => 'abc'];
        $rules = ['name|名称' => 'min:2'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckMinFail(): void
    {
        $data = ['name' => 'a'];
        $rules = ['name|名称' => 'min:2'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckMaxPass(): void
    {
        $data = ['name' => 'abc'];
        $rules = ['name|名称' => 'max:5'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckMaxFail(): void
    {
        $data = ['name' => 'abcdef'];
        $rules = ['name|名称' => 'max:5'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckBetweenPass(): void
    {
        $data = ['age' => '50'];
        $rules = ['age|年龄' => 'between:1,100'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckBetweenFail(): void
    {
        $data = ['age' => '200'];
        $rules = ['age|年龄' => 'between:1,100'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckEmailPass(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckEmailFail(): void
    {
        $data = ['email' => 'not-an-email'];
        $rules = ['email' => 'email'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckIntPass(): void
    {
        $data = ['id' => '123'];
        $rules = ['id|ID' => 'int'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckIntFail(): void
    {
        $data = ['id' => 'abc'];
        $rules = ['id|ID' => 'int'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckInPass(): void
    {
        $data = ['status' => '1'];
        $rules = ['status|状态' => 'in:0,1,2'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckInFail(): void
    {
        $data = ['status' => '9'];
        $rules = ['status|状态' => 'in:0,1,2'];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
    }

    public function testCheckMultipleRulesPass(): void
    {
        $data = ['name' => 'hello', 'age' => '25'];
        $rules = [
            'name|名称' => 'required|min:2|max:20',
            'age|年龄' => 'required|between:1,150',
        ];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    public function testCheckMultipleRulesFail(): void
    {
        $data = ['name' => '', 'age' => '200'];
        $rules = [
            'name|名称' => 'required|min:2|max:20',
            'age|年龄' => 'required|between:1,150',
        ];
        $messages = self::$v->getCheckMessages($data, $rules);
        $this->assertNotNull($messages);
        $this->assertGreaterThanOrEqual(2, count($messages));
    }

    public function testCheckCustomMessages(): void
    {
        $data = ['name' => ''];
        $rules = ['name|名称' => 'required'];
        $messages = ['name.required' => '名称不能为空'];
        $result = self::$v->getCheckMessages($data, $rules, $messages);
        $this->assertNotNull($result);
        $this->assertEquals('名称不能为空', $result[0]);
    }

    public function testCheckUrlPass(): void
    {
        $data = ['url' => 'https://example.com'];
        $rules = ['url' => 'url'];
        $this->assertNull(self::$v->getCheckMessages($data, $rules));
    }

    // ============================================================
    //  check() — 异常抛出
    // ============================================================

    public function testCheckThrowsOnFail(): void
    {
        $this->expectException(\Exception::class);
        $data = ['name' => ''];
        $rules = ['name|名称' => 'required'];
        self::$v->check($data, $rules);
    }

    public function testCheckPassesWithoutException(): void
    {
        $data = ['name' => 'hello'];
        $rules = ['name|名称' => 'required'];
        // 不应抛出异常
        self::$v->check($data, $rules);
        $this->assertTrue(true);
    }

    // ============================================================
    //  isPhone / isEmail / mustPhone / mustEmail
    // ============================================================

    public function testIsPhone(): void
    {
        $this->assertTrue(MyAssert::isPhone('13800138000'));
        $this->assertTrue(MyAssert::isPhone('15912345678'));
        $this->assertFalse(MyAssert::isPhone('1234'));
        $this->assertFalse(MyAssert::isPhone(''));
    }

    public function testIsEmail(): void
    {
        $this->assertTrue(MyAssert::isEmail('test@example.com'));
        $this->assertFalse(MyAssert::isEmail('not-email'));
        $this->assertFalse(MyAssert::isEmail(''));
    }

    public function testMustPhoneThrows(): void
    {
        $this->expectException(\Exception::class);
        MyAssert::mustPhone('123');
    }

    public function testMustEmailThrows(): void
    {
        $this->expectException(\Exception::class);
        MyAssert::mustEmail('not-email');
    }
}
