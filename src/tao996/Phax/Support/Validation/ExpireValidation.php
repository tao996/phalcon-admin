<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class ExpireValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
//        $value = $validation->getValue($field);
        $min = $this->options['min'];
        $max = $this->options['max'];
        if (strtotime($min) <= time() && time() <= strtotime($max)) {
            return true;
        }
        return $this->addMessage($validation,$this->options,$field);
    }
}