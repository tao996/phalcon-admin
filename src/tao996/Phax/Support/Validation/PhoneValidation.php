<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class PhoneValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (self::match($value)) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }

    public static function match($phone): bool
    {
        if (empty($phone)) {
            return false;
        }
        // 中國大陸手機號或香港手機號
        return preg_match('/^1[3-9]\d{9}$/', $phone) || preg_match('/^\d{8}$/', $phone);
    }
}