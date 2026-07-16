<?php

namespace App\Modules\tao;

use App\Modules\tao\Helper\TaoHtmlHelper;
use App\Modules\tao\sdk\phaxui\HtmlAssets;
use App\Modules\tao\sdk\phaxui\Layui\Layui;
use App\Modules\tao\sdk\phaxui\Layui\LayuiForm;
use App\Modules\tao\sdk\phaxui\Layui\LayuiHtml;
use App\Modules\tao\sdk\phaxui\Layui\LayuiSearch;
use App\Modules\tao\utils\ResponseUtil;
use Phax\Db\QueryBuilder;
use Phax\Foundation\AppService;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\BusinessException;

class BaseResponseController extends Controller
{
    /**
     * 禁用主布局 views/index.phtml
     */
    protected bool $disabledMainLayout = false;

    /**
     * @var bool 小程序之类的请求
     * https://developers.weixin.qq.com/miniprogram/dev/framework/ability/network.html
     * 只要成功接收到服务器返回，无论 statusCode 是多少，都会进入 success 回调。开发者根据业务逻辑对返回值进行判断。
     */
    protected bool $jsonBodyRequest = false;
    /**
     * @var array 前端请求数据
     */
    public array $requestData = [];

    /**
     * 为 action 设置移动版模板
     * @var array 示例 `['index'=>'index/index_mobile.phtml']`
     */
    protected array $mobileTemplate = [];

    /**
     * @var string 設置 HTML 頁面名称
     */
    protected string $htmlTitle = '';
    /**
     * @var array|string 面包屑导航
     */
    protected array|string $breadcrumb = '';
    /**
     * @var bool 是否有控制台（后台管理控制器）
     */
    protected bool $console = false;

