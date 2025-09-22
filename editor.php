<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 22.09.2025
 * Time: 22:54
 */

// editor.php — PHP 5.6 compatible
require_once __DIR__ . '/config.php';

function json_out($arr){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function get($k,$d=''){return isset($_GET[$k])?$_GET[$k]:$d;}
function post($k,$d=''){return isset($_POST[$k])?$_POST[$k]:$d;}
function trim_or_null($v){ if(!isset($v))return null; $v=is_string($v)?trim($v):$v; return $v===''?null:$v; }
function safe_random_hex($n){
    if(function_exists('openssl_random_pseudo_bytes')){
        $b=openssl_random_pseudo_bytes($n); if($b!==false) return bin2hex($b);
    }
    return substr(str_replace('.','',uniqid('',true)),0,$n*2);
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'list') {
        $q = trim(get('q',''));
        $page = (int)get('page',1); if($page<1)$page=1;
        $perPage = (int)get('perPage',15); if($perPage<5)$perPage=5; if($perPage>50)$perPage=50;
        $offset = ($page-1)*$perPage;

        $where=''; $params=array();
        if($q!==''){
            $where="WHERE (title LIKE :q OR path LIKE :q OR description LIKE :q)";
            $params[':q']="%".$q."%";
        }

        $stmt=$pdo->prepare("SELECT COUNT(*) FROM videos {$where}");
        $stmt->execute($params);
        $total=(int)$stmt->fetchColumn();

        $sql="SELECT id,title,path,description,cover_url,cover_file,movie_image,source_url,year,created_at
              FROM videos {$where}
              ORDER BY created_at DESC, id DESC
              LIMIT :lim OFFSET :off";
        $stmt=$pdo->prepare($sql);
        foreach($params as $k=>$v){ $stmt->bindValue($k,$v,PDO::PARAM_STR); }
        $stmt->bindValue(':lim',(int)$perPage,PDO::PARAM_INT);
        $stmt->bindValue(':off',(int)$offset,PDO::PARAM_INT);
        $stmt->execute();

        json_out(array('ok'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'perPage'=>$perPage));
    }

    if ($action === 'get') {
        $id=(int)get('id',0);
        $stmt=$pdo->prepare("SELECT * FROM videos WHERE id=:id LIMIT 1");
        $stmt->execute(array(':id'=>$id));
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        json_out(array('ok'=>(bool)$row,'item'=>$row));
    }

    if ($action === 'save' && $_SERVER['REQUEST_METHOD']==='POST') {
        $id=(int)post('id',0);

        $fields=array('title','path','description','cover_url','source_url',
            'year','actors','country','genre','director',
            'codec_video','codec_audio','folder','extension');

        $data=array();
        foreach($fields as $f){ $data[$f]=trim_or_null(post($f,null)); }

        $data['is_series']=isset($_POST['is_series'])?1:0;
        $data['meta_found']=isset($_POST['meta_found'])?1:0;
        $sizeRaw=post('size',''); $data['size']=($sizeRaw===''?null:(int)$sizeRaw);

        // --- Сначала INSERT, чтобы получить ID ---
        $newId = $id;
        if ($id==0) {
            $cols=array_keys($data); $ph=array(); $params=array();
            foreach($cols as $c){ $ph[]=':'.$c; $params[':'.$c]=$data[$c]; }
            $sql="INSERT INTO videos(`".implode('`,`',$cols)."`) VALUES(".implode(',',$ph).")";
            $stmt=$pdo->prepare($sql);
            $ok=$stmt->execute($params);
            $newId = (int)$pdo->lastInsertId();
        }

        // --- Cover File ---
        if (!empty($_FILES['cover_file']['name']) && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
            $baseDir = __DIR__ . '/posters/i/' . date('Y') . '/' . date('n') . '/' . date('j') . '/';
            if (!is_dir($baseDir)) @mkdir($baseDir,0775,true);
            $fname = uniqid().'.jpg';
            $dest = $baseDir.$fname;
            // Конвертируем в JPG
            $src = imagecreatefromstring(file_get_contents($_FILES['cover_file']['tmp_name']));
            if ($src) {
                imagejpeg($src,$dest,90);
                imagedestroy($src);
                $data['cover_file'] = str_replace(__DIR__.'/','',$dest);
            }
        }

        // --- Movie Image ---
        if (!empty($_FILES['movie_image']['name']) && is_uploaded_file($_FILES['movie_image']['tmp_name'])) {
            $baseDir = __DIR__ . '/screenshots/';
            if (!is_dir($baseDir)) @mkdir($baseDir,0775,true);
            $dest = $baseDir.$newId.'.jpg';
            $src = imagecreatefromstring(file_get_contents($_FILES['movie_image']['tmp_name']));
            if ($src) {
                imagejpeg($src,$dest,90);
                imagedestroy($src);
                $data['movie_image'] = str_replace(__DIR__.'/','',$dest);
            }
        }

        if ($id>0) {
            unset($data['path']); // path immutable
            if (!empty($data)) {
                $sets=array(); $params=array(':id'=>$id);
                foreach($data as $k=>$v){ $sets[]='`'.$k.'`=:'.$k; $params[':'.$k]=$v; }
                $sql="UPDATE videos SET ".implode(', ',$sets)." WHERE id=:id";
                $stmt=$pdo->prepare($sql);
                $stmt->execute($params);
            }
            json_out(array('ok'=>true,'id'=>$id));
        } else {
            // если были загружены файлы → обновим запись ссылками
            if (!empty($data['cover_file']) || !empty($data['movie_image'])) {
                $sets=array(); $params=array(':id'=>$newId);
                foreach($data as $k=>$v){
                    if($v!==null){ $sets[]='`'.$k.'`=:'.$k; $params[':'.$k]=$v; }
                }
                if(!empty($sets)){
                    $sql="UPDATE videos SET ".implode(', ',$sets)." WHERE id=:id";
                    $stmt=$pdo->prepare($sql);
                    $stmt->execute($params);
                }
            }
            json_out(array('ok'=>true,'id'=>$newId));
        }
    }


    json_out(array('ok'=>false,'error'=>'bad action'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Videos Editor</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="editor.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
  <h1 class="h3 mb-4">Videos</h1>

  <div class="row g-2 mb-3 align-items-center">
    <div class="col-md-4"><input id="search" class="form-control" placeholder="Search by title / path / description"></div>
    <div class="col-md-2">
      <select id="perPage" class="form-select">
        <option>10</option><option selected>15</option><option>25</option><option>50</option>
      </select>
    </div>
    <div class="col-auto">
      <button id="btnSearch" class="btn btn-primary">Search</button>
      <button id="btnReset" class="btn btn-outline-secondary ms-2">Reset</button>
    </div>
      <!--
    <div class="col-auto ms-auto">
      <button id="btnNew" class="btn btn-success">+ New</button>
    </div>
      -->
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:60px">#</th>
          <th style="width:70px">Cover</th>
          <th>Title</th>
          <th>Path</th>
<!--          <th>Description</th>-->
          <th style="width:160px" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody id="rows"></tbody>
    </table>
  </div>
  <nav><ul id="pager" class="pagination"></ul></nav>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">View</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body view-body">
        <div id="viewContent"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button id="viewToEdit" class="btn btn-primary">Edit</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form id="editForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Edit</h5>
          <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="editFormFields"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="editor.js"></script>
</body>
</html>
