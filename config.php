<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08.06.2025
 * Time: 4:26
 */

set_time_limit(0);

//ignore_user_abort(true);
ini_set('max_execution_time', 0);

setlocale(LC_ALL, 'en_US.UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);


date_default_timezone_set('Europe/Riga');

/**
 * @TODO:
 * Настроить php.ini на большие файлы (upload_max_filesize, post_max_size, memory_limit
 *
 *
 *
 *
 */

$AddToCRONTAB = <<<HTML
# каждые 3 часа индексировать
0 */3 * * * php /var/www/html/cron_scan.php
# раз в сутки искать описания
30 2 * * * php /var/www/html/cron_metadata.php
# раз в сутки чистить несуществующие
0 4 * * * php /var/www/html/cron_cleanup.php
HTML;

$pdo = new PDO('mysql:host=localhost;dbname=phpxtream', 'phpxtream', 'phpxtream');
$pdo->exec("SET NAMES utf8mb4");


$allUsers = [
    'demo' => ['password'=>'demo'],
];


$allowedExt = ['mp4','avi','mkv'];


$rootPaths = [
    '/HDD/1TbWhite/DLNA',
    '/HDD/4Tb/DLNA/Video',
    '/HDD/1Tb/DLNA2/Video',

    //    '/HDD/1Tb', // SUX
    //    '/HDD/3Tb', // SUX
];
$folders = [1=>'Russian', 2=>'noRussian', 3=>'CCCP', 4=>'multi', 5=>'Doc'];



$omdbAPI = '[YOUR_OMDB_KEY]';

$tmbd = [];
$tmbd['access'] = '[YOUR_TMBD_ACCESS]';
$tmbd['key'] = '[YOUR_TMBD_KEY]';



$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) mkdir($cacheDir);


if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) {
    $protocol = 'https';
} else {
    $protocol = 'http';
}

define('PROTOCOLS', $protocol);
define('DOMAIN', $_SERVER['HTTP_HOST'] );


