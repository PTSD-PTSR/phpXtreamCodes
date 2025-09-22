<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 18.06.2025
 * Time: 3:05
 */


include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');

ob_start();

header('Content-type: text/plain; charset=utf-8');
//header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

echo 'START: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n";
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM videos WHERE codec_video IS NULL LIMIT 100");
$count = $stmt->rowCount();
echo '$count: '.print_r($count,1)."<BR>\r\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $path = $row['path'];
//    echo '$path: '.print_r($path,1)."<BR>\r\n";

    $result = getCodecInfo($path);
    $videoCodec = $result['video'];
    $audioCodec = $result['audio'];
//    echo '$videoCodec: '.print_r($videoCodec,1)."<BR>\r\n";
//    echo '$audioCodec: '.print_r($audioCodec,1)."<BR>\r\n";
    if(empty($videoCodec)) $videoCodec = 'NONE';
    if(empty($audioCodec)) $audioCodec = 'NONE';

    if(empty($videoCodec) || empty($audioCodec)){
        echo '$path: '.print_r($path,1)."<BR>\r\n";
        echo '$result: '.print_r($result,1)."<BR>\r\n";

        exit();
    }


    $upd = $pdo->prepare("UPDATE videos SET codec_video = ?, codec_audio = ?  WHERE id = ?");
    $upd->execute([$videoCodec, $audioCodec, $row['id']]);

//    exit();

}
echo 'END: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n";
if(!empty($count)){
//    header("Refresh: 2"); // only for HTML
    echo 'REFRESH: '.print_r($_SERVER['PHP_SELF'],1)."<BR>\r\n";

    sleep(2); // подождать 2 секунды
    header("Location: ".$_SERVER['PHP_SELF'].'?time='.time()); // reload текущей страницы
    exit;
}
