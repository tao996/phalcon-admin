<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class MacValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (filter_var($value, FILTER_VALIDATE_MAC)) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }
}