<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 09.06.2025
 * Time: 14:28
 */



require_once(__DIR__.'/lib/phpQuery.php');

function scanDirRecursive($base, $folderName, $pdo) {

//    $testCode = 'ДМБ';
//    $testCode = 'Kung-Fu Panda Legends';


    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator("$base/$folderName", FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS)
    );


    foreach ($rii as $file) {



        if ($file->isDir()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['mp4', 'avi', 'mkv'])) continue;


        $rawPath = $file->getPathname();
        $cleanPath = preg_replace('/[\x00-\x1F\x7F]/u', '', $rawPath); // удаляет \n, \r, \t и пр.
//        $path = realpath($cleanPath);
        $path = $cleanPath;

        if(!empty($testCode) && strpos($path, $testCode) === false){
            continue;
        }


        // проверка на наличие в базе
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE path = ?");
        $stmt->execute([$path]);

        if ($stmt->fetchColumn() > 0) continue;


        $isSeries = is_dir(dirname($path)) && basename(dirname($path)) !== $folderName;

        if ($isSeries) {

            $relative = str_replace("$base/$folderName" . '/', '', $path);
            $parts = explode(DIRECTORY_SEPARATOR, $relative);
            if(count($parts) > 1){
                $rawTitle = $parts[0];

            }else{


                $dirParts = explode(DIRECTORY_SEPARATOR, dirname($path));
                $rawTitle = array_pop($dirParts); // получаем "Бедные люди, s1, 2016"
            }
        } else {
            $filename = $file->getFilename();
            $rawTitle = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', $filename);

        }


        // Чистка
        $clean = preg_replace('/[^a-zA-Zа-яА-ЯёЁ0-9]+/u', ' ', $rawTitle);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        // Разделяем по шаблону
        if ($isSeries && preg_match('/\s[sScCdDсС]\d+/u', $clean)) {
            $parts = preg_split('/\s[sScCdDсС]\d+/u', $clean, 2);
            $title = trim($parts[0]);
        } else {
            $title = $rawTitle;
        }

        $rawTitle = mExplode([
            '(',
            '(',
            'BDRip',
            'WEBRip',
            'WEB',
            'DVDRip',
            'HDRip',
            'HDTVRip',
            'SATRip',
            'CamRip',
        ],
            $rawTitle);

        $title = str_replace(
            array('.', '_', '`', '`', ),
            array(' ', ' ', '', ),
            $rawTitle);


        if(!empty($testCode) && strpos($path, $testCode) !== false){
            echo '$rawTitle: '.print_r($rawTitle,1)."<BR>\r\n";
            echo '$clean: '.print_r($clean,1)."<BR>\r\n";
            echo '$title: '.print_r($title,1)."<BR>\r\n";
            echo '$path: '.print_r($path,1)."<BR>\r\n";
            exit('STOP');
        }

        if(empty($testCode)){
            $size = trim(shell_exec('stat -c %s ' . escapeshellarg($path)));
//            echo '$size: '.print_r($size,1)."<BR>\r\n";


            $insert = $pdo->prepare("INSERT INTO videos (path, folder, is_series, title, size, extension) VALUES (?, ?, ?, ?, ?, ?)");
//            $insert->execute([$path, $folderName, $isSeries ? 1 : 0, $title, $file->getSize(), $ext]);
//            $insert->execute([$path, $folderName, $isSeries ? 1 : 0, $title, filesize($path), $ext]);
            $insert->execute([$path, $folderName, $isSeries ? 1 : 0, $title, $size, $ext]);
//                    echo '$title: '.print_r($title,1)."<BR>\r\n";
//                    echo '$folderName: '.print_r($folderName,1)."<BR>\r\n";
//                    echo '$size: '.print_r($size,1)."<BR>\r\n";
//                    echo '$ext: '.print_r($ext,1)."<BR>\r\n";
//                    echo '$filename: '.print_r($filename,1)."<BR>\r\n";
//                    echo 'is_file($path): '.print_r(is_file($path),1)."<BR>\r\n";
//                    echo 'is_readable($path): '.print_r(is_readable($path),1)."<BR>\r\n";
//                    echo 'filesize($path): '.print_r(filesize($path),1)."<BR>\r\n";
//                    echo 'path: '.print_r($path,1)."<BR>\r\n";
            echo "Добавлен: $path\n";
//            echo '$stmt->errorInfo(): '.print_r($stmt->errorInfo(),1)."<BR>\r\n";
//            exit();


        }

    }
}


