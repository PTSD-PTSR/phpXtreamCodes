<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09.06.2025
 * Time: 21:41
 */

include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');


//header('Content-type: text/plain; charset=utf-8');
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

$log = sprintf(
    "[%s] Any actions: %s | username=%s | ip=%s | query=%s\n",
    date('Y-m-d H:i:s'),
    $action,
    $username,
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['REQUEST_URI']
);
file_put_contents(__DIR__ . '/logs/player_api_'.date('d_m_Y').'.log', $log, FILE_APPEND);

// player_api.php — совместимый с Xtream Codes API (PHP 5.6)

if (empty($_GET['action'])) $_GET['action'] = 'validate_user';

// Парсинг запроса
$action = isset($_GET['action']) ? $_GET['action'] : '';
$username = isset($_GET['username']) ? $_GET['username'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';


if (!validate_user($username, $password)) {
    response(['user_info' => ['auth' => 0, 'status' => 'Blocked']]);
}

$validatedUser = [
    'server_info' => [
        'url' => 'bmg1.id.lv',
        'port' => '80',
        'https_port' => '443',
        'server_protocol' => 'http',
        'rtmp_port' => '8000',
        'timezone' => 'Europe/Riga',
        'timestamp_now' => time(),
        'time_now' => date('Y-m-d H:i:s'),
    ],
    'user_info' => [
        'auth' => 1,
        'username' => $username,
        'password' => $password,
        'message' => 'OK',
        'status' => 'Active',
        'is_trial' => '0',
        'active_cons' => '0',
        'max_connections' => '55',
        'exp_date' => strtotime('+ 25 days'),
        'created_at' => strtotime('- 25 days'),
        'allowed_output_formats' => [
            'avi',
            'mp4',
            'mkv',
        ],
    ]
];

switch ($action) {
    case 'handshake':
        response(['status' => 'OK']);
    case 'validate_user':
        response($validatedUser);
    case 'get_vod_categories':
//        response(get_vod_categories($pdo));
        response(get_vod_categories());
    case 'get_vod_streams':
        $cat = isset($_GET['category_id']) ? $_GET['category_id'] : null;
        response(get_vod_streams($pdo, $cat));
    case 'get_vod_info':
        $vod_id = isset($_GET['vod_id']) ? intval($_GET['vod_id']) : 0;
        response(get_vod_info($pdo, $vod_id));
    case 'get_series':
        $cat = isset($_GET['category_id']) ? $_GET['category_id'] : null;
        response(get_series($pdo, $cat));
    case 'get_series_categories':
        response(get_series_categories($pdo));
    case 'get_series_info':
        $series_id = isset($_GET['series_id']) ? $_GET['series_id'] : '';
        response(get_series_info($pdo, $series_id));
    default:
        $log = sprintf(
            "[%s] Unknown action: %s | username=%s | ip=%s | query=%s\n",
            date('Y-m-d H:i:s'),
            $action,
            $username,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['REQUEST_URI']
        );
        file_put_contents(__DIR__ . '/logs/player_api_errors_'.date('d_m_Y-H_i_s').'.log', $log, FILE_APPEND);
        response(['error' => 'Unknown action']);
}
