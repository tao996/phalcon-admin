<?php

namespace App\Modules\tao;


use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\LoginAuthHelper;
use App\Modules\tao\Models\SystemUser;
use Phax\Support\Exception\BlankException;
use Phax\Support\Router;

/**
 * 控制器访问权限判断
 */
class BaseRbacController extends BaseResponseController
{
    /**
     * 默认为空，则为 cookies 授权
     * @var LoginAuthAdapter|string|null
     */
    private LoginAuthAdapter|string|null $loginAdapter = null;

    /**
     * @var string 默认的 action 名称
     */
    public string $action = 'index';
    /**
     * 是否已经检查过登录状态
     * @var bool
     */
    private bool $hasCheckLogin = false;
    /**
     * 超级用户能够访问的节点，使用 * 表示全部节点; <br>
     * 优先级：superAdminActions < openActions
     * @var array|string
     */
    protected array|string $superAdminActions = [];

    /**
     * 登录用户能够访问的节点；全部节点则使用 * 表示; <br>
     * 优先级：userActions < superAdminActions < openActions
     * @var array|string
     */
    protected array|string $userActions = [];
    /**
     * 公开的节点，使用 * 表示全部节点; <br>
     * 优先级：openActions > superAdminActions > userActions > otherActionRoles
     * @var array|string ['a1','a2'] 或者 a1,a2
     */
    protected array|string $openActions = [];

    /**
     * 其它节点所能够访问的角色
     * @var array
     */
    protected array $otherActionRoles = [];

    /**
     * @var array （白名单）当前控制器允许访问的 action，如果设置，则只有 enableActions 中的 action 才允许访问
     */
    public array $enableActions = [];
    /**
     * @var array (黑名单)当前控制器取消的操作（不是每个控制器都有 add/edit/delete）
     */
    public array $disableActions = [];

    /**
     * 是否禁用 add/edit/modify/delete 操作
     * @var bool
     */
    public bool $disableUpdateActions = false;

    public array $updateActions = ['add', 'edit', 'modify', 'delete'];

    public function setLoginAdapter(LoginAuthAdapter|string $authAdapter, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->loginAdapter = $authAdapter;
        } elseif (empty($this->loginAdapter)) {
            $this->loginAdapter = $authAdapter;
        }
    }

    /**
     * @throws \Exception
     */
    protected function isLogin(): bool
    {
        try {
            return $this->tryGetLoginAuth()->isLogin();
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * 尝试获取登录用户的信息
     * @return LoginAuthHelper
     * @throws \Exception
     */
    public function tryGetLoginAuth(): LoginAuthHelper
    {
        if (!$this->hasCheckLogin) {
            $this->hasCheckLogin = true;
            $this->vv->loginAuthHelper()
                ->setAuthAdapter($this->loginAdapter);
            $this->vv->loginAuthHelper()->login();
        }
        return $this->vv->loginAuthHelper();
    }

    /**
     * @throws \Exception
     */
    public function loginUser(): SystemUser
    {
        if (!$this->hasCheckLogin) {
            $this->tryGetLoginAuth();
        }
        return $this->vv->user();
    }

    public function initialize(): void
    {
        parent::initialize();
        if ($action = $this->vv->router()->getActionName()) {
            $this->action = Router::formatNodeName($action);
        } else {
            if ($this->vv->di->has('route')) {
                $this->action = $this->vv->route()->getAction();
            }
        }
    }

    protected function isSuperAdminAction(): bool
    {
        return '*' == $this->superAdminActions
            || in_array(
                $this->action,
                is_array($this->superAdminActions) ? $this->superAdminActions : explode(',', $this->superAdminActions)
            );
    }

    protected function isUserAction(): bool
    {
        return '*' == $this->userActions
            || in_array(
                $this->action,
                is_array($this->userActions) ? $this->userActions : explode(',', $this->userActions)
            );
    }

    protected function accessDenyResponse($msg = '')
    {
        if ('' == $msg) {
            $msg = '没有访问的权限';
        }
        if ($this->isApiRequest()) {
            $this->json($this->error($msg, 403));
        } else {
            echo $this->vv->responseHelper()->simpleView(self::getTaoViewDir('redirect.phtml'), [
                'msg' => $msg,
                'url' => 'close',
            ]);
        }
        throw new BlankException();
    }

    public bool $rbacInitialize = true;

    /**
     * @throws \Exception
     */
    protected function rbacInitialize(): void
    {
        if (!$this->rbacInitialize) {
            return;
        }
        if ($this->disableActions && in_array($this->action, $this->disableActions)) {
            throw new \Exception('in not allow disableActions');
        }
        if ($this->enableActions && !in_array($this->action, $this->enableActions)) {
            throw new \Exception('not in allow enableActions');
        }
        // 开放接口
        if ($this->openActions == '*') {
            if (!$this->disableUpdateActions) {
                $this->disableUpdateActions = true;
            }
            if ($this->disableUpdateActions && in_array($this->action, $this->updateActions)) {
                throw new \Exception('not allow disableUpdateActions in open access');
            }
            return;
        } elseif (in_array(
            $this->action,
            is_array($this->openActions) ? $this->openActions : explode(',', $this->openActions)
        )) {
            return;
        }

        if ($this->disableUpdateActions && in_array($this->action, $this->updateActions)) {
            throw new \Exception('not allow disableUpdateActions');
        }

        // 非公共节点都需要登录
        if (!$this->isLogin()) {
            if ($this->isApiRequest()) {
                $this->json($this->error('您还没有登录', 401));
            } else {
                echo $this->vv->responseHelper()->simpleView(self::getTaoViewDir('redirect.phtml'), [
                    'msg' => '您还没有登录，前往登录?',
                    'url' => $this->vv->urlWith('/m/tao/auth/index'),
                ]);
            }
            throw new BlankException();
        }

        if ($this->vv->loginUserHelper()->isSuperAdmin()) {
            return;
        }

        // 超级管理员节点
        if ($this->isSuperAdminAction()) {
            if (!$this->vv->loginUserHelper()->isSuperAdmin()) {
                $this->accessDenyResponse('非超级管理员，无权访问');
            }
            return;
        }

        if ($this->isUserAction()) {
            return;
        }

        // 节点检查
        $user = $this->vv->loginUserHelper();

        if ($this->otherActionRoles) {
            $roleIds = $this->vv->roleService()->getIds($this->otherActionRoles);
            if ($user->inRoles($roleIds)) {
                return;
            } else {
                $this->accessDenyResponse('没有访问的权限');
            }
        }
        if (!$user->access($this->vv->route()->getNode())) {
            $this->accessDenyResponse('没有访问节点的权限');
        }
    }

    /**
     * @throws \Exception
     */
    protected function beforeViewResponse(mixed $data)
    {
        $this->tryGetLoginAuth();
        return parent::beforeViewResponse($data);
    }


    public function mustPostMethod(): void
    {
        if (!$this->request->isPost()) {
            throw new \Exception('only support POST method');
        }
    }

    /**
     *
     * 限制请求方法
     * @param array $methods 默认为 [post]，使用小写
     * @return void
     * @throws \Exception
     */
    public function mustRequestMethods(array $methods = ['post']): void
    {
        if (!in_array(strtolower($this->request->getMethod()), $methods)) {
            throw new \Exception('only support request method');
        }
    }
}