function mExplode($delimiters, $haystack) {
    if (!is_array($delimiters)) {
        $delimiters = [$delimiters];
    }

    foreach ($delimiters as $delimiter) {
        $pos = mb_strpos($haystack, $delimiter);
        if ($pos !== false) {
            return mb_substr($haystack, 0, $pos);
        }
    }

    // если ничего не найдено — вернуть исходную строку
    return $haystack;
}

function saveCoverImage($url, $videoPath) {
    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    $filePath = preg_replace('/\\.[^.]+$/', '', $videoPath) . '.' . $ext;
    if (!file_exists($filePath)) {
        $img = @file_get_contents($url);
        if ($img) file_put_contents($filePath, $img);
    }
    return $filePath;
}


function fetchOMDb($title, $apiKey, $cacheDir) {
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

    $cacheFile = "$cacheDir/omdbapi_" . md5($title) . ".json";
    if (file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $query = urlencode($title . ' русский');
    $json = @file_get_contents("http://www.omdbapi.com/?t=$query&apikey=$apiKey&plot=short&r=json");
    $data = null;

    if ($json) {
        $d = json_decode($json, true);
        if (!empty($d['Title']) && $d['Response'] === 'True') {
            $data = [
                'title' => $d['Title'],
                'description' => $d['Plot'],
                'year' => $d['Year'],
                'poster' => $d['Poster'] !== 'N/A' ? $d['Poster'] : null
            ];
        }
        file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    return $data;
}

function fetchTMDb($title, $tmbd, $cacheDir) {

    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

    $cacheFile = "$cacheDir/TMDb_" . md5($title) . ".json";
    if (file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $title = urlencode($title);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$tmbd['access']}\r\nAccept: application/json\r\n"
        ]
    ]);

    $json = @file_get_contents("https://api.themoviedb.org/3/search/movie?query=$title&language=ru", false, $ctx);
    $data = null;

    if ($json) {
        $result = json_decode($json, true);
        if (!empty($result['results'][0])) {
            $film = $result['results'][0];
            $data = [
                'title' => $film['title'],
                'description' => $film['overview'],
                'year' => isset($film['release_date']) ? substr($film['release_date'], 0, 4) : '',
                'poster' => $film['poster_path'] ? "https://image.tmdb.org/t/p/w500{$film['poster_path']}" : null
            ];
        }
        file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    return $data;
}


function curlGet($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_ENCODING, ''); // включаем поддержку gzip/deflate
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


function fetchRezkaMeta($title, $cacheDir) {

    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

    $cacheFile = "$cacheDir/rezka_" . md5($title) . ".json";
//    echo '$cacheFile: '.print_r($cacheFile,1)."<BR>\r\n";
    if (file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }


    if(!isRussianTitle($title)){
        $title .= ' '.translitToRussian($title);
    }

    sleep(2);

    $searchUrl = 'https://rezka.ag/search/?do=search&subaction=search&q=' . urlencode($title);
    $html = curlGet($searchUrl);
    if (!$html) return null;

    phpQuery::newDocument($html);
    $first = pq('.b-content__inline_items .b-content__inline_item')->eq(0);
    if (!$first || !$first->length) return null;

    $link = $first->find('a')->attr('href');
    $poster = $first->find('img')->attr('src');
    $name = $first->find('.b-content__inline_item-link')->text();

    $filmHtml = curlGet($link);
    if (!$filmHtml) return null;

    phpQuery::newDocument($filmHtml);
    $desc = trim(pq('.b-post__description_text')->text());
    $posterBig = trim(pq('.b-sidecover a')->attr('href'));
    if(!empty($posterBig)){
        $poster = $posterBig;
    }

    $info = [];
    $rows = pq('.b-post__info tr');
    foreach ($rows as $row) {
        $label = trim(pq($row)->find('td.l')->text());
        $label = preg_replace('/[:\s]+$/u', '', $label);
        $value = trim(pq($row)->find('td')->eq(1)->text());
        $valueHtml = trim(pq($row)->find('td')->eq(1)->html());
//        echo '$valueHtml: '.print_r($valueHtml,1)."<BR>\r\n";
        if ($label && $value) {
            $info[$label] = $value;

            if($label == 'Рейтинги' && !empty($valueHtml)){
                preg_match_all('/<span class="bold">([\d.]+)<\/span>/', $valueHtml, $matches);
//                echo '$matches: '.print_r($matches,1)."<BR>\r\n";
                if(!empty($matches[1])){
                    $average = array_sum($matches[1]) / count($matches[1]);
                    $info[$label] = $average;

                }
            }
        }
    }

//    echo '$info: '.print_r($info,1)."<BR>\r\n";

    $actors = [];
    foreach (pq('.persons-list-holder .person-name-item span[itemprop=name]') as $actor) {
        $thisActor = pq($actor)->text();
        if(empty($info['Режиссер']) || $thisActor !== $info['Режиссер']) $actors[] = $thisActor;
    }

    $result = [
        'title'       => $name,
        'description' => $desc,
        'poster'      => $poster,
        'source'      => $link,
        'year'        => (!empty($info['Дата выхода']) ? $info['Дата выхода'] : ''),
        'country'     => (!empty($info['Страна']) ? $info['Страна'] : ''),
        'director'    => (!empty($info['Режиссер']) ? $info['Режиссер'] : ''),
        'genre'       => (!empty($info['Жанр']) ? $info['Жанр'] : ''),
        'rating'       => (!empty($info['Рейтинги']) ? $info['Рейтинги'] : ''),
        'actors'      => implode(', ', $actors),
    ];

    file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $result;
}


