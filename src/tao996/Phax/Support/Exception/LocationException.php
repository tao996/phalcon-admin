<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace Phax\Support\Exception;

class LocationException extends \Exception
{
    public function __construct(string $location = "")
    {
        parent::__construct($location, 302, null);
    }
}