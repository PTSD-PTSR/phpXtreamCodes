<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 08.06.2025
 * Time: 3:57
 */


include_once(__DIR__.'/config.php');

$videos = $pdo->query("SELECT * FROM videos GROUP BY title ORDER BY is_series, folder, title")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Медиа Сервер</title></head>
<body>
<h1>Видео библиотека</h1>
<ul>
<?php
$n = 1;
foreach ($videos as $v): ?>
    <li>
        <?=($n++)?> |
        <?=(!empty($v['is_series']) ? 'Series' : 'Movie')?> |
		<?=htmlspecialchars($v['folder'])?> |
        <?=htmlspecialchars($v['title'])?> |
        <a href="stream.php?stream=<?=urlencode($v['id'])?>">▶️ Смотреть</a>
    </li>
<?php endforeach; ?>
</ul>
</body>
</html>
