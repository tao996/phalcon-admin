<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\Helper\Auth\AuthRedisData;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\sdk\phaxui\Layui\Layui;
use App\Modules\tao\sdk\phaxui\Layui\LayuiForm;
use App\Modules\tao\sdk\phaxui\Layui\LayuiFormSearch;
use App\Modules\tao\sdk\phaxui\Layui\LayuiHtml;
use App\Modules\tao\sdk\phaxui\TaoHtmlHelper;
use Phax\Foundation\AppService;
use Phax\Helper\MyMvc;

class MyMvcHelper extends MyMvc
{
    /**
     * @var string 指定要加载的脚本
     */
    public string $pickName = '';
    protected string $html_helper_class = TaoHtmlHelper::class;

    /**
     * 在统一入口 BaseResponseController 中被初始化
     * @param \Phalcon\Di\Di $di
     */
    public function __construct(\Phalcon\Di\Di $di)
    {
        parent::__construct($di);
        $this->injectServices();
    }


    public function isJsonBodyRequest(): bool
    {
        return $this->di->get('request')->getQuery('data', 'string') === 'jsonbody';
    }

    public function userRecordAccess(int $currentUserId, int $recordUserId): bool
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

    /**
     * 添加当前视图目录下的文件
     * @param $file string 待添加文件名称，如 tao.css
     * @return bool
     */
    public function addViewFile(string $file): bool
    {
        $pathFile = $this->di->get('view')->getViewsDir() . $file;
        return $this->html()->includeAssetsFile($pathFile);
    }

    /**
     * 如果当前模板下存在着同名 js 文件，则引入它；比如你的模板为 add.phtml，如果存在 add.js 则会引入它
     * @return bool
     */
    public function appendTemplateJs(): bool
    {
        $theme = AppService::route()->theme;
        $pickName = $this->pickName ?: AppService::route()->getPickView(true);
        $jsFile = join(
                '/',
                $theme
                    ? [AppService::route()->getViewDIR(), $theme, $pickName]
                    : [AppService::route()->getViewDIR(), $pickName]
            ) . '.js';
        return $this->html()->includeAssetsFile($jsFile, 'js');
    }


    public function layui(): Layui
    {
        return $this->di->getShared('layui');
    }

    public function layuiHtml(): LayuiHtml
    {
        return $this->di->getShared('tao.layuiHtml');
    }

    /**
     * 编辑页面快速生成表单组件
     * @return LayuiForm
     */
    public function layuiForm(): LayuiForm
    {
        return $this->di->getShared('tao.layuiForm');
    }

    /**
     * 首页，快速生成搜索表单组件
     * @return LayuiFormSearch
     */
    public function layuiFormSearch(): LayuiFormSearch
    {
        return $this->di->getShared('tao.layuiFormSearch');
    }

    public function userHtmlHelper(): TaoUserHtmlHelper
    {
        return $this->di->getShared('tao.userHtmlHelper');
    }

    protected function injectServices(): void
    {
        $mvc = $this;
        $this->di->setShared('layui', function () use ($mvc) {
            /**
             * @var $html TaoHtmlHelper
             */
            $html = $mvc->html();
            return new Layui($html);
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
        $this->di->setShared('tao.layuiHtml', function () use ($mvc) {
            return new LayuiHtml($mvc);
        });
        $this->di->setShared('tao.layuiForm', function () use ($mvc) {
            return new LayuiForm($mvc);
        });
        $this->di->setShared('tao.layuiFormSearch', function () use ($mvc) {
            return new LayuiFormSearch($mvc);
        });
        $this->di->setShared('tao.userHtmlHelper', function () use ($mvc) {
            return new TaoUserHtmlHelper($mvc);
        });
        $this->di->setShared('tao.ossUploadHelper', function () use ($mvc) {
            return new OssUploadHelper($mvc);
        });

        $this->di->setShared('tao.a0.openHelper', function () use ($mvc) {
            return new MyOpenMvcHelper($mvc);
        });
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

    public function a0openHelper(): MyOpenMvcHelper
    {
        return $this->di->getShared('tao.a0.openHelper');
    }

}