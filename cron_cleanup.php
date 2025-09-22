<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08.06.2025
 * Time: 4:48
 */

include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');


$total =0;
$delete =0;
$stmt = $pdo->query("SELECT id, path FROM videos");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $total++;
    if (!file_exists($row['path'])) {
        $del = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        $del->execute([$row['id']]);
        $delete++;
    }
}
echo '$total: '.print_r($total,1)."<BR>\r\n";
echo '$delete: '.print_r($delete,1)."<BR>\r\n";