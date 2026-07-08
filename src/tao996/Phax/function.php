<?php


if (!function_exists('pr')) {
    /**
     * print only value (if run in cli, the print in console)
     * @param $var
     * @return void
     * @throws Exception
     */
    function pr($var): void
    {
        echo IS_TASK ? '|<--- ' . PHP_EOL : '<pre>';
        foreach (func_get_args() as $arg) {
            print_r($arg);
            echo IS_TASK ? PHP_EOL : '<br/>';
        }
        echo IS_TASK ? '|---> ' . PHP_EOL : '</pre>';
        if (func_get_args()[func_num_args() - 1] !== false) {
            exit();
        }
    }
}

if (!function_exists('ddd')) {
    /**
     * print var type and value, and stop
     */
    function ddd($var): void
    {
        if (class_exists('\Phalcon\Support\Debug\Dump')) {
            $args = func_get_args();
            array_map(function ($x) {
                $string = (new \Phalcon\Support\Debug\Dump())->variable($x);
                echo IS_PHP_FPM ? $string : strip_tags($string) . PHP_EOL;
            }, $args);
            exit();
        } else {
            pr(func_get_args());
        }
    }
}

if (!function_exists('prettyError')) {
    /**
     * 将 PHP Error / Warning / Notice 转为 ErrorException，
     * 使其能被 try/catch 捕获并进入统一错误处理流程。
     * 不再直接输出 HTML，避免泄露信息到页面。
     */
    function prettyError($errno, $errstr, $errfile, $errline): bool
    {
        // 错误级别被 error_reporting 屏蔽时忽略
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * 输出异常详情（用于 CLI 或调试）
     */
    function prettyException(\Throwable $e): void
    {
        echo sprintf(
            "%s: %s\n  %s(%d)\n  %s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            str_replace("\n", "\n  ", $e->getTraceAsString())
        );
    }
}

if (!function_exists('__')) {
    /*
 // 1. 使用冒号 :name 规范
echo __('sayHello', '你好 :name，欢迎来到:company！', ['name' => 'bibi', 'company' => '义和泥头车']);
// 输出: 你好 bibi，欢迎来到义和泥头车！

// 2. 兼容你提出来的 @name 规范
echo __('sayHello', '你好 @name', ['name' => 'bibi']);
// 输出: 你好 bibi

// 3. 甚至兼容大括号 {name} 规范
echo __('sayHello', '你好 {name}', ['name' => 'bibi']);
// 输出: 你好 bibi

namespace App\Modules\yihe\Controllers;

class ReportController extends BaseController {
    public function indexAction() {
        // 🚀 传入 __NAMESPACE__，函数会自动去 yihe 模块的语言包里找 'welcome_msg'
        // 如果语言包文件存在且有定义，就用语言包的；如果没有，就兜底输出默认中文
        echo __('welcome_msg', '当前工地：:siteName', ['siteName' => '蛇口1号地'], __NAMESPACE__);
    }
}

// 当未来你们要把系统卖给香港或海外客户，需要支持繁体或英文时，直接在这个文件改文字，代码完全不用动：
// App/Modules/yihe/resources/lang/zh_CN.php
return [
    'welcome_msg' => '目前所在工地為：:siteName (繁體模版)',
];
     */
    /**
     * 全局国际化翻译函数
     * @param string $key 语言包配置键名
     * @param string $default 默认中文翻译文本（带占位符）
     * @param array $params 需要替换的键值对参数
     * @param string $namespace 传入 __NAMESPACE__ 或 get_class($this) 动态识别模块
     * @return string
     */
    function __(string $key, string $default, array $params = [], string $namespace = ''): string
    {
        return \Phax\Support\I18nService::translate($key, $default, $params, $namespace);
    }

}
if (!function_exists('array_merge_deep')) {
    /**
     * 深度合并两个数组（同名标量键覆盖，递归合并数组）
     */
    function array_merge_deep(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = array_merge_deep($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}