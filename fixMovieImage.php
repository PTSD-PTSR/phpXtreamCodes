<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.09.2025
 * Time: 8:06
 */


include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');

ob_start();

header('Content-type: text/plain; charset=utf-8');
//header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');


$outputDir = "/var/www/bmg1.id.lv/public_html/screenshots/";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo 'START sceenshots: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n";
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM videos WHERE movie_image IS NULL LIMIT 100");
$count = $stmt->rowCount();
echo '$count: '.print_r($count,1)."<BR>\r\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $path = $row['path'];
    //    echo '$path: '.print_r($path,1)."<BR>\r\n";

    $output = $outputDir.$row['id'].'.jpg';

    // например, кадр на 7-й секунде
    $time = 7;

    $cmd = "/usr/bin/ffmpeg -ss {$time} -i " . escapeshellarg($path) ." -vframes 1 -q:v 2 " . escapeshellarg($output) . " -y 2>&1";
//    echo '$cmd: '.print_r($cmd,1)."<BR>\r\n";

    $result = shell_exec($cmd);

    if (!file_exists($output)) {
        $upd = $pdo->prepare("UPDATE videos SET movie_image = ?  WHERE id = ?");
        $upd->execute([$row['cover_file'], $row['id']]);
//        exit('ERROR! Cant create screenshot (id: '.$row['id'].') for: '.$path);
        continue;
    }


    $upd = $pdo->prepare("UPDATE videos SET movie_image = ?  WHERE id = ?");
    $upd->execute(['screenshots/'.$row['id'].'.jpg', $row['id']]);

    //    exit();

}
echo 'END sceenshots: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n";

if(!empty($count)){
    //    header("Refresh: 2"); // only for HTML
    echo 'REFRESH: '.print_r($_SERVER['PHP_SELF'],1)."<BR>\r\n";

    sleep(2); // подождать 2 секунды
    header("Location: ".$_SERVER['PHP_SELF'].'?time='.time()); // reload текущей страницы
    exit;
}