function buildTiviMateDescription(array $meta) {
    $parts = [];

    if (!empty($meta['description'])) {
        $parts[] = trim($meta['description']);
    }

    if (!empty($meta['year'])) {
        $parts[] = 'Год: ' . $meta['year'];
    }

    if (!empty($meta['country'])) {
        $parts[] = 'Страна: ' . $meta['country'];
    }

    if (!empty($meta['director'])) {
        $parts[] = 'Режиссёр: ' . $meta['director'];
    }

    if (!empty($meta['genre'])) {
        $parts[] = 'Жанр: ' . $meta['genre'];
    }

    if (!empty($meta['actors'])) {
        $parts[] = 'Актёры: ' . $meta['actors'];
    }

    return implode("\n\n", $parts);
}

function isRussianTitle($text) {
    return preg_match_all('/[А-Яа-яЁё]/u', $text) >= 2;
}

function translitToRussian($text) {
    //Nayomnyj.ubijca
    $map = [
        'yj'=>'ый', 'j'=>'й', 'с'=>'ц',
        'YJ'=>'ый', 'J'=>'й', 'С'=>'ц',

        'A'=>'А','B'=>'Б','V'=>'В','G'=>'Г','D'=>'Д','E'=>'Е','Yo'=>'Ё','Zh'=>'Ж','Z'=>'З',
        'I'=>'И','Y'=>'Й','K'=>'К','L'=>'Л','M'=>'М','N'=>'Н','O'=>'О','P'=>'П','R'=>'Р',
        'S'=>'С','T'=>'Т','U'=>'У','F'=>'Ф','H'=>'Х','Ts'=>'Ц','Ch'=>'Ч','Sh'=>'Ш','Sch'=>'Щ',
        'Yu'=>'Ю','Ya'=>'Я',

        'a'=>'а','b'=>'б','v'=>'в','g'=>'г','d'=>'д','e'=>'е','yo'=>'ё','zh'=>'ж','z'=>'з',
        'i'=>'и','y'=>'й','k'=>'к','l'=>'л','m'=>'м','n'=>'н','o'=>'о','p'=>'п','r'=>'р',
        's'=>'с','t'=>'т','u'=>'у','f'=>'ф','h'=>'х','ts'=>'ц','ch'=>'ч','sh'=>'ш','sch'=>'щ',
        'yu'=>'ю','ya'=>'я',

        'ye'=>'е','e'=>'е',' ’'=>'','\''=>'','`'=>'','~'=>'','q'=>'к','w'=>'в','x'=>'кс'
    ];

    // Сначала более длинные (двусоставные) заменить
    uasort($map, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    return strtr($text, $map);
}

function downloadPosterIfNotExists($url) {
    $parsed = parse_url($url);
    if (!isset($parsed['path'])) return false;

    $relativePath = ltrim($parsed['path'], '/');
    $localPath = __DIR__ . '/posters/' . $relativePath;

    // Если файл уже существует — вернуть путь
    if (file_exists($localPath)) {
        return 'posters/' . $relativePath;
    }

    // Создаём директории если нужно
    $dir = dirname($localPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // Получаем содержимое
    $content = @file_get_contents($url);
    if ($content === false) return false;

    // Сохраняем файл
    if (file_put_contents($localPath, $content) === false) return false;

    return 'posters/' . $relativePath;
}




// Функции
function response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validate_user($username, $password) {
    global $allUsers;

    // Простая проверка (можно доработать под таблицу пользователей)
//    return $username === 'demo' && $password === 'demo';
    return (!empty($allUsers[$username]['password']) && $allUsers[$username]['password'] == $password);
}

function get_vod_categories() {
    global $folders;
    $out = [];
    foreach ($folders as $id => $name) {
        $out[] = [
            'category_id'   => $id,
            'category_name' => $name
        ];
    }
    return $out;
}
function get_series_categories() {
    global $folders;
    $out = [];
    foreach ($folders as $id => $name) {
        $out[] = [
            'category_id'   => $id,
            'category_name' => 'Сериалы | '.$name,
            'censored' => 0,
            'parent_id' => 0,
        ];
    }
    return $out;
}

function get_vod_streams($pdo, $categoryId = null) {
    global $folders;

    $sql = "SELECT * FROM videos WHERE is_series = 0 ORDER BY created_at DESC, `path` ASC";
    $params = [];

    if ($categoryId !== null && isset($folders[$categoryId])) {
        $sql .= " AND folder = ?";
        $params[] = $folders[$categoryId];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $folderId = array_search($row['folder'], $folders);
        $out[] = [
            'name'                => $row['title'],
            'stream_id'           => $row['id'],
            'stream_type'         => 'movie',
            'category_id'         => $folderId ?: 0,
            'added'               => date('Y-m-d H:i:s'),
            'rating'              => (!empty($row['rating']) ? $row['rating'] : 6.9 ),
            'stream_icon'         => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
//            'container_extension' => $row['extension']
//            'container_extension' => 'ts'
            'created_at'           => $row['created_at'],

        ];
    }

    return $out;
}

function get_vod_info($pdo, $vod_id) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ? ORDER BY `path` ASC");
    $stmt->execute([$vod_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return [
        'info' => [
            'movie_image' => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
            'plot' => $row['description'],
            'cast' => $row['actors'],
            'director' => $row['director'],
            'genre' => $row['genre'],
            'releaseDate' => $row['year'],
            'country' => $row['country'],
//            'container_extension'=> 'ts',
        ],
        'stream_id' => $row['id'],
        'name' => $row['title'],
        'stream_icon' => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
//        'extension' => $row['extension'],
//        'extension'           => 'ts',
        'category_id' => $row['folder'],
    ];
}

function get_series($pdo,  $categoryId = null){
    global $folders;

    if(!empty($categoryId)){
        $thisCatName = '';
        if(!empty($folders[$categoryId])) $thisCatName = $folders[$categoryId];
        $thisCatName = str_replace('Сериалы | ', '', $thisCatName);


        $sql = "SELECT DISTINCT * FROM videos WHERE is_series = 1 AND folder = '$thisCatName' GROUP BY title ORDER BY created_at DESC , `path` ASC";
    }else{
        $sql = "SELECT DISTINCT * FROM videos WHERE is_series = 1  GROUP BY title ORDER by created_at DESC , title ASC";

    }


    $stmt = $pdo->query($sql);
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $folderId = array_search($row['folder'], $folders);

        $out[] = [
            'series_id' => $row['id'],
            'name' => $row['title'],
            'plot' => $row['description'],
            'cast' => $row['actors'],
            'director' => $row['director'],
            'genre' => $row['genre'],
            'releaseDate' => $row['year'],
            'category_id'         => $folderId ?: 0,
            'rating'              => (!empty($row['rating']) ? $row['rating'] : 6.9 ),


            'cover' => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
            'movie_image' => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
                'country' => $row['country'],
                'created_at' => $row['created_at'],

        ];
    }
    return $out;
}

function get_series_info($pdo, $series_id=null) {
    global $folders;

    $returnArray = [];
    // 1. Получаем path по id
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE is_series = 1 AND id = ? ORDER BY `path` ASC");
    $stmt->execute([$series_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $thisSeriesPath = $row['path'];
//    echo '$thisSeriesPath: '.print_r($thisSeriesPath,1)."<BR>\r\n";
    $directoryFirst = dirname($thisSeriesPath); // Получаем директорию файла
    $directory = dirname($directoryFirst); // Получаем директорию файла
    $lastFolder = basename($directory); // Получаем последнюю папку
//    echo '$directoryFirst: '.print_r($directoryFirst,1)."<BR>\r\n";
//    echo '$directory: '.print_r($directory,1)."<BR>\r\n";
//    echo '$lastFolder: '.print_r($lastFolder,1)."<BR>\r\n";
//    exit();

    $countFolders = countFolders($row['path']);
//    echo '$countFolders: '.print_r($countFolders,1)."<BR>\r\n";
    if($countFolders == 1){
        $directory = $directoryFirst;
    }





    /*
  "info": {
    "name": "Пространство",
    "cover": "http://mag-aura.com/stalker_portal/screenshots/4/361.jpg",
    "releaseDate": "2018-01-01",
    "episode_run_time": 0,
    "youtube_trailer": "",
    "director": "",
    "cast": "Steven Strait, Cas Anvar, Dominique Tipper, Wes Chatham, Shohreh Aghdashloo",
    "plot": "Через двести лет от дня сегодняшнего некий сыщик, привыкший всё доводить до конца при любых обстоятельствах, в рамках расследования дела об исчезновении молодой женщины оказывается на борту звездолета, капитан которого решает помочь детективу в его непростом деле. Величайший заговор в истории человечества гарантируется, как и его непременное раскрытие.",
    "last_modified": 1530973167,
    "genre": "Фантастика / Детектив",
    "category_id": 30,
    "backdrop_path": []
  },
     */

    $returnArray['info'] = [
        'name'=>$row['title'],
        'cover'=> PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
        'movie_image' => PROTOCOLS. '://'.DOMAIN.'/'.'posters/nauka.jpeg',

        'releaseDate'=>$row['year'],
        'episode_run_time'=>0,
        'youtube_trailer'=>'',
        'director'=>$row['director'],
        'cast'=>$row['actors'],
        'plot'=>$row['description'],
        'last_modified'=>$row['created_at'], //@TODO: convert to unixtime
        'genre'=>$row['genre'],
        'category_id'=>array_search($row['folder'], $folders, true),
        'backdrop_path'=>$row[''],
        'rating'              => (!empty($row['rating']) ? $row['rating'] : 6.9 ),

        //        '$directory'=>$directory,

    ];

//    echo '$directory: '.print_r($directory,1)."<BR>\r\n";
//    $emptySeries = cleanPath($row['path']);
//    echo '$emptySeries: '.print_r($emptySeries,1)."<BR>\r\n";

//    echo '$stmt->rowCount(): '.print_r($stmt->rowCount(),1)."<BR>\r\n";exit();

    if (!$row) return ['episodes' => []];

    $targetTitle = $row['title'];
//    echo '$targetTitle: '.print_r($targetTitle,1)."<BR>\r\n";

    // 2. Получаем все серии с тем же path
//    $stmt = $pdo->prepare("SELECT * FROM videos WHERE is_series = 1 AND title = ? ORDER BY id ASC");
//    $stmt = $pdo->prepare("SELECT * FROM videos WHERE is_series = 1 AND path LIKE  '%?%' ORDER BY id ASC");
//    $stmt = $pdo->prepare("SELECT * FROM videos WHERE path LIKE  '%?%' ORDER BY id ASC");
//    $stmt->execute([$directory]);

//    $sql = "SELECT * FROM videos WHERE is_series = 1 AND path LIKE  '%$directory%' ORDER BY `path` ASC";
//    echo '$sql: '.print_r($sql,1)."<BR>\r\n";
//    $stmt = $pdo->query($sql);

    $sql = "SELECT *
        FROM videos
        WHERE is_series = 1
          AND path LIKE :directory
        ORDER BY path ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':directory' => "%$directory%"]);

//    $emptySeries = cleanPath($row['path']);
//    echo '$emptySeries: '.print_r($emptySeries,1)."<BR>\r\n";
//
//    echo '$stmt->rowCount(): '.print_r($stmt->rowCount(),1)."<BR>\r\n";exit();

    $episodes = [];
    $episode_num = 1;
    $episodeNum = [];
    $seazonsNum = [];
    $seazonsLast = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $thisSeriesPath = $row['path'];
//        echo '$thisSeriesPath: '.print_r($thisSeriesPath,1)."<BR>\r\n";
        $directoryFirst = dirname($thisSeriesPath); // Получаем директорию файла
        $sesonName = basename($directoryFirst); // Получаем СЕЗОН
        $directory = dirname($directoryFirst); // Получаем директорию файла
        $lastFolder = basename($directory); // Получаем последнюю папку
//        echo '$directoryFirst: '.print_r($directoryFirst,1)."<BR>\r\n";
//        echo '$sesonName: '.print_r($sesonName,1)."<BR>\r\n";
//        echo '$directory: '.print_r($directory,1)."<BR>\r\n";
//        echo '$lastFolder: '.print_r($lastFolder,1)."<BR>\r\n";


        if(!isset($episodeNum[$sesonName])){
            $episodeNum[$sesonName] =0;
            $seazonsLast++;
            $seazonsNum[$sesonName] = $seazonsLast;
        }
        $episodeNum[$sesonName]++;
//        $episodes[$seazonsNum[$sesonName]][$episodeNum[$sesonName]] = [
//        $episodes[$seazonsNum[$sesonName]][] = [
        $episodes[$sesonName][] = [
            'id' => $row['id'],
//            '$sesonName' => $sesonName,
//            '$thisSeriesPath' => $thisSeriesPath,
            'title' => basename($row['path']),
//            'season' => $seazonsNum[$sesonName],
            'season' => $sesonName,
//            'episode_num' => $episode_num++,
            'episode_num' => $episodeNum[$sesonName],
            'container_extension' => $row['extension'],
            'movie_image' => PROTOCOLS. '://'.DOMAIN.'/'.'posters/nauka.jpeg?t='.time(),
//            'cover' => PROTOCOLS. '://'.DOMAIN.'/'.$row['cover_file'],
            'cover' => PROTOCOLS. '://'.DOMAIN.'/'.'posters/nauka.jpeg',
            'rating'              => (!empty($row['rating']) ? $row['rating'] : 6.9 ),
            'info'=>[
                'releasedate'=>'12/11/2009',
                'plot'=>' asdh kjhdkjsh dlkjhs dlkhsa dhaslkjdhasjd hashdjah',
                'duration_secs'=>'334',
                'duration'=>'2.3',
                'movie_image'=>PROTOCOLS. '://'.DOMAIN.'/'. (!empty($row['movie_image']) ? $row['movie_image'] : $row['cover_file'] ).'?t='.time(),
            ],


        ];
//        exit();
    }

//    echo '$episodes<PRE>: '.print_r($episodes,1)."<BR>\r\n";
//    $returnArray['episodes']['1'] = $episodes;
    $returnArray['episodes'] = $episodes;
//    return ['episodes' => ['1' => $episodes]];
    return $returnArray;
}


function getCodecInfo($filePath) {
    $filePath = escapeshellarg($filePath);

    // Видео кодек
    $videoCodec = trim(shell_exec("ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 $filePath"));

    // Аудио кодек
    $audioCodec = trim(shell_exec("ffprobe -v error -select_streams a:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 $filePath"));

    return [
        'video' => $videoCodec,
        'audio' => $audioCodec
    ];
}


function cleanPath($path) {
    global $rootPaths, $folders;
    // Убираем стартовые пути
    foreach ($rootPaths as $root) {
        if (strpos($path, $root) === 0) {
            $path = substr($path, strlen($root));
            break;
        }
    }

    $path = ltrim($path, "/"); // убрать ведущий /

    // Убираем первую встреченную категорию
    foreach ($folders as $folder) {
        if (strpos($path, $folder . "/") === 0) {
            $path = substr($path, strlen($folder) + 1);
            break;
        }
    }

    return $path;
}


function countFolders($path) {
    // сначала чистим
    $clean = cleanPath($path);

    // разбиваем
    $parts = explode('/', $clean);

    // последний элемент — файл, его исключаем
    array_pop($parts);

    // возвращаем количество папок
    return count($parts);
}