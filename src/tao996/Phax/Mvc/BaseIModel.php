<?php

namespace Phax\Mvc;

abstract class BaseIModel extends Model
{
//    protected string $tablePrefix = 'prefix_';

    public int $id = 0;
    public int $created_at = 0; // int(11) default 0 unsigned
    public int $updated_at = 0;
    public int|null $deleted_at = null;
}