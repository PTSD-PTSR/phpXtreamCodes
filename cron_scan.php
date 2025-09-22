<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08.06.2025
 * Time: 4:15
 */


include_once(__DIR__.'/config.php');
include_once(__DIR__.'/functions.php');


 header('Content-type: text/plain; charset=utf-8');
//header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

echo 'START: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n";


foreach ($rootPaths as $base) {
    foreach ($folders as $folder) {

//        if($folder != 'noRussian') continue;

//        echo 'Путь: ' . "$base/$folder" . "<br>\r\n";
        if (is_dir("$base/$folder")) {
            scanDirRecursive($base, $folder, $pdo);
//            echo "OK<br>\r\n"; exit();

        }
    }
}

echo "Scan complete.\r\n";

echo 'END: '.print_r(date('d/m/Y, H:i:s'),1)."<BR>\r\n\r\n\r\n\r\n\r\n";