<?php

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\A0\open\BaseOpenMiniController;
use App\Modules\tao\A0\open\Models\OpenUserOpenid;
use App\Modules\tao\TaoAppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Validate;


class UserController extends BaseOpenMiniController
{
    protected array|string $userActions = ['info'];
    protected array|string $openActions = ['logout'];

    /**
     * 更新用户资料
     * @throws \Exception
     */
    public function infoAction()
    {
        $record = OpenUserOpenid::queryBuilder($this->getDI())
            ->int('user_id', $this->loginUser()->id)
            ->string('appid', $this->getAppid())
            ->findFirstModel();
        if (!$record) {
            throw new BusinessException('没有找到 userOpenid 记录');
        }
        if ($this->request->isGet()) {
            return $record->toArray([
                'user_id',
                'avatar_url',
                'nickname',
                'openid'
            ]);
        } elseif ($this->request->isPost()) {
            Validate::checkData($this->requestData, ['name' => 'required', 'value' => 'required']);
            if (!in_array($this->requestData['name'], ['avatar_url', 'nickname'])) {
                throw new BusinessException('不允许修改的字段');
            }

            $record->assign([
                $this->requestData['name'] => $this->requestData['value']
            ],
                ['avatar_url', 'nickname', 'gender', 'city', 'province', 'country']
            );
            return $this->saveModelResponse($record->save());
        }
        return [];
    }

    /**
     * 退出登录
     * @return string
     */
    public function logoutAction()
    {
        TaoAppService::loginAuthHelper()->logout();
        return '退出成功';
    }
}