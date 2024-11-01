<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemUser;
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
     * @throws \Exception
     */
    public function mustAccountString(string $account): void
    {
        if (empty($account)) {
            throw new \Exception('账号不能为空');
        }
        if ($this->mvc->validate()->isEmail($account) || $this->mvc->validate()->isPhone($account)) {
            return;
        }
        throw new \Exception('不是一个合法的账号');
    }

    /**
     * 账号是否可以注册
     * @param string $account
     * @return void
     * @throws \Exception
     */
    public function mustCanRegister(string $account): void
    {
        $isEmail = $this->mvc->smsCodeService()->mustReceiver($account);

        if ($row = SystemUser::queryBuilder()
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirstArray()
        ) {
            if ($isEmail) {
                if ($row['email_valid'] == 1) {
                    throw new \Exception('邮箱已经被占用');
                }
            } else {
                if ($row['phone_valid'] == 1) {
                    throw new \Exception('手机号已经被占用');
                }
            }
        }
    }

    /**
     * 账号是否可以登录
     * @param mixed $account
     * @return void
     * @throws \Exception
     */
    public function mustCanLogin(mixed $account): void
    {
        $isEmail = $this->mvc->smsCodeService()->mustReceiver($account);

        if ($row = SystemUser::queryBuilder()
            ->where($isEmail ? 'email' : 'phone', $account)
            ->columns('id,email_valid,phone_valid,status')
            ->findFirstArray()
        ) {
            if ($row[$isEmail ? 'email_valid' : 'phone_valid'] != 1) {
                throw new \Exception('账号不存在或未激活');
            }
        } else {
            throw new \Exception('账号不存在');
        }
    }

    /**
     * 密码是否合法
     * @param string $password
     * @return void
     * @throws \Exception
     */
    public function mustPassword(string $password): void
    {
        if (strlen($password) < 6) {
            throw new \Exception('密码最少为6位');
        }
    }

    /**
     * @param array $condition
     * @return SystemUser
     * @throws \Exception
     */
    public function mustGetUser(array $condition): SystemUser
    {
        $qb = SystemUser::queryBuilder()
            ->where($condition);

        if ($user = $qb->findFirstModel()) {
            return $user;
        }
        throw new \Exception('没有找到符合条件的用户');
    }

    /**
     * 注册账号
     * @param SystemUser $user
     * @return void
     * @throws \Exception
     */
    public function create(SystemUser $user): void
    {
        if (!$user->save()) {
            throw new \Exception('注册账号失败');
        }
    }

    /**
     * 批量查询用户信息
     * @param array $userIds
     * @param array $columns
     * @return array
     * @throws \Exception
     */
    public function findColumns(array $userIds, array $columns = ['id', 'nickname']): array
    {
        if (empty($userIds)) {
            return [];
        }
        return SystemUser::queryBuilder()
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
     * @throws \Exception
     */
    public function newPassword(string $password, SystemUser $user): void
    {
        $this->mustPassword($password);
        $user->password = $this->mvc->security()->hash($password);
    }

    /**
     * 检查密码是否正确
     * @throws \Exception
     */
    public function checkPassword(string $password, SystemUser $user, bool $must = true): void
    {
        if (empty($password) && $must) {
            throw new \Exception('密码不能为空');
        }
        if (!$this->mvc->security()->checkHash($password, $user->password)) {
            throw new \Exception('密码错误');
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
     * @throws \Exception
     */
    public function mustUniquePhone(string $phone, SystemUser $user, bool $assign = false): void
    {
        if ($user->phone == $phone) {
            return;
        }
        if (empty($phone)) {
            throw new \Exception('待检测的手机号不能为空');
        }
        $this->mvc->validate()->mustPhone($phone);
        if ($user->getQueryBuilder()
            ->string('phone', $phone)
            ->notEqual("id", $user->id)
            ->exits()) {
            throw new \Exception('手机号重复');
        }
        if ($assign) {
            $user->phone = $phone;
        }
    }

    /**
     * 检查待注册的电子邮箱是否合法
     * @throws \Exception
     */
    public function mustUniqueEmail(string $email, SystemUser $user, bool $assign = false): void
    {
        if ($user->email == $email) {
            return;
        }
        if ($user->getQueryBuilder()->string('email', $email)
            ->notEqual('id', $user->id)->exits()) {
            throw new \Exception('邮箱地址重复');
        }
        if ($assign) {
            $user->email = $email;
        }
    }

    /**
     * 能否修改账号
     * @param string $kind 类型 account|phone|email
     * @param string $account 待检查的账号
     * @throws \Exception
     */
    public function mustAllowChangeAccount(string $kind, string $account, SystemUser $user): void
    {
        if (empty($account)) {
            throw new \Exception('账号不能为空');
        }
        switch ($kind) {
            case 'phone':
                if (!$this->enableChangePhoneAt($user->phone_at)) {
                    throw new \Exception('每 30天 才能修改一次电话号码');
                }
                $this->mustUniquePhone($account, $user);
                break;
            case 'email':
                if (!$this->enableChangeEmailAt($user->email_at)) {
                    throw new \Exception('每 30天 才能修改一次电子邮箱');
                }
                $this->mustUniqueEmail($account, $user);
                break;
            default:
                throw new \Exception('不支持修改的账号类型');
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
            $rows = SystemRole::queryBuilder()->in('id', $ids)
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
     * @throws \Exception
     */
    public function addBinds(SystemUser $user, string $bind): void
    {
        if (!in_array($bind, array_keys(Data::MapBinds))) {
            throw new \Exception('不支持绑定类型:' . $bind);
        }
        if (empty($user->binds)) {
            $user->binds = json_encode([$bind]);
        } else {
            $binds = json_decode($user->binds, true);
            if (!in_array($bind, $binds)) {
                $binds[] = $bind;
            }
            $user->binds = json_encode($binds);
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
     * @throws \Exception
     */
    public function activeStatus(SystemUser $user): void
    {
        if ($user->status == SystemUser::STATUS_DELETE) {
            throw new \Exception('当前账号已经被禁止登录');
        }
    }

    public function addUserProfile(\Hybridauth\User\Profile $profile): SystemUser
    {
        // 准备注册账号
        $qb = SystemUser::queryBuilder();
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
            throw new \Exception('register account failed');
        }
    }

    public function userIdExist(int $userId, int $status = 1):bool
    {
        if ($userId < 1) {
            throw new \Exception('用户 ID 不能为空');
        }
        return SystemUser::queryBuilder()->int('id', $userId)
            ->int('status', $status)->exits();
    }
}