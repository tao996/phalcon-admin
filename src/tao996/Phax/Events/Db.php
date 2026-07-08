<?php

namespace Phax\Events;

use Phalcon\Logger\Exception;
use Phax\Foundation\AppService;

class Db
{
    /**
     * sql 日志
     * @return void
     * @throws Exception
     */
    public static function attach(\Phalcon\Di\Di $di,\Phalcon\Db\Adapter\Pdo\AbstractPdo $db): void
    {
        $em = $di->get('eventsManager');

        $path = AppService::config()->getString('database.log.path');
        preg_match('|{(\w+)}|',$path, $matches);
        if (!empty($matches)) {
            $path = str_replace($matches[0], date($matches[1]), $path);
        }

        $adapter = new \Phalcon\Logger\Adapter\Stream($path);
        $logger = new \Phalcon\Logger\Logger('messages', [
            'db' => $adapter
        ]);

        $em->attach('db:beforeQuery', function (\Phalcon\Events\Event $event, \Phalcon\Db\Adapter\Pdo\AbstractPdo $db) use ($logger) {
            $logger->info($db->getSQLStatement());
            if (IS_DEBUG) {
                $logger->info(json_encode($db->getSQLVariables()));
            }
//            switch ($event->getType()) {
//                case 'beforeQuery':
//                    $logger->info($db->getSQLStatement());
//                    break;
//
//            }
        });
        $db->setEventsManager($em);
    }
}