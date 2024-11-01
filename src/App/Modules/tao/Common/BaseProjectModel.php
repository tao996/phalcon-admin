<?php

namespace App\Modules\tao\Common;

use Phax\Mvc\Model;

class BaseProjectModel extends Model
{
    public int $id = 0;
    public int $created_at = 0;
    public int $updated_at = 0;
    public int|null $deleted_at = null;

    /**
     * 前端视图记录 ID
     * @return string
     */
    public function generateRecordCid(): string
    {
        return join('.', [$this->id, $this->created_at]);
    }

    /**
     * @param string $cid
     * @return array [id=>主键, created_at=>创建时间]
     * @throws \Exception
     */
    public static function getFromCid(string $cid): array
    {
        if (empty($cid)) {
            throw new \Exception('记录 CID 不能为空');
        }
        $data = explode('.', $cid);
        if (count($data) != 2) {
            throw new \Exception('记录 CID 不合法');
        }
        if ($data[0] < 1) {
            throw new \Exception('记录 CID 不合要求');
        }
        return ['id' => $data[0], 'created_at' => $data[1]];
    }
}