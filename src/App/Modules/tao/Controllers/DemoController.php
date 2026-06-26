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

            // PRG: 将结果存入 flash session，然后重定向到 GET 页面
//            if ($data['code'] == 0){
//                $this->flashSession->success($data['msg']);
//            } else {
//                $this->flashSession->error($data['msg']);
//            }
//            $this->response->redirect('/m/tao/demo/form');
//            $this->view->disable();
//            return [];
        }
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