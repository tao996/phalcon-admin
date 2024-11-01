<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class AcceptedValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);

        if (in_array($value, ["yes", "on", "true", "1", 1, true])) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }
}