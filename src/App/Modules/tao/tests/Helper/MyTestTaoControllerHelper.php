<?php

namespace App\Modules\tao\tests\Helper;

use App\Modules\tao\BaseResponseController;
use App\Modules\tao\Helper\Auth\LoginUnitTestAuthAdapter;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;
use Phax\Mvc\Controller;
use Tests\Helper\MyTestControllerHelper;

class MyTestTaoControllerHelper extends MyTestControllerHelper
{
    public int $userId = 1000; // this is a superAdmin id set in config/config.php

    /**
     * @param string|\Phax\Mvc\Controller $controller
     * @return array{MyTestTaoControllerHelper,Controller}
     * @throws \Exception
     */
    public static function with(string|\Phax\Mvc\Controller $controller): array
    {
        return parent::with($controller);
    }

    /**
     * 登录，用于测试控制器操作
     * <code>
     * list($tc, $cc) = MyTestTaoControllerHelper::with(FamilyAttrController::class);
     * $tc->login($this->userId)->initialize()->setPostMethod();
     * return $cc->someAction();
     * </code>
     * @param int $userId 指定的用户 ID
     * @param bool $createUserIfNotExist 如果账号不存在，是否创建一个
     * @return $this
     */
    public function login(int $userId = 0, bool $createUserIfNotExist = true): static
    {
        if ($userId < 1) {
            $userId = $this->userId;
        }
        /**
         * @var BaseResponseController $cc
         */
        $cc = $this->controller;
        $this->request->data['getHeaders'][LoginUnitTestAuthAdapter::HeaderKeyName] = $userId;
        if (property_exists($cc, 'userId')) {
            $cc->userId = $userId;
        }
        // 测试的时候关闭
        if (property_exists($cc, 'rbacInitialize')) {
            $cc->rbacInitialize = false;
        }
        if ($createUserIfNotExist) {
            if (SystemUser::queryBuilder()
                ->int('id', $userId)->notExists()) {
                $user = new SystemUser();
                $user->assign([
                    'id' => $userId, // add this userId to config.php app.superAdmin if need
                    'seed' => '123456',
                    'password' => $cc->di->get('security')->hash('123456'),
                    'email' => 'phax-' . $userId . '@unit.test',
                    'email_valid' => true,
                    'phone' => time() . '0',
                    'phone_valid' => true,
                    'signature' => 'unit test account',
                    'status' => 1,
                ]);
                if (!$user->save()) {
                    throw new \Exception($user->getFirstError());
                }
            }
        }

        return $this;
    }

    public function afterSetController(): void
    {
        if (method_exists($this->controller, 'setLoginAdapter')) {
            $this->controller->setLoginAdapter(LoginUnitTestAuthAdapter::class, true);
        } else {
            if ($this->controller->vv instanceof MyMvcHelper) {
                $this->controller->vv->loginAuthHelper()->setAuthAdapter(LoginUnitTestAuthAdapter::class);
            }
        }
    }
}