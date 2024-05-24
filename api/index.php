<?php
require __DIR__ . '/vendor/autoload.php';
header('Access-Control-Allow-Origin: *');

use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

define('MQ_SERVER_ADDR', isset($_GET['ip']) ? $_GET['ip'] : '');
define('MQ_SERVER_PORT', isset($_GET['port']) ? $_GET['port'] : 25565);
define('DATA_RAW', isset($_GET['raw']) ? $_GET['raw'] : false);
/**
 * 查询超时时间
 */
define('MQ_TIMEOUT', 3);

// Display everything in browser, because some people can't look in logs for errors
Error_Reporting(0);
Ini_Set('display_errors', false);
header('Content-Type: application/json; charset=utf-8');
$Timer = MicroTime(true);
$Info = false;
$Query = null;
try {
    $Query = new MinecraftPing(MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT);
    $Info = $Query->Query();
    if ($Info === false) {
        $Query->Close();
        $Query->Connect();
        $Info = $Query->QueryOldPre17();
    }
} catch (MinecraftPingException $e) {
    $Exception = $e;
}
// 关闭链接
if ($Query !== null) {
    $Query->Close();
}
$Timer = number_format(microtime(true) - $Timer, 4, '.', '');
/**
 * 解析原始MOTD内容为HTML
 *
 * @param [type] $extra
 * @return void
 */
function parseMOTD($array)
{
    $html = '';

    foreach ($array as $item) {
        if (isset($item['text'])) {
            $html .= trim(nl2br($item['text']));
        }

        if (isset($item['extra']) && is_array($item['extra'])) {
            $html .= parseMOTD($item['extra']);
        }
    }

    return "$html";
}


$response = [
    'queryTime' => $Timer . 's'
];
if (isset($Exception)) {
    // 无法处理请求
    $response['code'] = 500;
    $response['data']['error'] = [
        'message' => htmlspecialchars($Exception->getMessage()),
    ];
} else if ($Info !== false) {
    $response['code'] = 200;

    if (DATA_RAW === false) {
        $description = $Info['description'];

        // 解析玩家信息
        $players = null;
        if (isset($Info['players']) && isset($Info['players']['sample'])) {
            $players = array_map(function ($player) {
                return htmlspecialchars($player['name']);
            }, $Info['players']['sample']);
        }

        // 解析MOTD
        $extraDescription = '';
        if (isset($description['extra'])) {
            $extraDescription = parseMOTD($description['extra']);
        } else {
            $extraDescription = nl2br($description['text']);
        }

        // 生成完整MOTD
        $fullDescription = '<div>' . $extraDescription . '</div>';

        // 设置响应数据
        $response['data']['version'] = $Info['version']['name'];
        $response['data']['motd'] = $fullDescription;
        $response['data']['players'] = [
            'online' => $Info['players']['online'] ?? null,
            'max' => $Info['players']['max'] ?? null,
            'players' => $players
        ];
        $response['data']['favicon'] = $Info['favicon'];
    } else {
        $response['data'] = $Info;
    }
} else {
    // 未获取到数据
    $response['code'] = 204;
    $response['data']['motd'] = null;
    $response['data']['players'] = [
        'online' => null,
        'max' => null,
        'players' => null
    ];
    $response['data']['favicon'] = null;
}

// 输出数据
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
