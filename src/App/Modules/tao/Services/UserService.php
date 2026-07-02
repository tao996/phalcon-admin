<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Data\UserBindPlatform;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Models\SystemUserBind;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Logger;

class UserService
{

    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 账号是否合法
     * @param string $account
     * @return void
     */
    public function mustAccountString(string $account): void
    {
        if (empty($account)) {
            throw new BusinessException('账号不能为空');
        }
        if ($this->mvc->validate()->isEmail($account) || $this->mvc->validate()->isPhone($account)) {
            return;
        }
        throw new BusinessException('不是一个合法的账号');
    }

    /**
     * 账号是否可以注册
     * @param string $account
     * @return void
     */
    public function mustCanRegister(string $account): void
    {
        $isEmail = $this->mvc->smsCodeService()->mustReceiver($account);

        if ($row = SystemUser::queryBuilder($this->mvc->getDi())
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirstArray()
        ) {
            if ($isEmail) {
                if ($row['email_valid'] == 1) {
                    throw new BusinessException('邮箱已经被占用');
                }
            } else {
                if ($row['phone_valid'] == 1) {
                    throw new BusinessException('手机号已经被占用');
                }
            }
        }
    }

    /**
     * 账号是否可以登录
     * @param mixed $account
     * @return void
     */
    public function mustCanLogin(mixed $account): void
    {
        $isEmail = $this->mvc->smsCodeService()->mustReceiver($account);

        if ($row = SystemUser::queryBuilder($this->mvc->getDi())
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirstArray()
        ) {
            if ($row[$isEmail ? 'email_valid' : 'phone_valid'] != 1) {
                throw new BusinessException('账号不存在或未激活');
            }
        } else {
            throw new BusinessException('账号不存在');
        }
    }

