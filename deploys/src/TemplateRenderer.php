<?php

/**
 * 配置模板渲染器
 * 
 * 读取模板文件，替换 {{KEY}} 占位符，生成正式配置文件。
 * 模板目录结构：deploys/template/{project}/...
 * 每个文件中的 {{KEY}} 会替换为对应的变量值。
 */
class TemplateRenderer
{
    /**
     * @param string $templateDir 模板目录
     */
    public function __construct(protected string $templateDir)
    {
    }

    /**
     * 渲染单个模板文件内容
     */
    public function render(string $templatePath, array $vars): string
    {
        if (!file_exists($templatePath)) {
            deploy_log("模板文件不存在: {$templatePath}", 'warn');
            return '';
        }

        $content = file_get_contents($templatePath);
        return $this->replaceVars($content, $vars);
    }

    /**
     * 渲染模板并写入目标文件
     */
    public function renderToFile(string $templatePath, string $outputPath, array $vars): void
    {
        $content = $this->render($templatePath, $vars);
        if ($content === '') {
            return;
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $content);
        deploy_log("生成: {$outputPath}", 'ok');
    }

    /**
     * 渲染整个模板目录到输出目录
     * 
     * 保持子目录结构，跳过以 _ 开头的文件（内部引用）
     */
    public function renderDir(string $subDir, string $outputDir, array $vars): void
    {
        $srcDir = $this->templateDir . '/' . $subDir;
        if (!is_dir($srcDir)) {
            deploy_log("模板子目录不存在: {$srcDir}", 'warn');
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());
            $prefix = str_replace(DIRECTORY_SEPARATOR, '/', $srcDir) . '/';
            $relative = substr($relativePath, strlen($prefix));

            // 跳过以 _ 开头的文件（辅助模板片段）
            if (str_starts_with($relative, '_') || str_starts_with(basename($relative), '_')) {
                continue;
            }

            $outputPath = $outputDir . '/' . $relative;
            $this->renderToFile($file->getPathname(), $outputPath, $vars);
        }
    }

    /**
     * 替换字符串中的 {{KEY}} 占位符
     */
    protected function replaceVars(string $content, array $vars): string
    {
        $search = [];
        $replace = [];

        foreach ($vars as $key => $value) {
            $search[] = '{{' . strtoupper($key) . '}}';
            $replace[] = $value;
        }

        return str_replace($search, $replace, $content);
    }
}
