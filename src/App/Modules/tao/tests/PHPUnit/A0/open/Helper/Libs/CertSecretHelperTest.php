<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Helper\Libs;


use App\Modules\tao\A0\open\Helper\Libs\CertSecretHelper;
use PHPUnit\Framework\TestCase;

class CertSecretHelperTest extends TestCase
{
    public function testEncrypt()
    {
        $origin = 'abcde';
        $data = CertSecretHelper::encryptData($origin, 2);
        $this->assertEquals('abdde', $data);
        $this->assertEquals($origin, CertSecretHelper::decryptData($data, 2));

        $origin = 'aaaaaaaaaaaa';
        $data = CertSecretHelper::encryptData($origin, 3, 4);
        $this->assertNotEquals($origin, $data);
        $this->assertEquals($origin, CertSecretHelper::decryptData($data, 3, 4));
    }
}