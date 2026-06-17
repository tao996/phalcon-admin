<?php

namespace Phax\Utils;

use Vtiful\Kernel\Excel;

/**
 * @link https://xlswriter.viest.me/docs/index.html
 */
class MyExcel
{
    private array $options = [
        'path' => '',
    ];
    private string $filename = '';

    public function __construct(public string $filepath)
    {
        if (!file_exists($this->filepath)) {
            throw new \Exception("File does not exist: {$this->filepath}");
        }
        $this->options['path'] = dirname($this->filepath);
        $this->filename = basename($this->filepath);
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    private Excel|null $excel = null;

    private function getExcel(): Excel
    {
        if ($this->excel == null) {
            $this->excel = new Excel($this->options);
            $this->excel = $this->excel->openFile($this->filename);
        }
        return $this->excel;
    }

    /**
     * 打开工作表
     * @link https://xlswriter.viest.me/docs/index.html#/zh-cn/reader/set-type.md 设置列的数据类型
     * @param string|null $sheetName 默认为第 1 个工作表
     * @param array $types 列的类型, 示例 `[0=>\Vtiful\Kernel\Excel::TYPE_TIMESTAMP]`
     * @return array
     * @throws \Exception
     */
    public function open(string|null $sheetName = null, array $types = []): Excel
    {
        if (empty($sheetName)) {
            throw new \Exception('sheet name is empty');
        }
        $sheet = $this->getExcel()->openSheet($sheetName);
        if ($types) {
            $sheet = $sheet->setType($types);
        }
        return $sheet;
    }

    public function rows(): \Generator
    {
        while ($row = $this->getExcel()->nextRow()) {
            yield $row;
        }
    }
}