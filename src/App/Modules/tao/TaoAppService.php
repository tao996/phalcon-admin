<?php

namespace App\Modules\tao;

use App\Modules\tao\A0\open\Helper\ApplicationHelper;
use App\Modules\tao\A0\open\Helper\MiniAppHelper;
use App\Modules\tao\A0\open\Helper\OpenUrlHelper;
use App\Modules\tao\A0\open\Helper\TiktokHelper;
use App\Modules\tao\A0\open\Helper\WechatHelper;
use App\Modules\tao\A0\open\Helper\WepayHelper;
use App\Modules\tao\Helper\Auth\AuthRedisData;
use App\Modules\tao\Helper\CaptchaHelper;
use App\Modules\tao\Helper\LoginAuthHelper;
use App\Modules\tao\Helper\LoginUserHelper;
use App\Modules\tao\Helper\RegisterHelper;
use App\Modules\tao\sdk\qiniu\QiniuDriver;
use App\Modules\tao\sdk\RedisCache;
use App\Modules\tao\Services\ConfigService;
use Phax\Foundation\AppService;

class TaoAppService
{
    public static function captchaHelper(): CaptchaHelper
    {
        return AppService::getLazyService('tao.captchaHelper', function () {
            return new CaptchaHelper();
        });
    }

    public static function applicationHelper(): ApplicationHelper
    {
        return AppService::getLazyService('tao.applicationHelper', function () {
            \App\Modules\tao\sdk\SdkHelper::easyWechat();
            return new ApplicationHelper();
        });
    }

    public static function redisCache(): RedisCache
    {
        return AppService::getLazyService('tao.redisCache', function () {
            return new RedisCache();
        });
    }

    public static function tiktokHelper(): TiktokHelper
    {
        return AppService::getLazyService('tao.tiktokHelper', function () {
            return new TiktokHelper();
        });
    }

    public static function wepayHelper(): WepayHelper
    {
        return AppService::getLazyService('tao.wepayHelper', function () {
            return new WepayHelper();
        });
    }

    public static function wechatHelper(): WechatHelper
    {
        return AppService::getLazyService('tao.wechatHelper', function () {
            return new WechatHelper();
        });
    }

    public static function registerHelper(): RegisterHelper
    {
        return AppService::getLazyService('tao.registerHelper', function () {
            return new RegisterHelper();
        });
    }

    public static function openUrlHelper(): OpenUrlHelper
    {
        return AppService::getLazyService('tao.openUrlHelper', function () {
            return new OpenUrlHelper();
        });
    }

    public static function miniAppHelper(): MiniAppHelper
    {
        return AppService::getLazyService('tao.miniAppHelper', function () {
            return new MiniAppHelper();
        });
    }

    public static function authRedisData(): AuthRedisData
    {
        return AppService::getLazyService('tao.authRedisData', function () {
            return new AuthRedisData();
        });
    }

    /**
     * 文件上传的 token
     * https://developer.qiniu.com/kodo/manual/put-policy
     * @return QiniuDriver
     * @throws \Exception
     */
    public static function qiniuDriver(): QiniuDriver
    {
        return AppService::getLazyService('tao.qiniu', function () {
            return new QiniuDriver(ConfigService::uploadConfig());
        });
    }

    public static function loginUserHelper(): LoginUserHelper
    {
        return AppService::getLazyService('tao.loginUserHelper', function () {
            return new LoginUserHelper();
        });
    }

    public static function loginAuthHelper(): LoginAuthHelper
    {
        return AppService::getLazyService('tao.loginAuthHelper', function () {
            return new LoginAuthHelper();
        });
    }


    public static function userRecordAccess(int $currentUserId, int $recordUserId): bool
    {
        if ($currentUserId == $recordUserId) {
            return true;
        }
        $superAdminIds = AppService::superAdminIds();
        if (in_array($recordUserId, $superAdminIds)) { // 待修改记录是超级管理员记录
            if (in_array($currentUserId, $superAdminIds)) { //自己也必须是超级管理员
                $recordIndex = array_search($recordUserId, $superAdminIds);
                $currentIndex = array_search($currentUserId, $superAdminIds);
                return $currentIndex < $recordIndex;
            }
        } elseif (in_array($currentUserId, $superAdminIds)) {
            return true;
        }

        return false;
    }
}