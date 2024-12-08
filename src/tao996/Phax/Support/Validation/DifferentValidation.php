<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class DifferentValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        $with = $this->options['with'];
        if ($value != $with) {
            return true;
        }
        return $this->addMessage($validation, $this->options, $field);
    }
}