    /**
     * 密码是否合法
     * @param string $password
     * @return void
     */
    public function mustPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new BusinessException('密码最少为8位');
        }
        if (!preg_match('/[a-zA-Z]/', $password)) {
            throw new BusinessException('密码必须包含字母');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new BusinessException('密码必须包含数字');
        }
    }

    /**
     * @param array $condition
     * @return SystemUser
     */
    public function mustGetUser(array $condition): SystemUser
    {
        $qb = SystemUser::queryBuilder($this->mvc->getDi())
            ->where($condition);

        if ($user = $qb->findFirstModel()) {
            return $user;
        }
        throw new BusinessException('没有找到符合条件的用户');
    }

    /**
     * 注册账号
     * @param SystemUser $user
     * @return void
     */
    public function create(SystemUser $user): void
    {
        if (!$user->save()) {
            throw new BusinessException('注册账号失败');
        }
    }

    /**
     * 批量查询用户信息
     * @param array $userIds
     * @param array $columns
     * @return array
     */
    public function findColumns(array $userIds, array $columns = ['id', 'nickname']): array
    {
        if (empty($userIds)) {
            return [];
        }
        return SystemUser::queryBuilder($this->mvc->getDi())
            ->in('id', $userIds)
            ->columns($columns)
            ->find();
    }


    /**
     * 设置新的账号
     * @param string $account
     * @param SystemUser $user
     * @return void
     */
    public function newAccount(string $account, SystemUser $user): void
    {
        if ($this->mvc->validate()->isEmail($account)) {
            $user->email = $account;
            $user->email_at = time();
            $user->email_valid = 1;
        } elseif ($this->mvc->validate()->isPhone($account)) {
            $user->phone = $account;
            $user->phone_at = time();
            $user->phone_valid = 1;
        }
    }

    /**
     * 设置新的密码
     */
    public function newPassword(string $password, SystemUser $user): void
    {
        $this->mustPassword($password);
        $user->password = $this->mvc->security()->hash($password);
    }

    /**
     * 检查密码是否正确
     */
    public function checkPassword(string $password, SystemUser $user, bool $must = true): void
    {
        if (empty($password) && $must) {
            throw new BusinessException('密码不能为空');
        }
        if (!$this->mvc->security()->checkHash($password, $user->password)) {
            throw new BusinessException('密码错误');
        }
    }

    /**
     * 用户存在登录账号
     * @param SystemUser $user
     * @return bool
     */
    public function hasLoginAccount(SystemUser $user): bool
    {
        if ($user->phone_valid == 1 && !empty($user->phone)) {
            return true;
        }
        if ($user->email_valid == 1 && !empty($user->email)) {
            return true;
        }
        return false;
    }


    public function enableChangePhoneAt(int $phoneAt): bool
    {
        return $phoneAt == 0 || $phoneAt + 60 * 60 * 24 * 30 < time();
    }

    public function enableChangeEmailAt(int $emailAt): bool
    {
        return $emailAt == 0 || $emailAt + 60 * 60 * 24 * 30 < time();
    }

    /**
     * 待注册的手机号是否合法
     */
    public function mustUniquePhone(string $phone, SystemUser $user, bool $assign = false): void
    {
        if ($user->phone == $phone) {
            return;
        }
        if (empty($phone)) {
            throw new BusinessException('待检测的手机号不能为空');
        }
        $this->mvc->validate()->mustPhone($phone);
        if ($user->getQueryBuilder($this->mvc->di)
            ->string('phone', $phone)
            ->notEqual("id", $user->id)
            ->exits()) {
            throw new BusinessException('手机号重复');
        }
        if ($assign) {
            $user->phone = $phone;
        }
    }

    /**
     * 检查待注册的电子邮箱是否合法
     */
    public function mustUniqueEmail(string $email, SystemUser $user, bool $assign = false): void
    {
        if ($user->email == $email) {
            return;
        }
        if ($user->getQueryBuilder($this->mvc->di)->string('email', $email)
            ->notEqual('id', $user->id)->exits()) {
            throw new BusinessException('邮箱地址重复');
        }
        if ($assign) {
            $user->email = $email;
        }
    }

    /**
     * 能否修改账号
     * @param string $kind 类型 account|phone|email
     * @param string $account 待检查的账号
     */
    public function mustAllowChangeAccount(string $kind, string $account, SystemUser $user): void
    {
        if (empty($account)) {
            throw new BusinessException('账号不能为空');
        }
        switch ($kind) {
            case 'phone':
                if (!$this->enableChangePhoneAt($user->phone_at)) {
                    throw new BusinessException('每 30天 才能修改一次电话号码');
                }
                $this->mustUniquePhone($account, $user);
                break;
            case 'email':
                if (!$this->enableChangeEmailAt($user->email_at)) {
                    throw new BusinessException('每 30天 才能修改一次电子邮箱');
                }
                $this->mustUniqueEmail($account, $user);
                break;
            default:
                throw new BusinessException('不支持修改的账号类型');
        }
    }

    /**
     * 通过手机号/邮箱来查询账号
     * @param string $account
     * @param string $kind 默认为 空
     * @return null|SystemUser
     */
    public function findByAccount(string $account, string $kind = ''): ?SystemUser
    {
        if (empty($account)) {
            throw new BusinessException('账号不能为空');
        } elseif (empty($kind)) {
            if ($this->mvc->validate()->isEmail($account)) {
                $kind = 'email';
            } elseif ($this->mvc->validate()->isPhone($account)) {
                $kind = 'phone';
            }
        }
        switch ($kind) {
            case 'email':
                return SystemUser::queryBuilder($this->mvc->getDi())
                    ->string('email', $account)->findFirstModel();
            case 'phone':
                return SystemUser::queryBuilder($this->mvc->getDi())
                    ->string('phone', $account)->findFirstModel();
            default:
                throw new BusinessException('不支持的账号类型');
        }
    }

    /**
     * 获取用户角色列表
     * @return array{int,string}
     * @throws \Exception
     */
    public function getRolesAttr(SystemUser $user): array
    {
        if ($user->role_ids) {
            $ids = explode(',', $user->role_ids);
            $rows = SystemRole::queryBuilder($this->mvc->getDi())
                ->in('id', $ids)
                ->findColumn(['id', 'title']);
            return array_column($rows, 'title', 'id');
        }
        return [];
    }

    /**
     * 添加绑定
     * @param SystemUser $user
     * @param string $bind Data::Xxx = gmail|tiktokMini|wechatMini|wechatOfficial
     * @return void
     */

    /**
     * 添加绑定
     * @param SystemUser $user
     * @param string $bind UserBindPlatform::Xxx = gmail|tiktokMini|wechatMini|wechatOfficial
     * @return void
     * @throws \Exception
     */
    public function addBinds(SystemUser $user, string $bind): void
    {
        if (!UserBindPlatform::isValid($bind)) {
            throw new BusinessException('不支持绑定类型:' . $bind);
        }
        $bindModel = new SystemUserBind();
        $bindModel->user_id = $user->id;
        $bindModel->platform = $bind;
        if (!$bindModel->save()) {
            throw new \Exception('绑定失败：' . json_encode($bindModel->getMessages()));
        }
    }

    /**
     * 是否为超级管理员
     * @param SystemUser|null $user
     * @return bool
     */
    public function isSuperAdmin(SystemUser|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }
        return in_array($user->id, $this->mvc->superAdminIds());
    }


    /**
     * 账号必须是正常状态
     */
    public function activeStatus(SystemUser $user): void
    {
        if ($user->status == SystemUser::STATUS_DELETE) {
            throw new BusinessException('当前账号已经被禁止登录');
        }
    }

    public function addUserProfile(\Hybridauth\User\Profile $profile): SystemUser
    {
        // 准备注册账号
        $qb = SystemUser::queryBuilder($this->mvc->getDi());
        if ($profile->email) {
            $qb->where(['email' => $profile->email, 'email_valid' => 1]);
        }
        if ($user = $qb->findFirstModel()) {
            return $user;
        }
        if ($profile->phone) {
            $qb->where(['phone' => $profile->phone, 'phone_valid' => 1]);
        }
        if ($user = $qb->findFirstModel()) {
            return $user;
        }
        // 注册
        $user = new SystemUser();
        if ($profile->email) {
            $user->email = $profile->email;
            $user->email_valid = 1;
        }
        if ($profile->phone) {
            $user->phone = $profile->phone;
            $user->phone_valid = 1;
        }
        $user->head_img = $profile->photoURL;
        $user->nickname = $profile->displayName;
        $this->addBinds($user, Data::Gmail);

        if ($user->save()) {
            return $user;
        } else {
            Logger::error('注册账号失败', $user->getErrors());
            throw new BusinessException(__('auth.register_fail','注册账号失败'));
        }
    }

    public function userIdExist(int $userId, int $status = 1): bool
    {
        if ($userId < 1) {
            throw new BusinessException('用户 ID 不能为空');
        }
        return SystemUser::queryBuilder($this->mvc->getDi())
            ->int('id', $userId)
            ->int('status', $status)->exits();
    }

    /**
     * 账号密码登录
     * @param string $account
     * @param string $password
     * @return SystemUser
     * @throws \Exception
     */
    public function loginWithPassword(string $account, string $password)
    {
        if (empty($password)) {
            throw new BusinessException('密码不能为空');
        }
        if ($this->mvc->validate()->isEmail($account)) {
            $condition = ['email' => $account, 'email_valid' => 1];
        } elseif ($this->mvc->validate()->isPhone($account)){
            $condition = ['phone' => $account, 'phone_valid' => 1];
        } else {
            throw new BusinessException('账号格式不正确');
        }
        if ($user = SystemUser::queryBuilder($this->mvc->getDi())
            ->where($condition)->findFirstModel()){
            $this->checkPassword($password,$user);
            $this->activeStatus($user);
            return $user;
        } else {
            throw new BusinessException('账号不存在或密码不正确');
        }
    }
}