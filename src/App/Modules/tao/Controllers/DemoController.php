<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use Phax\Utils\MyData;

/**
 * 测试响应
 * 1. application/json 接口请求，响应接口数据
 * 2. POST 页面 POST 请求，返回提交页面，并且追加上错误信息
 * 3. 普通 GET 请求，显示页面
 */
class DemoController extends BaseController
{
    public array $enableActions = ['index', 'form'];

    public function indexAction(): array
    {
        if ($this->isApiRequest()) {
            return $this->_doData();
        }
        return [];
    }

    public function formAction(): array
    {
        $data = [];
        if ($this->request->isPost()) {
            $data = $this->_doData();
        }
        // GET 请求时生成 CSRF 令牌传给视图
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('csrfKey', $this->security->getTokenKey());
        return $data;
    }

    private function _doData(): array
    {
        // CSRF 验证
        if (!$this->security->checkToken()) {
            return $this->error('表单已过期，请刷新后重试');
        }
        $success = MyData::getInt($this->requestData, 'num') % 2 == 0;
        if ($success) {
            return $this->success('成功啦');
        }
        return $this->error('错误啦', data: ['num' => MyData::getInt($this->requestData, 'num')]);
    }
}