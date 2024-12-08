<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class BoolValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', 't', 'ok', 'on',
                'false', 'f', 'no', 'off'
            ]);
        }
        if (is_bool($value)) {
            return true;
        }
        return $this->addMessage($validation,[],$field);
    }
}