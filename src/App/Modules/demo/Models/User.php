<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;
use Phax\Helper\ModelHelper;

/**
 * @property Profile $profile
 * @property  \Phalcon\Mvc\Model\Resultset\Simple $articles
 * @property  \Phalcon\Mvc\Model\Resultset\Simple $roles
 */
class User extends DemoBaseModel
{
    public int $id = 0;
    public string $title = '';
    public string $email = '';

    public function initialize(): void
    {
        parent::initialize();

        $this->hasOne(...ModelHelper::hasOne(
            Profile::class,
            'user_id'
        ));
        $this->hasMany(...ModelHelper::hasMany(
            Article::class,
            'user_id'
        ));
        $this->hasManyToMany(...ModelHelper::hasManyToMany(
            UsersRoles::class,
            'user_id',
            Role::class,
        ));
    }

}