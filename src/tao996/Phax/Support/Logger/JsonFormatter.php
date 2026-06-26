<?php

namespace Phax\Support\Logger;

use Phalcon\Logger\Formatter\AbstractFormatter;
use Phalcon\Logger\Item;

/**
 * JSON Lines 格式化器
 *
 * 每条日志输出为一行 JSON，完美兼容：
 *   - PHPStorm（配合 JSON Lines 插件）
 *   - ELK / Grafana Loki / Datadog 等日志聚合系统
 *   - 各类 JSON Lines 查看器
 *
 * 如果消息本身是 JSON 字符串（由 Logger::exception() 等生成），
 * 则自动注入 time/level 字段后直接输出；
 * 如果是普通文本（info/debug/warning 等简单消息），
 * 则包装为 {"time","level","message"} 后再输出。
 */
class JsonFormatter extends AbstractFormatter
{
    public function format(Item $item): string
    {
        $msgStr = $item->getMessage();
        $time = $item->getDateTime()->format('Y-m-d H:i:s');
        $level = $item->getLevelName();

        $existing = json_decode($msgStr, true);
        if (is_array($existing)) {
            // 消息已是 JSON，注入 time/level 后输出
            $existing['time'] = $time;
            $existing['level'] = $level;
            return json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }

        // 普通文本消息，包装为 JSON
        return json_encode([
            'time' => $time,
            'level' => $level,
            'message' => $msgStr,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
