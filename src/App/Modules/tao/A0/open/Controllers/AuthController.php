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
        $this->mustPostMethod();

        // 增加验证码校验，防止 PUID 暴力破解
        $captchaKey = 'open_puid_captcha';
        if (empty($this->requestData['captcha']) || empty($this->requestData['captcha']['value'])) {
            $a = rand(10, 50);
            $b = rand(10, 50);
            $c = $a + $b;
            $rule = "{$a}+{$b}=?";
            $this->vv->session()->set($captchaKey, [
                'answer' => (string)$c,
                'expire' => time() + 120,
                'attempts' => 0,
            ]);
            return [
                'rule' => $rule,
            ];
        } else {
            $captchaData = $this->vv->session()->get($captchaKey);
            if (empty($captchaData) || empty($captchaData['answer'])) {
                throw new \Exception('验证码不存在，请重新获取');
            }
            if ($captchaData['expire'] < time()) {
                $this->vv->session()->remove($captchaKey);
                throw new \Exception('验证码已过期，请重新获取');
            }
            if ($captchaData['attempts'] >= 3) {
                $this->vv->session()->remove($captchaKey);
                throw new \Exception('验证码错误次数过多，请重新获取');
            }
            if ((string)$this->requestData['captcha']['value'] !== (string)$captchaData['answer']) {
                $captchaData['attempts'] += 1;
                $this->vv->session()->set($captchaKey, $captchaData);
                throw new \Exception('验证码错误');
            }
            $this->vv->session()->remove($captchaKey);
        }

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
            // 生成服务端验证码：存储到 session，仅返回规则给客户端
            $a = rand(10, 50);
            $b = rand(10, 50);
            $c = $a + $b;
            $rule = "{$a}+{$b}=?";
            $captchaKey = 'open_login_captcha';
            $this->vv->session()->set($captchaKey, [
                'answer' => (string)$c,
                'expire' => time() + 120,
                'attempts' => 0,
            ]);
            return [
                'rule' => $rule,
            ];
        } else {
            // 校验验证码：从 session 中取出答案进行比对
            $captchaKey = 'open_login_captcha';
            $captchaData = $this->vv->session()->get($captchaKey);
            if (empty($captchaData) || empty($captchaData['answer'])) {
                throw new \Exception('验证码不存在，请重新获取');
            }
            if ($captchaData['expire'] < time()) {
                $this->vv->session()->remove($captchaKey);
                throw new \Exception('验证码已过期，请重新获取');
            }
            // 错误次数检查：最多允许3次尝试
            if ($captchaData['attempts'] >= 3) {
                $this->vv->session()->remove($captchaKey);
                throw new \Exception('验证码错误次数过多，请重新获取');
            }
            if ((string)$this->requestData['captcha']['value'] !== (string)$captchaData['answer']) {
                $captchaData['attempts'] += 1;
                $this->vv->session()->set($captchaKey, $captchaData);
                throw new \Exception('验证码错误');
            }
            // 验证通过后立即销毁
            $this->vv->session()->remove($captchaKey);
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