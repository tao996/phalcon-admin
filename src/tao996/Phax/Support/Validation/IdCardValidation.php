<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class IdCardValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (preg_match('/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/', $value)) {
            return true;
        }
        return $this->addMessage($validation,[],$field);
    }
}