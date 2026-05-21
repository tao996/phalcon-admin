<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

/**
 * Id > 0
 */
class IdValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        if (filter_var($value, FILTER_VALIDATE_INT) !== false && intval($value) > 0) {
            return true;
        }
        return $this->addMessage($validation, [], $field);
    }
}