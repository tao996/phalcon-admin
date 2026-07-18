<?php

namespace Phax\Utils;

/**
 * $url = MyUrlBuilder::new()
 * ->path('/detail')
 * ->language('zh-CN')
 * ->asApi()
 * ->query(['id' => 123])
 * ->build();
 */
class MyUrlBuilder
{
    private string $path = '';
    private string $language = '';
    private bool $isApi = false;
    private array|string $query = [];
    private string $origin = '';

    // 静态入口
    public static function new(): self
    {
        return new self();
    }

    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function language(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function asApi(): self
    {
        $this->isApi = true;
        return $this;
    }

    public function withModule(string $path): self
    {
        $this->path = 'm/'.$path;
        return $this;
    }

    public function withProject(string $path): self
    {
        $this->path = 'p/'.$path;
        return $this;
    }

    public function queryParams(array|string $query): self
    {
        $this->query = $query;
        return $this;
    }
    public function autoRoute():self
    {
        return $this;
    }

    /**
     * 从参数 $_GET 中获取
     * @param array|string $getNames
     * @return $this
     */
    public function query(array|string $getNames): self
    {
        foreach ($getNames as $name){
            if (!empty($_GET[$name])){
                $this->query[$name] = $_GET[$name];
            }
        }
        return $this;
    }

    public function origin(string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }


    /**
     * 构建链接
     * [语言/][api/][p/ProjectName/|m/ModuleName/][controller/][action/][params]
     * @return string
     */
    public function build(): string
    {
        $items = [];
        if ($this->language !== '') $items[] = $this->language;
        if ($this->isApi) $items[] = 'api';

        $url = '/' . ltrim($this->path, '/');
        if (!empty($items)) {
            $url = '/' . implode('/', $items) . $url;
        }

        if (!empty($this->query)) {
            $queryString = is_array($this->query) ? http_build_query($this->query) : $this->query;
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        if ($this->origin !== '') {
            return rtrim($this->origin, '/') . $url;
        }

        return $url;
    }
}