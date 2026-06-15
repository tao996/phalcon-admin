<?php

namespace App\Modules\tao;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\sdk\phaxui\HtmlAssets;
use Phax\Db\QueryBuilder;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BlankException;

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

    protected bool $jsonBodyRequest = false;
    /**
     * @var array 前端请求数据
     */
    public array $requestData = [];

    public function initialize(): void
    {
        // 小程序/API 请求判断：URL 参数 data=jsonbody 或 Content-Type 为 application/json 的 POST 请求
        $isJsonBody = $this->request->getQuery('data', 'string') === 'jsonbody'
            || ($this->request->isPost() && str_contains($this->request->getContentType() ?? '', 'application/json'));

        if ($isJsonBody) {
            $this->jsonBodyRequest = true;
            $this->jsonResponse = true;
            $this->requestData = $this->request->getJsonRawBody(true) ?: [];
        }
        $this->vv = new MyMvcHelper($this->di);
        parent::initialize();
    }

    /**
     * 判断数据是否已经是标准响应格式 {code, msg, data}
     */
    protected function isJsonResponseFormat(mixed $data): bool
    {
        return is_array($data) && isset($data['code']) && isset($data['msg']);
    }

    protected function executeRouteResponseData(mixed $data): bool
    {
        if ($data instanceof \Psr\Http\Message\ResponseInterface) {
            $responseBody = $data->getBody()->getContents();
            $this->vv->response()->setStatusCode($data->getStatusCode())
                ->setContent($responseBody)
                ->send();
            return true;
        }
        if ($this->jsonBodyRequest) { // 小程序响应
            if ($this->isJsonResponseFormat($data)) {
                $this->doResponse(true, $data);
            } else {
                $this->doResponse(true, $this->success('', $data));
            }
            return true;
        }
        return parent::executeRouteResponseData($data);
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
     * include BaseResponseController::getBaseViewDir('index.phtml')
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
        if ($this->isJsonResponseFormat($data)) {
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
        // 渲染视图所需要用到的常量： PATH_TAO 的相关路径
        require_once __DIR__ . '/Common/common.php';
        $this->vv->route()->theme = $this->theme;
        if ($this->disabledMainLayout) {
            $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        }
        return parent::beforeViewResponse($data);
    }

    /**
     * 返回 json 格式的错误信息
     * @param array|string $msg
     * @param int $code
     * @return array
     */
    public function error(array|string $msg, int $code = 500): array
    {
        $this->jsonResponse = true;
        return [
            'code' => $code,
            'msg' => is_array($msg) ? join('<br>', $msg) : $msg,
            'data' => [],
        ];
    }

    /**
     * 返回 json 格式的成功信息
     * @param string $message
     * @param mixed|null $data
     * @return array
     */
    public function success(string $message, mixed $data = null): array
    {
        $this->jsonResponse = true;

        if ($data instanceof \Phax\Mvc\Model || $data instanceof \Phalcon\Mvc\Model\Resultset\Simple) {
            $data = $data->toArray();
        }
        return [
            'code' => 0,
            'msg' => $message,
            'data' => $data
        ];
    }

      public function successData($data): array
    {
        $this->jsonResponse = true;

        if ($data instanceof \Phax\Mvc\Model || $data instanceof \Phalcon\Mvc\Model\Resultset\Simple) {
            $data = $data->toArray();
        }
        return [
            'code' => 0,
            'msg' => '',
            'data' => $data
        ];
    }




    /**
     * 通常用在显示列表数据
     * @param int $count
     * @param mixed $rows
     * @return array
     */
    public function successPagination(int $count, mixed $rows): array
    {
        $this->jsonResponse = true;

        if ($rows instanceof \Phax\Mvc\Model || $rows instanceof  \Phalcon\Mvc\Model\Resultset\Simple){
            $rows = $rows->toArray();
        }
        return [
            'code' => 0,
            'msg' => '',
            'data' => ['count' => $count, 'rows' => $rows]
        ];
    }

    /**
     * 渲染指定模板
     * @param string $tpl
     * @param $data
     * @return mixed
     * @throws BlankException
     */
    public function simpleView(string $tpl, $data): mixed
    {
        if (!is_array($data)) {
            throw new \Exception('simple view data must be array');
        } elseif (isset($data['vv'])) {
            throw new \Exception('simple view data must not have vv');
        }
        $data['vv'] = $this->vv;
        echo $this->vv->responseHelper()->simpleView($tpl, $data);
        throw new BlankException();
    }

    public function beforeExecuteRoute($dispatcher)
    {
        if ($this->jsonBodyRequest) { // 小程序之类的，不要将错误显示出来

            // 1. 获取当前准备执行的 Action 名称（例如：weatherAction）
            $actionName = $dispatcher->getActiveMethod();

            // 2. 检查方法是否存在（安全守卫）
            if (method_exists($this, $actionName)) {
                try {
                    // 3. 手动调用 Action，捕获返回值
                    $result = $this->$actionName();

                    // 4. 处理并发送响应（若尚未发送）
                    if (!$this->response->isSent()) {
                        $this->executeRouteResponseData($result);
                    }

                    // 5. 返回 false 告诉 Phalcon：已手工执行，不需要再分发
                    return false;

                } catch (BlankException $e) {
                    // BlankException 表示响应已通过 json() 发送，无需再处理
                    return false;
                } catch (\Throwable $e) {
                    // 捕获 Action 抛出的异常，以 JSON 格式返回错误信息
                    if (!$this->response->isSent()) {
                        $this->doResponse(true, $this->error($e->getMessage()));
                    }
                    return false;
                }
            }
        }
    }
}