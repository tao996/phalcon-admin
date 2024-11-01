<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Service\OpenAppService;
use App\Modules\tao\A0\open\Service\OpenConfigService;
use App\Modules\tao\A0\open\Service\OpenMchService;
use App\Modules\tao\A0\open\Service\OpenOrderService;
use App\Modules\tao\A0\open\Service\OpenUserService;
use App\Modules\tao\Helper\MyMvcHelper;

class MyOpenMvcHelper
{
    public function __construct(public MyMvcHelper $mvc)
    {
        $this->injectServices();
    }

    protected function injectServices(): void
    {
        $helper = $this;
        $this->mvc->di->setShared('open.configService', function () use ($helper) {
            return new OpenConfigService($helper);
        });
        $this->mvc->di->setShared('open.mchService', function () use ($helper) {
            return new OpenMchService($helper);
        });
        $this->mvc->di->setShared('open.appService', function () use ($helper) {
            return new OpenAppService($helper);
        });
        $this->mvc->di->setShared('open.userService', function () use ($helper) {
            return new OpenUserService($helper);
        });
        $this->mvc->di->setShared('open.orderService', function () use ($helper) {
            return new OpenOrderService($helper);
        });

        $this->mvc->di->setShared('open.application', function () use ($helper) {
            return new ApplicationHelper($helper);
        });
        $this->mvc->di->setShared('open.tiktokHelper', function () use ($helper) {
            return new TiktokHelper($helper);
        });
        $this->mvc->di->setShared('open.wechatHelper', function () use ($helper) {
            return new WechatHelper($helper);
        });
        $this->mvc->di->setShared('open.openUrlHelper', function () use ($helper) {
            return new OpenUrlHelper($helper);
        });
        $this->mvc->di->setShared('open.wepayHelper', function () use ($helper) {
            return new WepayHelper($helper);
        });

        $this->mvc->di->setShared('open.miniAppHelper', function () use ($helper) {
            return new MiniAppHelper($helper);
        });
    }

    public function configService(): OpenConfigService
    {
        return $this->mvc->di->get('open.configService');
    }

    public function mchService(): OpenMchService
    {
        return $this->mvc->di->get('open.mchService');
    }

    public function appService(): OpenAppService
    {
        return $this->mvc->di->get('open.appService');
    }

    public function userService(): OpenUserService
    {
        return $this->mvc->di->get('open.userService');
    }

    public function orderService(): OpenOrderService
    {
        return $this->mvc->di->get('open.orderService');
    }

    public function application(): ApplicationHelper
    {
        return $this->mvc->di->get('open.application');
    }

    public function tiktokHelper(): TiktokHelper
    {
        return $this->mvc->di->get('open.tiktokHelper');
    }

    public function wechatHelper(): WechatHelper
    {
        return $this->mvc->di->get('open.wechatHelper');
    }

    public function openUrlHelper(): OpenUrlHelper
    {
        return $this->mvc->di->get('open.openUrlHelper');
    }

    public function wepayHelper(): WepayHelper
    {
        return $this->mvc->di->get('open.wepayHelper');
    }

    public function miniAppHelper(): MiniAppHelper
    {
        return $this->mvc->di->get('open.miniAppHelper');
    }
}