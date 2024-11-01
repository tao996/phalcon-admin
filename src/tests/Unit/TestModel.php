<?php

use Phax\Mvc\Model;

class TestUser extends Model
{
    use \Phax\Traits\SoftDelete;

    public int $id = 0;
    public string $name = '';
    public int $age = 0;

    public function beforeSave()
    {
        if ($this->age == -1) {
            throw new \Exception('sorry, age less than 0');
        } elseif ($this->age == 10000) {
            throw new \Exception('sorry, age too large');
        }
    }
}