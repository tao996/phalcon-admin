<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\Helper\Auth\AuthRedisData;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\phaxui\HtmlAssets;
use App\Modules\tao\sdk\phaxui\Layui\Layui;
use App\Modules\tao\sdk\phaxui\Layui\LayuiHtml;
use App\Modules\tao\Services\ConfigService;
use App\Modules\tao\Services\LogService;
use App\Modules\tao\Services\MenuService;
use App\Modules\tao\Services\MigrationService;
use App\Modules\tao\Services\NodeService;
use App\Modules\tao\Services\RoleService;
use App\Modules\tao\Services\SmsCodeService;
use App\Modules\tao\Services\UploadfileService;
use App\Modules\tao\Services\UserService;
use Phax\Helper\MyMvc;

class MyMvcHelper extends MyMvc
{
    /**
     * @var string 指定要加载的脚本
     */
    public string $pickName = '';

    public function __construct(\Phalcon\Di\Di $di)
    {
        parent::__construct($di);
        $this->injectServices();
    }

    /**
     * 超级管理员 ID
     * @return array
     */
    public function superAdminIds(): array
    {
        return $this->config()->getSuperAdminIds();
    }

    /**
     * 添加当前视图目录下的文件
     * @param $file string 待添加文件名称，如 tao.css
     * @return bool
     */
    public function addViewFile(string $file): bool
    {
        $pathFile = $this->di->get('view')->getViewsDir() . $file;
        return HtmlAssets::includeAssetsFile($pathFile);
    }

    public function taoViewIndex(string $suffix = '.phtml'): string
    {
        return $this->route()->mergeFileViewWithTheme(dirname(__DIR__)) . 'index' . $suffix;
    }

    /**
     * 如果当前模板下存在着同名 js 文件，则引入它；比如你的模板为 add.phtml，如果存在 add.js 则会引入它
     * @return bool
     */
    public function appendTemplateJs(): bool
    {
        $theme = $this->route()->theme;
        $pickName = $this->pickName ?: $this->route()->getPickView(true);
        $jsFile = join(
                '/',
                $theme
                    ? [$this->route()->getViewPath(), $theme, $pickName]
                    : [$this->route()->getViewPath(), $pickName]
            ) . '.js';
        return HtmlAssets::includeAssetsFile($jsFile, 'js');
    }

    /**
     * 模板目录
     * @return string
     */
    public function viewsDir(): string
    {
        return $this->view()->getViewsDir();
    }

    public function layui(): Layui
    {
        return Layui::getInstance();
    }

    public function layuiHtml(): LayuiHtml
    {
        return $this->di->getShared('tao.layuiHtml');
    }

    protected function injectServices(): void
    {
        $mvc = $this;
        $this->di->setShared('tao.configService', function () use ($mvc) {
            return new ConfigService($mvc);
        });
        $this->di->setShared('tao.smsCodeService', function () use ($mvc) {
            return new SmsCodeService($mvc);
        });
        $this->di->setShared('tao.userService', function () use ($mvc) {
            return new UserService($mvc);
        });
        $this->di->setShared('tao.uploadfileService', function () use ($mvc) {
            return new UploadfileService($mvc);
        });
        $this->di->setShared('tao.nodeService', function () use ($mvc) {
            return new NodeService($mvc);
        });

        $this->di->setShared('tao.roleService', function () use ($mvc) {
            return new RoleService($mvc);
        });
        $this->di->setShared('tao.migrationService', function () use ($mvc) {
            return new MigrationService($mvc);
        });

        $this->di->setShared('tao.menuService', function () use ($mvc) {
            return new MenuService($mvc);
        });

        $this->di->setShared('tao.captchaHelper', function () use ($mvc) {
            return new CaptchaHelper($mvc);
        });
        $this->di->setShared('tao.responseHelper', function () use ($mvc) {
            return new ResponseHelper($mvc);
        });
        $this->di->setShared('tao.redirectHelper', function () use ($mvc) {
            return new RedirectHelper($mvc);
        });
        $this->di->setShared('tao.registerHelper', function () use ($mvc) {
            return new RegisterHelper($mvc);
        });
        $this->di->setShared('tao.loginUserHelper', function () use ($mvc) {
            return new LoginUserHelper($mvc);
        });
        $this->di->setShared('tao.authRedisData', function () use ($mvc) {
            return new AuthRedisData($mvc);
        });

        $this->di->setShared('tao.loginAuthHelper', function () use ($mvc) {
            return new LoginAuthHelper($mvc);
        });
        $this->di->setShared('tao.logService', function () use ($mvc) {
            return new LogService($mvc);
        });
        $this->di->setShared('tao.layuiHtml', function () use ($mvc) {
            return new LayuiHtml($mvc);
        });
        $this->di->setShared('tao.ossUploadHelper', function () use ($mvc) {
            return new OssUploadHelper($mvc);
        });

        $this->di->setShared('tao.a0.cmsHelper', function () use ($mvc) {
            return new MyCmsMvcHelper($mvc);
        });
        $this->di->setShared('tao.a0.openHelper', function () use ($mvc) {
            return new MyOpenMvcHelper($mvc);
        });
    }

    public function configService(): ConfigService
    {
        return $this->di->getShared('tao.configService');
    }

    public function smsCodeService(): SmsCodeService
    {
        return $this->di->getShared('tao.smsCodeService');
    }

    public function userService(): UserService
    {
        return $this->di->getShared('tao.userService');
    }

    public function uploadfileService(): UploadfileService
    {
        return $this->di->getShared('tao.uploadfileService');
    }

    public function nodeService(): NodeService
    {
        return $this->di->getShared('tao.nodeService');
    }

    public function roleService(): RoleService
    {
        return $this->di->getShared('tao.roleService');
    }

    public function migrationService(): MigrationService
    {
        return $this->di->getShared('tao.migrationService');
    }

    public function menuService(): MenuService
    {
        return $this->di->getShared('tao.menuService');
    }

    public function captchaHelper(): CaptchaHelper
    {
        return $this->di->getShared('tao.captchaHelper');
    }

    public function responseHelper(): ResponseHelper
    {
        return $this->di->getShared('tao.responseHelper');
    }

    public function redirectHelper(): RedirectHelper
    {
        return $this->di->getShared('tao.redirectHelper');
    }

    public function registerHelper(): RegisterHelper
    {
        return $this->di->getShared('tao.registerHelper');
    }

    public function loginUserHelper(): LoginUserHelper
    {
        return $this->di->getShared('tao.loginUserHelper');
    }

    public function authRedisData(): AuthRedisData
    {
        return $this->di->getShared('tao.authRedisData');
    }

    public function loginAuthHelper(): LoginAuthHelper
    {
        return $this->di->getShared('tao.loginAuthHelper');
    }

    /**
     * 当前登录的用户
     * @return SystemUser
     * @throws \Exception
     */
    public function user(): SystemUser
    {
        return $this->loginUserHelper()->user();
    }

    public function logService(): LogService
    {
        return $this->di->getShared('tao.logService');
    }

    public function a0cmsHelper(): MyCmsMvcHelper
    {
        return $this->di->getShared('tao.a0.cmsHelper');
    }

    public function ossUploadHelper(): OssUploadHelper
    {
        return $this->di->getShared('tao.ossUploadHelper');
    }

    public function a0openHelper(): MyOpenMvcHelper
    {
        return $this->di->getShared('tao.a0.openHelper');
    }

    public function limitRate(string $action, int $userId = 0): LimitRateHelper
    {
        return new LimitRateHelper($this->redis(), $action, $userId);
    }
}