    public function initialize(): void
    {
        // 小程序/API 请求判断：URL 参数 data=jsonbody 或 Content-Type 为 application/json 的 请求
        $this->jsonBodyRequest = $this->request->getQuery('data', 'string') === 'jsonbody';
        if ($this->jsonBodyRequest || str_contains($this->request->getContentType() ?? '', 'application/json')) {
            $this->jsonResponse = true;
            $this->requestData = $this->request->getJsonRawBody(true) ?: [];
        } elseif ($this->request->isPost()) {
            $this->requestData = $this->request->getPost() ?: [];
        } elseif ($this->request->isGet()) {
            $this->requestData = $this->request->getQuery() ?: [];
        } elseif ($this->request->isPut()) {
            $this->requestData = $this->request->getPut() ?: [];
        }
        if (!$this->isApiRequest()) {
            AppService::getDi()->setShared('html', function () {
                return new TaoHtmlHelper();
            });
        }
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
            AppService::response()->setStatusCode($data->getStatusCode())
                ->setContent($responseBody)
                ->send();
            return true;
        }
        if ($this->jsonResponse) {
            if ($this->isJsonResponseFormat($data)) {
                $this->doResponse(true, $data);
            } else {
                $this->doResponse(true, $this->success('', $data));
            }
            return true;
        }
        // 为页面准备数据：然后跳转到方法：beforeViewResponse
        return parent::executeRouteResponseData($data);
    }

    /**
     * 是否为首页查询
     * @return bool
     */
    protected function isFirstPage(): bool
    {
        return $this->request->getQuery('page', 'int', 1) == 1;
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
        AppService::route()->theme = $this->theme;
        if ($this->disabledMainLayout) {
            $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        }
        // 如果数据是一个 api 格式，需要进行二次处理
        if ($this->isJsonResponseFormat($data)) {
            if (!empty($data['msg'])) {
                if (in_array($data['code'], [0, 200])) {
                    $this->flash->success($data['msg']);
                } else {
                    $this->flash->error($data['msg']);
                }
            }
            $data = $data['data'] ?? [];
        }
        $action = $this->router->getActionName();
        if ('add' == $action) {
            // TODO 除非 add 模板存在，否则使用 edit 模板
        }
        // 如果定义了移动版模板
        if (isset($this->mobileTemplate[$action]) && AppService::isMobile()) {
            AppService::route()->setPickView($this->mobileTemplate[$action]);
        }
        return parent::beforeViewResponse($data);
    }

    protected function beforeDoViewResponse(): void
    {
        AppService::getLazyService('tao.layui', function () {
            return new Layui();
        });
        $obj = $this;
        AppService::getLazyService('tao.layuiHtml', function () use ($obj) {
            $html = new LayuiHtml();
            if ($obj->breadcrumb) {
                $html->addBreadcrumbItem($obj->breadcrumb);
            }
            return $html;
        });
        AppService::getLazyService('tao.layuiForm', function () {
            $layui = AppService::getShared('tao.layui');
            return new LayuiForm($layui);
        });
        AppService::getLazyService('tao.layuiSearch', function () {
            return new LayuiSearch();
        });
        $this->view->setVars([
            'console' => $this->console,
        ]);
        AppService::html()->setHtmlTitle($this->htmlTitle);
    }

    /**
     * 返回 json 格式的错误信息
     * @param array|string $msg
     * @param int $code
     * @param mixed $data 错误数据
     * @return array
     */
    public function error(array|string $msg, int $code = 500, mixed $data = null): array
    {
        if ($data == null && IS_DEBUG && isset($_GET['test'])) { // 开启测试时显示调用栈
            $data = debug_backtrace();
        }
        return [
            'code' => $code,
            'msg' => is_array($msg) ? join('<br>', $msg) : $msg,
            'data' => $data,
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
     * @param array $merge 其它数据，会合并到返回数据中
     * @return array
     */
    public function successPagination(int $count, mixed $rows, array $merge = []): array
    {

        if ($rows instanceof \Phax\Mvc\Model || $rows instanceof \Phalcon\Mvc\Model\Resultset\Simple) {
            $rows = $rows->toArray();
        }
        return [
            'code' => 0,
            'msg' => '',
            'data' => array_merge(['count' => $count, 'rows' => $rows], $merge)
        ];
    }

    /**
     * 渲染指定模板
     * @param string $tpl
     * @param $data
     * @throws BlankException
     */
    public function simpleView(string $tpl, $data): never
    {
        if (!is_array($data)) {
            throw new BusinessException('simple view data must be array');
        } elseif (isset($data['vv'])) {
            throw new BusinessException('simple view data must not have vv');
        }
        $this->beforeDoViewResponse();
        $data['vv'] = AppService::html();
        echo ResponseUtil::simpleView($tpl, $data);
        exit;
    }

    /**
     * 跳转到提示页面（带倒计时 + 自动跳转）
     * @param string $msg 提示信息
     * @param string $url 跳转目标 URL（空则返回上一页）
     * @param int $icon 图标：1=成功, 2=错误, 3=询问, 6=笑脸（默认 2）
     * @param int $wait 倒计时秒数（默认 5）
     * @param string $title 弹窗标题（默认根据 icon 自动选择）
     * @return never
     * @throws BlankException
     */
    public function redirect(string $msg = '', string $url = '', int $icon = 2, int $wait = 5, string $title = ''): never
    {
        // 如果需要直接跳转，使用 throw new \Phax\Support\Exception\LocationException 即可
        $this->simpleView(self::getTaoViewDir('redirect.phtml'), [
            'msg' => $msg,
            'url' => $url,
            'icon' => $icon,
            'wait' => $wait,
            'title' => $title,
        ]);
    }

    public function beforeExecuteRoute($dispatcher)
    {
        if ($this->jsonBodyRequest) { // 小程序之类的，不要将错误显示出来
            // https://developers.weixin.qq.com/miniprogram/dev/framework/ability/network.html
            // 只要成功接收到服务器返回，无论 statusCode 是多少，都会进入 success 回调。
            // 开发者根据业务逻辑对返回值进行判断，所以需要将全部错误进行包装

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