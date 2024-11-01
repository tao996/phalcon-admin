<?php

namespace App\Modules\tao;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\sdk\phaxui\HtmlAssets;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Controller;

/**
 * 定义各种响应格式
 * @property \App\Modules\tao\Helper\MyMvcHelper $vv
 */
class BaseResponseController extends Controller
{
    /**
     * 禁用主布局 views/index.phtml
     */
    protected bool $disabledMainLayout = false;

    public function initialize(): void
    {
        $this->vv = new MyMvcHelper($this->di);
        parent::initialize();
    }

    /**
     * 处理分页数据
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder
     */
    protected function pagination(QueryBuilder $queryBuilder): QueryBuilder
    {
        $page = $this->request->get('page', 'int', 1);
        $limit = $this->request->get('limit', 'int', 15);
        $queryBuilder->pagination($page - 1, $limit);
        return $queryBuilder;
    }

    /**
     * 为了解决 layui table.reload 会保存上次搜索条件的问题 <br>
     * 当搜索 reset 时，会追加 reset=1 此时会忽略搜索条件
     * @return bool
     */
    protected function isResetSearch(): bool
    {
        return $this->request->getQuery('reset', 0) == 1;
    }

    /**
     * 当前默认主题，如果设置，则使用 views/$theme 下的模板
     */
    public string $theme = 'layui';

    /**
     * 模块/应用目录，通常用于定位视图模板 views 的位置
     * @var string
     */
    public string $baseDIR = __DIR__;

    /**
     * 指向模板文件，通常用在 index.phtml 中，用来包含通用的模板 <br>
     * include_once BaseResponseController::getBaseViewDir('index.phtml')
     * @param string $tpl
     * @return string
     */
    public function getTaoViewDir(string $tpl): string
    {
        if ($this->theme) {
            return $this->baseDIR . '/views/' . $this->theme . '/' . $tpl;
        } else {
            return $this->baseDIR . '/views/' . $tpl;
        }
    }

    public function jsonResponseData(mixed $data): array
    {
        if (is_array($data) && isset($data['code']) && isset($data['msg'])) {
// $data['data'] 可能为 null
            return $data;
        }
        return $this->success('', $data);
    }

    protected function beforeJsonResponse(mixed $data)
    {
        return $this->jsonResponseData($data);
    }

    protected function beforeViewResponse(mixed $data)
    {
        HtmlAssets::initWithCdn();
        // PATH_TAO 的相关路径
        require_once __DIR__ . '/Common/common.php';
        $this->vv->route()->theme = $this->theme;
        if ($this->disabledMainLayout) {
            $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        }
        return parent::beforeViewResponse($data);
    }

    public function error(array|string $msg, int $code = 500): array
    {
        $this->jsonResponse = true;
        return [
            'code' => $code,
            'msg' => is_array($msg) ? join('<br>', $msg) : $msg,
            'data' => [],
        ];
    }

    public function success(string $message, mixed $data = null): array
    {
        $this->jsonResponse = true;
        return [
            'code' => 0,
            'msg' => $message,
            'data' => $data
        ];
    }


    /**
     * 通常用在显示列表数据
     * @param int $count
     * @param array $rows
     * @return array
     */
    public function successPagination(int $count, array $rows): array
    {
        $this->jsonResponse = true;
        return [
            'code' => 0,
            'msg' => '',
            'data' => ['count' => $count, 'rows' => $rows]
        ];
    }

}