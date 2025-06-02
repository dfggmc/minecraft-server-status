<?php
// 允许跨站
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/vendor/autoload.php';

use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

// 确定请求方法
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 初始化服务器列表
$serverList = [];
$rawData = false;

// 处理GET请求
if ($requestMethod === 'GET') {
    $serverList[] = [
        'ip' => isset($_GET['ip']) ? $_GET['ip'] : '',
        'port' => isset($_GET['port']) ? $_GET['port'] : 25565
    ];
    $rawData = isset($_GET['raw']) ? $_GET['raw'] : false;
}
// 处理POST请求
elseif ($requestMethod === 'POST') {
    // 检查是否有JSON数据
    $jsonData = file_get_contents('php://input');
    $postData = json_decode($jsonData, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($postData)) {
        // 从JSON获取服务器列表
        if (isset($postData['servers']) && is_array($postData['servers'])) {
            foreach ($postData['servers'] as $server) {
                $serverList[] = [
                    'ip' => $server['ip'] ?? '',
                    'port' => $server['port'] ?? 25565
                ];
            }
        }
        // 从JSON获取raw参数
        $rawData = $postData['raw'] ?? false;
    }
    // 检查传统POST数据
    elseif (!empty($_POST)) {
        // 处理单个服务器的POST数据
        if (isset($_POST['ip'])) {
            $serverList[] = [
                'ip' => $_POST['ip'],
                'port' => $_POST['port'] ?? 25565
            ];
        }
        $rawData = $_POST['raw'] ?? false;
    }
}

/**
 * 查询超时时间
 */
define('MQ_TIMEOUT', 3);

// Display everything in browser, because some people can't look in logs for errors
Error_Reporting(0);
Ini_Set('display_errors', false);
header('Content-Type: application/json; charset=utf-8');

// 初始化结果数组
$results = [];

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

// 处理每个服务器
foreach ($serverList as $server) {
    $ip = $server['ip'];
    $port = $server['port'];

    if (empty($ip)) {
        $results[] = [
            'server' => ['ip' => $ip, 'port' => $port],
            'code' => 400,
            'data' => ['error' => ['message' => '服务器IP不能为空']]
        ];
        continue;
    }

    $Timer = MicroTime(true);
    $Info = false;
    $Query = null;

    try {
        $Query = new MinecraftPing($ip, $port, MQ_TIMEOUT);
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

    $response = [
        'server' => ['ip' => $ip, 'port' => $port],
        'queryTime' => $Timer . 's'
    ];

    if (isset($Exception)) {
        // 无法处理请求
        $response['code'] = 500;
        $response['data'] = ['error' => [
            'message' => htmlspecialchars($Exception->getMessage()),
        ]];
        unset($Exception); // 重置异常
    } else if ($Info !== false) {
        $response['code'] = 200;

        if ($rawData === false) {
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
                $extraDescription = nl2br($description['text'] ?? $description ?? '');
            }

            // 生成完整MOTD
            $fullDescription = '<div>' . $extraDescription . '</div>';

            // 设置响应数据
            $response['data']['version'] = $Info['version']['name'];
            $response['data']['motd'] = $fullDescription;
            $response['data']['players'] = [
                'online' => $Info['players']['online'] ?? null,
                'max' => $Info['players']['max'] ?? null,
                'list' => $players
            ];
            $response['data']['favicon'] = $Info['favicon'] ?? null;
        } else {
            $response['data'] = $Info;
        }
    } else {
        // 未获取到数据
        $response['code'] = 204;
        $response['data'] = [
            'motd' => null,
            'players' => [
                'online' => null,
                'max' => null,
                'list' => null
            ],
            'favicon' => null
        ];
    }

    $results[] = $response;
}

// 输出数据
echo json_encode(
    count($results) > 1 ? $results : (count($results) == 1 ? $results[0] : []),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);