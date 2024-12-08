<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;

class AfterValidation extends AbstractValidation
{

    public function validate(Validation $validation, $field): bool
    {
        $value = $validation->getValue($field);
        $date = $this->options['date'];
        if (strtotime($value) >= strtotime($date)) {
            return true;
        }
        return $this->addMessage($validation, [
            'date' => $date,
        ], $field);
    }
}