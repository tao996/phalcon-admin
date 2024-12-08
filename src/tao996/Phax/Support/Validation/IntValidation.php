<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class IntValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (filter_var($value,FILTER_VALIDATE_INT) !== false) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }
}