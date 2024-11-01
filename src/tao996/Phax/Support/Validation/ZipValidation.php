<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class ZipValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (preg_match('/\d{6}/', $value)) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }
}