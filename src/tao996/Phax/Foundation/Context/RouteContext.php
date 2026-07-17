<?php

namespace Phax\Foundation\Context;

use Phax\Support\Config;

class RouteContext
{
    public string $module = '';
    public string $projectName = '';
    public string|null $projectNamespace = null;
    public string|null $projectViewPath = null;

    public string $defaultNamespace = 'App\\Http\\Controllers';
    public string $defaultViewPath = PATH_APP . 'Http' . DIRECTORY_SEPARATOR . 'views';

    public function with(Config $config): self
    {
        $project = $config->getString('app.project');
        $this->updateProject($project);

        $defaultApp = $config->getArray('app.defaultApp');

        if (!empty($defaultApp['namespace'])) {
            $this->defaultNamespace = $defaultApp['namespace'];
        }
        if (empty($defaultApp['viewpath'])) {
            if ($this->defaultNamespace != 'App\\Http\\Controllers') {
                if (!str_ends_with($this->defaultNamespace, '\\Controllers')) {
                    throw new \Exception('自定义命名空间必须以 \\Controllers 结尾');
                }
                if (str_starts_with($this->defaultNamespace, 'App\\Modules\\')) {
                    $cc = explode('\\', $this->defaultNamespace);
                    $this->defaultViewPath = PATH_APP_MODULES . $cc[2] . '/views';
                } elseif (str_starts_with($this->defaultNamespace, 'App\\Projects\\')) {
                    $cc = explode('\\', $this->defaultNamespace);
                    $this->defaultViewPath = PATH_APP_PROJECTS . $cc[2] . '/views';
                }
            }
        }
        return $this;
    }

    public static function config(Config $config): RouteContext
    {
        return (new RouteContext())->with($config);
    }

    public function updateProject(string $project):void
    {
        if (!empty($project)) {
            $this->projectName = $project;
            $this->projectNamespace = 'App\\Projects\\' . $project . '\\Controllers';
            $this->projectViewPath = PATH_APP_PROJECTS . $project . DIRECTORY_SEPARATOR . 'views';
        }
    }
}