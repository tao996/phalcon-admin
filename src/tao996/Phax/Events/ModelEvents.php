<?php

namespace Phax\Events;


/**
 * Just for test
 * 用于检测是模型在哪一步出现问题；对于返回 bool 类型的方法，表示可停止继续执行。源码 Mvc/Model.zep
 * @link https://docs.phalcon.io/5.0/en/db-models-events
 */
trait ModelEvents
{



    /**
     * Runs after creating a record
     * @return void
     */
    public function afterCreate(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * Runs after deleting records
     * @return void
     */
    public function afterDelete(): void
    {
        echo __METHOD__, PHP_EOL;
    }
    /**
     * Runs after fetching records
     * this allows the developer to execute actions after a record is fetched from the database
     * @return void
     */
    public function afterFetch(): void
    {
        echo __METHOD__, PHP_EOL;
    }


    /**
     * Runs after saving a record
     * @return void
     */
    public function afterSave(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * Runs after updating a record
     * @return void
     */
    public function afterUpdate(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * insert/update
     * Is executed before saving and allows data manipulation
     * @return void
     */
    public function prepareSave(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * Is executed before the fields are validated for not null/empty strings or foreign keys
     * @return bool
     */
    public function beforeValidation(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed before the fields are validated for not null/empty strings or foreign keys on an insert
     * @return bool
     */
    public function beforeValidationOnCreate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed before the fields are validated for not null/empty strings or foreign keys on an update
     * @return bool
     */
    public function beforeValidationOnUpdate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed before the fields are validated for not nulls/empty strings or foreign keys on an update
     * @return bool
     */
    public function validation(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed after the fields are validated for not null/empty strings or foreign keys on an insert
     * @return bool 如果返回 false 则会停止运行
     */
    public function afterValidationOnCreate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed after the fields are validated for not null/empty strings or foreign keys on an update
     * @return true 如果返回 false 则会停止运行
     */
    public function afterValidationOnUpdate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Is executed after the fields are validated for not null/empty strings or foreign keys
     * @return bool 如果返回 false 则会停止运行
     */
    public function afterValidation(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Runs before saving a record; run before beforeCreate
     * @return bool
     */
    public function beforeSave(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Runs before creating a record; run after beforeSave
     * @return bool 返回 false 表示停止
     */
    public function beforeCreate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Runs before updating a record
     * @return bool
     */
    public function beforeUpdate(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }


    /**
     * Runs before deleting records
     * @return bool
     */
    public function beforeDelete(): bool
    {
        echo __METHOD__, PHP_EOL;
        return true;
    }

    /**
     * Runs when records are not deleted (fail)
     * @return void
     */
    public function notDeleted(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * Runs when records are not saved (fail)
     * @return void
     */
    public function notSaved(): void
    {
        echo __METHOD__, PHP_EOL;
    }

    /**
     * Insert/Update, Is executed after an integrity validator fails
     */
    public function onValidationFails(): void
    {
        echo __METHOD__, PHP_EOL;
    }
}