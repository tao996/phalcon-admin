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

    private function _excel(): Excel
    {
        static $obj = null;
        if ($obj === null) {
            $excel = new Excel($this->options);
            $obj = $excel->fileName($this->filename);
        }
        return $obj;
    }

    /**
     * 读取数据
     * @link https://xlswriter.viest.me/docs/index.html#/zh-cn/reader/set-type.md 设置列的数据类型
     * @param string|null $sheetName 默认为第 1 个工作表
     * @param array $types 列的类型, 示例 `[0=>\Vtiful\Kernel\Excel::TYPE_TIMESTAMP]`
     * @return array
     * @throws \Exception
     */
    public function getSheetData(string|null $sheetName = null, array $types = []): array
    {
        $sheet = $this->_excel()->openSheet($sheetName);
        if ($types) {
            $sheet = $sheet->setType($types);
        }
        return $sheet->getSheetData();
    }
}