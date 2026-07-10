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
        if (!AppService::has('tao.captchaHelper')) {
            AppService::setShared('tao.captchaHelper', new CaptchaHelper());
        }
        return AppService::getShared('tao.captchaHelper');
    }

    public static function applicationHelper(): ApplicationHelper
    {
        if (!AppService::has('tao.openApplicationHelper')) {
            \App\Modules\tao\sdk\SdkHelper::easyWechat();
            AppService::setShared('tao.openApplicationHelper', new ApplicationHelper());
        }
        return AppService::getShared('tao.openApplicationHelper');
    }

    public static function redisCache(): RedisCache
    {
        if (!AppService::has('tao.redisCache')) {
            AppService::setShared('tao.redisCache', new RedisCache());
        }
        return AppService::getShared('tao.redisCache');
    }

    public static function tiktokHelper(): TiktokHelper
    {
        if (!AppService::has('tao.tiktokHelper')) {
            AppService::setShared('tao.tiktokHelper', new TiktokHelper());
        }
        return AppService::getShared('tao.tiktokHelper');
    }

    public static function wepayHelper(): WepayHelper
    {
        if (!AppService::has('tao.wepayHelper')) {
            AppService::setShared('tao.wepayHelper', new WepayHelper());
        }
        return AppService::getShared('tao.wepayHelper');
    }

    public static function wechatHelper(): WechatHelper
    {
        if (!AppService::has('tao.wechatHelper')) {
            AppService::setShared('tao.wechatHelper', new WechatHelper());
        }
        return AppService::getShared('tao.wechatHelper');
    }

    public static function registerHelper(): RegisterHelper
    {
        if (!AppService::has('tao.registerHelper')) {
            AppService::setShared('tao.registerHelper', new RegisterHelper());
        }
        return AppService::getShared('tao.registerHelper');
    }

    public static function openUrlHelper(): OpenUrlHelper
    {
        if (!AppService::has('tao.openUrlHelper')) {
            AppService::setShared('tao.openUrlHelper', new OpenUrlHelper());
        }
        return AppService::getShared('tao.openUrlHelper');
    }

    public static function miniAppHelper(): MiniAppHelper
    {
        if (!AppService::has('tao.miniAppHelper')) {
            AppService::setShared('tao.miniAppHelper', new MiniAppHelper());
        }
        return AppService::getShared('tao.miniAppHelper');
    }

    public static function authRedisData(): AuthRedisData
    {
        if (!AppService::has('tao.authRedisData')) {
            AppService::setShared('tao.authRedisData', new AuthRedisData());
        }
        return AppService::getShared('tao.authRedisData');
    }

    /**
     * 文件上传的 token
     * https://developer.qiniu.com/kodo/manual/put-policy
     * @return QiniuDriver
     * @throws \Exception
     */
    public static function qiniuDriver(): QiniuDriver
    {
        if (!AppService::has('tao.qiniu')) {
            AppService::setShared('tao.qiniu', new QiniuDriver(ConfigService::uploadConfig()));
        }
        return AppService::getShared('tao.qiniu');
    }

    public static function loginUserHelper(): LoginUserHelper
    {
        if (!AppService::has('tao.login')) {
            AppService::setShared('tao.login', new LoginUserHelper());
        }
        return AppService::getShared('tao.login');
    }

    public static function loginAuthHelper(): LoginAuthHelper
    {
        if (!AppService::has('tao.loginAuthHelper')) {
            AppService::setShared('tao.loginAuthHelper', new LoginAuthHelper());
        }
        return AppService::getShared('tao.loginAuthHelper');
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