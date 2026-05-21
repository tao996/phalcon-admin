<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class NotBetweenValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = intval($validation->getValue($field));
        $min = intval( $this->options['min']);
        $max = intval($this->options['max']);
        if ($value < $min || $max < $value) {
            return true;
        }
        return $this->addMessage($validation, $this->options, $field);
    }
}