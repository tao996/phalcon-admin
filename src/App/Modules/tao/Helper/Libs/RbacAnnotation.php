<?php

namespace App\Modules\tao\Helper\Libs;

use App\Modules\tao\Models\SystemNode;
use Phax\Support\Router;
use Phax\Utils\AnnotationDocCommentParse as Annotation;
use Phax\Utils\ServicesJSON;

/**
 * 分析模 RBAC 注解信息, 没有使用 rbac 标记的操作器或者操作不会出现在结果中 <pre>
 * 在模块/控制器/控制中使用 js JSON 格式来标注 rbac ({title:'名称’,close:1})
 * 使用时注意：
 *  1。如果 close (使用 isset('close') 判断 )写在控制器中，则表示整个控制器下的 Action 都默认不开启授权
 *  如果 close 写在 Action 上，则表示当前 Action 默认不开启授权
 *  2。没有标记的 Controller/Action 默认只有超级管理员能够访问
 * </pre>
 * @link https://docs.phalcon.io/5.0/en/annotations
 */
class RbacAnnotation
{

    /**
     * 获取 RBAC 节点信息
     * @param array $baseInfo 来自 moduleBaseInfo 或 projectBaseInfo
     * @return array
     * @throws \ReflectionException
     */
    public static function getNodes(array $baseInfo): array
    {
        $rows = [];
        $kind = $baseInfo['module'] ? SystemNode::KIND_MODULE : SystemNode::KIND_PROJECT;
        $rows[] = [
            'kind' => $kind,
            'module' => $baseInfo['name'],
            'node' => $baseInfo['name'],
            'title' => $baseInfo['title'] ?? $baseInfo['name'],
            'type' => SystemNode::TYPE_MODULE,
            'is_auth' => self::nodeIsAuth($baseInfo)
        ];
        // 直接控制器
        $controllers = $baseInfo['structure']['controllers'];
        foreach ($controllers as $ctrlPhpFile) {
            $controllerClassname = $baseInfo['namespace'] . '\Controllers\\' . str_replace('.php', '', $ctrlPhpFile);
            if ($ctrlRbacInfo = self::getControllerInfo($controllerClassname)) {

                $ctrlName = Router::formatNodeName($ctrlPhpFile);
                $ctrlNode = $baseInfo['name'] . '/' . $ctrlName; // demo/index
                $ctrlAuth = self::nodeIsAuth($ctrlRbacInfo);

                $rows[] = [
                    'kind' => $kind,
                    'module' => $baseInfo['name'],
                    'node' => $ctrlNode,
                    'title' => $ctrlRbacInfo['title'] ?? $ctrlName,
                    'type' => SystemNode::TYPE_CONTROLLER,
                    'is_auth' => $ctrlAuth,
                ];


                foreach ($ctrlRbacInfo['actions'] as $action => $actionRbacInfo) {
                    $rows[] = [
                        'kind' => $kind,
                        'module' => $baseInfo['name'],
                        'node' => $ctrlNode . '/' . $action, // demo/index/index
                        'title' => $actionRbacInfo['title'] ?? $action,
                        'type' => SystemNode::TYPE_ACTION,
                        'is_auth' => isset($actionRbacInfo['close']) ? self::nodeIsAuth($actionRbacInfo) : $ctrlAuth,
                    ];
                }
            }

        }
        // 子目录信息
        $subDirControllers = $baseInfo['structure']['subDirs'];
        foreach ($subDirControllers as $sub => $ctrlFiles) {
            foreach ($ctrlFiles as $ctrl) {
                $controllerClassname = $baseInfo['namespace'] . '\Controllers\\' . $sub . '\\' . str_replace('.php', '', $ctrl);
                // 控制器信息
                if ($ctrlRbacInfo = self::getControllerInfo($controllerClassname)) {
                    $ctrlName = Router::formatNodeName($ctrl);
                    // 模块/子目录.控制器/操作
                    $ctrlNode = $baseInfo['name'] . '/' . $sub . '.' . $ctrlName; // demo/db.index
                    $ctrlAuth = self::nodeIsAuth($ctrlRbacInfo);

                    $rows[] = [
                        'kind' => $kind,
                        'module' => $baseInfo['name'],
                        'node' => $ctrlNode,
                        'title' => $ctrlRbacInfo['title'] ?? $ctrlName,
                        'type' => SystemNode::TYPE_CONTROLLER,
                        'is_auth' => $ctrlAuth
                    ];

                    foreach ($ctrlRbacInfo['actions'] as $action => $actionRbacInfo) {
                        $rows[] = [
                            'kind' => $kind,
                            'module' => $baseInfo['name'],
                            'node' => $ctrlNode . '/' . $action, // demo/db.index/index
                            'title' => $actionRbacInfo['title'] ?? $action,
                            'type' => SystemNode::TYPE_ACTION,
                            'is_auth' => isset($actionRbacInfo['close']) ? self::nodeIsAuth($actionRbacInfo) : $ctrlAuth,
                        ];
                    }
                }
            }
        }
        // 子模块
        $a0DirControllers = $baseInfo['structure']['a0'];
        if (!empty($a0DirControllers)) {
            foreach ($a0DirControllers as $dir => $a0SubInfo) {
                $node = (empty($baseInfo['name']) ? '/' : $baseInfo['name'] . '.') . $dir . '/'; // demo.db/

                foreach ($a0SubInfo['controllers'] as $ctrlPhpFile) {
                    $controllerClassname = $baseInfo['namespace'] . '\A0\\' . $dir . '\Controllers\\' . str_replace('.php', '', $ctrlPhpFile);

                    if ($ctrlRbacInfo = self::getControllerInfo($controllerClassname)) {
                        $ctrlName = Router::formatNodeName($ctrlPhpFile);
                        $ctrlNode = $node . $ctrlName; // demo.db/test
                        $ctrlAuth = self::nodeIsAuth($ctrlRbacInfo);

                        $rows[] = [
                            'kind' => $kind,
                            'module' => $baseInfo['name'],
                            'node' => $ctrlNode,
                            'title' => $ctrlRbacInfo['title'] ?? $ctrlName,
                            'type' => SystemNode::TYPE_CONTROLLER,
                            'is_auth' => $ctrlAuth,
                        ];

                        foreach ($ctrlRbacInfo['actions'] as $action => $actionRbacInfo) {
                            $rows[] = [
                                'kind' => $kind,
                                'module' => $baseInfo['name'],
                                'node' => $ctrlNode . '/' . $action, // demo.db/test/index
                                'title' => $actionRbacInfo['title'] ?? $action,
                                'type' => SystemNode::TYPE_ACTION,
                                'is_auth' => isset($actionRbacInfo['close']) ? self::nodeIsAuth($actionRbacInfo) : $ctrlAuth
                            ];
                        }
                    }
                }
                // 子模块子目录
                foreach ($a0SubInfo['subDirs'] as $sub => $ctrlFiles) {
                    foreach ($ctrlFiles as $ctrl) {
                        $controllerClassname = $baseInfo['namespace'] . '\A0\\' . $dir . '\Controllers\\' . $sub . '\\' . str_replace('.php', '', $ctrl);
                        if ($ctrlRbacInfo = self::getControllerInfo($controllerClassname)) {
                            $ctrlName = Router::formatNodeName($ctrl);
                            $ctrlNode = $node . $sub . '.' . $ctrlName; // demo.db/user.info
                            $ctrlAuth = self::nodeIsAuth($ctrlRbacInfo);

                            $rows[] = [
                                'kind' => $kind,
                                'module' => $baseInfo['name'],
                                'node' => $ctrlNode,
                                'title' => $ctrlRbacInfo['title'] ?? $ctrlName,
                                'type' => SystemNode::TYPE_CONTROLLER,
                                'is_auth' => $ctrlAuth,
                            ];

                            foreach ($ctrlRbacInfo['actions'] as $action => $actionRbacInfo) {
                                $rows[] = [
                                    'kind' => $kind,
                                    'module' => $baseInfo['name'],
                                    'node' => $ctrlNode . '/' . $action,
                                    'title' => $actionRbacInfo['title'] ?? $action,
                                    'type' => SystemNode::TYPE_ACTION,
                                    'is_auth' => isset($actionRbacInfo['close']) ? self::nodeIsAuth($actionRbacInfo) : $ctrlAuth
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $rows;
    }

    private static function nodeIsAuth(array $rbacInfo): int
    {
        return isset($rbacInfo['close']) ? 0 : 1;
    }

    /**
     * 模块结构化信息
     * @param string $name 模块名称
     * @return array
     * @throws \ReflectionException
     * @throws \Exception
     */
    public static function moduleBaseInfo(string $name): array
    {
        $rows = [
            'name' => $name,
            'namespace' => 'App\Modules\\' . $name,// 多模块标记
            'path' => PATH_APP_MODULES . $name,
            'module' => true,
        ];

        // 模块名称
        if (file_exists($rows['path'] . '/Module.php')) {
            if ($mInfo = self::getClassRbacDoc($rows['namespace'] . '\Module')) {
                $rows['title'] = $mInfo['title'] ?? $rows['name'];
            }
        }

        $rows['structure'] = AppStructure::findInModule($name);
        return $rows;
    }

    /**
     * 项目结构化信息
     * @param string $name 项目名称，如果不填写，则是默认示例项目
     * @throws \Exception
     */
    public static function projectBaseInfo(string $name): array
    {
        $inProjects = !empty($name);
        $rows = [
            'name' => $name,
            'namespace' => $inProjects ? 'App\Projects\\' . $name : 'App\Http',
            'path' => PATH_APP . 'Http' . ($inProjects ? '/Projects/' . $name : ''),
            'module' => false,
        ];
        $rows['structure'] = AppStructure::getControllerFilesInDeep($rows['path']);

        return $rows;
    }


    /**
     * 获取控制器及其操作的 RBAC 信息，如果控制器/操作没有使用 rbac 标记，则不会出现在列表中
     * @param string $classController 控制器的类名
     * @return array|null
     * @throws \ReflectionException
     * @throws \Exception
     */
    public static function getControllerInfo(string $classController): array|null
    {
        $controllerRef = new \ReflectionClass($classController);
        $ctrlRbac = self::getRefClassRbacDoc($controllerRef);
        if (empty($ctrlRbac)) {
            return null;
        }
//        if (empty($ctrlRbac)) {
//            $ctrlRbac['title'] = Router::formatNodeName($controllerRef->getShortName(), false);
//        }
        $enableActions = [];
        $disabledActions = [];

        $hasEnActions = $controllerRef->hasProperty('enableActions');
        $hasDisActions = $controllerRef->hasProperty('disableActions');
        if ($hasEnActions || $hasDisActions) {
            $obj = new $classController;
            if ($hasEnActions) {
                $enableActions = $controllerRef->getProperty('enableActions')->getValue($obj);
            }
            if ($hasDisActions) {
                $disabledActions = $controllerRef->getProperty('disableActions')->getValue($obj);
            }
        }
        $ctrlRbac['actions'] = self::getActionsRbacDoc($controllerRef, $enableActions, $disabledActions);
        return $ctrlRbac;
    }

    /**
     * @throws \ReflectionException
     */
    private static function getClassRbacDoc($className): bool|array
    {
        $controllerRef = new \ReflectionClass($className);
        return self::getRefClassRbacDoc($controllerRef);
    }

    /**
     * 获取指定类的 rbac 信息
     * @param \ReflectionClass $ref 控制器 的反射类
     * @return mixed
     * @link https://docs.phalcon.io/5.0/en/annotations
     */
    private static function getRefClassRbacDoc(\ReflectionClass $ref): mixed
    {
        $cDos = Annotation::parse($ref->getDocComment());
        if (isset($cDos['rbac'])) {
            return ServicesJSON::decode(self::getDocContent($cDos['rbac']), ServicesJSON::GET_ARRAY);
        }
        return false;
    }

    /**
     * 读取指定控制器下所有 Action 的 rbac 信息
     * @param \ReflectionClass $ref 控制器反射对象
     * @param array $enableActions action 白名单
     * @param array $disableActions action 黑名单
     * @return array
     * @throws \Exception
     */
    private static function getActionsRbacDoc(\ReflectionClass $ref, array $enableActions = [], array $disableActions = []): array
    {
        $methods = [];
        foreach ($ref->getMethods() as $method) {
            if ($method->isPublic() && str_ends_with($method->getName(), 'Action')) {
                $mDoc = Annotation::parse($method->getDocComment());
                if (isset($mDoc['rbac'])) {
                    $name = substr($method->getName(), 0, -6); // Action 名称
                    if ($disableActions && in_array($name, $disableActions)) {
                        continue;
                    }
                    if ($enableActions && !in_array($name, $enableActions)) {
                        continue;
                    }
                    $dInfo = ServicesJSON::decode(self::getDocContent($mDoc['rbac']), ServicesJSON::GET_ARRAY);
                    if (is_null($dInfo)) {
                        throw new \Exception('is rbac annotation ok?' . $mDoc['rbac']);
                    } elseif (empty($dInfo)) {
                        $dInfo['title'] = Router::formatNodeName($name);
                    }
                    $methods[$name] = $dInfo;
                }
            }
        }
        return $methods;
    }

    /**
     * 格式化 rbac  注解信息, 将 "({title:'系统节点管理',scope:1})" 去掉左右两边的括号
     * @param string $doc
     * @return string
     */
    private static function getDocContent(string $doc): string
    {
        $doc = trim($doc, ' '); // 去掉两边多余的穴格
        return str_starts_with($doc, '(')
            ? substr($doc, 1, -1)
            : $doc;
    }

}