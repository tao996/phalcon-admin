<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\A0\open\Controllers;

use App\Modules\tao\A0\open\BaseOpenMiniController;
use App\Modules\tao\Models\SystemUser;
use Phax\Utils\MyData;

class AuthController extends BaseOpenMiniController
{
    protected array|string $openActions = ['puid', 'login'];

    /**
     * 使用统一平台登录码，格式 puid.id
     * @return array
     * @throws \Exception
     */
    public function puidAction()
    {
        $puid = $this->requestData['puid'] ?? '';
        if (empty($puid)) {
            throw new \Exception('puid 参数不能为空');
        }
        $items = explode('.', $puid);
        if (empty($items) || count($items) != 2) {
            throw new \Exception('puid 参数格式错误:1');
        }
        if (intval($items[1]) < 1) {
            throw new \Exception('puid 参数格式错误:2');
        } elseif (empty($items[0])) {
            throw new \Exception('puid 参数格式错误:3');
        }
        if ($user = SystemUser::queryBuilder($this->getDI())
            ->int('id', $items[1])->string('puid', $items[0])
            ->int('status', 1)
            ->findFirstArray()) {
            return $this->authData($user);
        } else {
            throw new \Exception('没有找到用户信息');
        }
    }

    /**
     * 账号密码登录
     * @return array
     */
    public function loginAction(): array
    {
        $this->vv->validate()->check($this->requestData, [
            'account|账号' => 'required',
            'password|密码' => 'required'
        ]);
        if (empty($this->requestData['captcha']) || empty($this->requestData['captcha']['value'])) {
            // 输出验证码
            $a = rand(10, 50);
            $b = rand(10, 50);
            $c = $a + $b;
            $rule = "{$a}+{$b}=?";
            $time = time();
            $validAnswer = md5($time . '|a' . $rule . '|b' . $c);
            return [
                'rule' => $rule,
                'time' => $time,
                'valid' => $validAnswer
            ];
        } else {
            $this->vv->validate()->check($this->requestData['captcha'], [
                'rule' => 'required',
                'time' => 'required',
                'valid' => 'required',
                'value' => 'required'
            ]);
            if (intval($this->requestData['captcha']['time']) + 60 < time()) {
                throw new \Exception('验证码已过期');
            }
            if (strlen($this->requestData['captcha']['valid']) != 32) {
                throw new \Exception('(1)验证码错误');
            }
            $validAnswer = md5(
                $this->requestData['captcha']['time'] . '|a' . $this->requestData['captcha']['rule'] . '|b' . $this->requestData['captcha']['value']
            );
            if ($validAnswer != $this->requestData['captcha']['valid']) {
                throw new \Exception('(2)验证码错误');
            }
        }

        $user = $this->vv->userService()->loginWithPassword(
            $this->requestData['account'],
            $this->requestData['password']
        )->toArray();
        return $this->authData($user);
    }

    /**
     * @throws \Exception
     */
    private function authData(array $user): array
    {
        $baseInfo = [
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar_url' => $user['head_img'] ?? $user['avatar'] ?? '',
        ];
        $baseInfo['ts'] = $this->tryGetLoginAuth()
            ->getAdapter()->saveUser(['id' => $baseInfo['user_id']]);
        return $baseInfo;
    }
}