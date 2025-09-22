<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08.06.2025
 * Time: 4:29
 */

include_once(__DIR__.'/config.php');

include_once(__DIR__.'/functions.php');


header('Content-type: text/plain; charset=utf-8');
//header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

//exit('BAN FROM rezka.ag');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$stmt = $pdo->query("SELECT * FROM videos WHERE meta_found = 0");
//$stmt = $pdo->query("SELECT * FROM videos WHERE rating IS NULL");
//$stmt = $pdo->query("SELECT * FROM videos WHERE cover_url = ''");
//$stmt = $pdo->query("SELECT * FROM videos WHERE source_url = ''");
//echo 'Var: '.print_r($stmt->rowCount(),1)."<BR>\r\n";exit();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $title = $row['title'];
//    $title = 'Scary Movie 3 Unrated 2003';
//    $title = 'Scary Movie';

//    echo '$title: '.print_r($title,1)."<BR>\r\n";
//    echo '$row: '.print_r($row,1)."<BR>\r\n";
//    continue;
//    exit();
    $data = fetchRezkaMeta($title, $cacheDir);
//    $data = null;
//    echo '$data<PRE>: '.print_r($data,1)."<BR>\r\n";exit();

    if (empty($data['description']) && empty($data['poster'])) {
        $data = fetchTMDb($title, $tmbd, $cacheDir);
    }
//    $data = null;

    if (empty($data['description']) && empty($data['poster'])) {
        $data = fetchOMDb($title, $omdbAPI, $cacheDir);
    }

//    echo '$data: '.print_r($data,1)."<BR>\r\n";

    if (empty($data['description']) || empty($data['poster'])) {
        if(!isRussianTitle($title)){
            $title = translitToRussian($title).' ';
//            echo '$title: '.print_r($title,1)."<BR>\r\n";
        }
        $data = fetchRezkaMeta($title, $omdbAPI, $cacheDir);
    }

//    echo '$data: '.print_r($data,1)."<BR>\r\n";
//    exit('STOP3');


    if (!empty($data) && !empty($data['description']) && !empty($data['poster'])) {
        $coverFile = $data['poster'] ? saveCoverImage($data['poster'], $row['path']) : null;

//        echo '$data: '.print_r($data,1)."<BR>\r\n";

        $tivimateDescription = buildTiviMateDescription($data);

//        echo '$tivimateDescription: '.print_r($tivimateDescription,1)."<BR>\r\n";


        $data['year'] = preg_match('/\b(19|20)\d{2}\b/', $data['year'], $m) ? $m[0] : '';

        try {
            // 2. Готовим и выполняем запрос
            $upd = $pdo->prepare(
                "UPDATE videos 
         SET description = ?, cover_url = ?,  year = ?, meta_found = 1,
             actors = ?, country = ?, director = ?, genre = ?, source_url = ?, rating = ?
         WHERE id = ?"
            );
            $upd->execute([
                $tivimateDescription,
                $data['poster'],
                $data['year'],

                $data['actors'],
                $data['country'],
                $data['director'],
                $data['genre'],

                $data['source'],
                $data['rating'],


                $row['id'],



            ]);

        } catch (PDOException $e) {
            // 3. Обрабатываем ошибку
            // можно логировать $e->getCode() и $e->getMessage()
            echo "Ошибка при обновлении записи (ID {$row['id']}): "
                . $e->getMessage()."\r\n";

            echo '$row: '.print_r($row,1)."<BR>\r\n";
            echo '$data: '.print_r($data,1)."<BR>\r\n";
            exit('STOP4');
        }

//        exit('STOP');
        //        echo "[+] Обновлено: {$row['title']}<br>\n";
    } else {
        echo "[!] Не найдено: {$row['title']}<br>\n";

        echo '$data: '.print_r($data,1)."<BR>\r\n";
        echo '$row: '.print_r($row,1)."<BR>\r\n";
        exit('ERROR! Not find!');
    }
}


include_once(__DIR__.'/cron_posters.php');
include_once(__DIR__.'/fix_getCodec.php');
include_once(__DIR__.'/fixMovieImage.php');