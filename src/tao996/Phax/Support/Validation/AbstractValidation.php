<?php

namespace Phax\Support\Validation;

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\AbstractValidator;
use Phalcon\Messages\Message;
use Phax\Support\Facade\MyHelperFacade;

abstract class AbstractValidation extends AbstractValidator
{

    protected function addMessage(Validation $validation, array $placeholders, string $filed): false
    {
        $message = $this->getOption('message');
        if (!isset($placeholders['field'])) {
            $placeholders['field'] = $filed;
        }

        $validation->appendMessage(
            new Message(
                MyHelperFacade::interpolate($message, $placeholders,':',''),
            )
        );
        return false;
    }
}