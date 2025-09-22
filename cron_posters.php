<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09.06.2025
 * Time: 22:36
 */


include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');


header('Content-type: text/plain; charset=utf-8');
//header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$total =0;
$download =0;
$stmt = $pdo->query("SELECT id, cover_url FROM videos WHERE cover_file IS NULL AND cover_url != '' ");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $total++;
    $newCoverUrl = downloadPosterIfNotExists($row['cover_url']);



    if ($newCoverUrl !== false) {
        $upd = $pdo->prepare("UPDATE videos SET cover_file = ? WHERE id = ?");
        $upd->execute([$newCoverUrl, $row['id']]);
        $download++;
    }

//        echo '$row: '.print_r($row,1)."<BR>\r\n";
//        echo '$newCoverUrl: '.print_r($newCoverUrl,1)."<BR>\r\n";
//        exit();
}
echo '$total: '.print_r($total,1)."<BR>\r\n";
echo '$download: '.print_r($download,1)."<BR>\r\n";