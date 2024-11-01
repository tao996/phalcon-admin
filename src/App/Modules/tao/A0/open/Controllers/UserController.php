<?php

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\A0\open\BaseOpenDeveloperController;
use App\Modules\tao\A0\open\Models\OpenUserOpenid;


class UserController extends BaseOpenDeveloperController
{
    protected array|string $userActions = '*';
    protected array|string $openActions = ['logout'];

    /**
     * 更新用户资料
     * @throws \Exception
     */
    public function infoAction()
    {
        $record = OpenUserOpenid::queryBuilder()
            ->int('user_id', $this->loginUser()->id)
            ->string('appid', $this->getAppid())
            ->findFirstModel();
        if (!$record) {
            throw new \Exception('没有找到 userOpenid 记录');
        }
        if ($this->request->isGet()) {
            return $record->toArray([
                'user_id',
                'avatar_url',
                'nickname',
                'openid'
            ]);
        } elseif ($this->request->isPost()) {
            $this->vv->validate()->check($this->requestData, ['name' => 'required', 'value' => 'required']);
            if (!in_array($this->requestData['name'], ['avatar_url', 'nickname'])) {
                throw new \Exception('不允许修改的字段');
            }

            $record->assign([
                $this->requestData['name'] => $this->requestData['value']
            ],
                ['avatar_url', 'nickname', 'gender', 'city', 'province', 'country']
            );
            return $this->saveModelResponse($record->save(), false);
        }
        return [];
    }

    public function logoutAction()
    {
        $this->vv->loginAuthHelper()->logout();
        return '退出成功';
    }
}