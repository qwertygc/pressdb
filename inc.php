<?php
session_start();
use voku\helper\HtmlDomParser;
include 'vendor/autoload.php';

function sql($query='', $values=array()) {
    global $db;
    $query = $db->prepare($query);
    $query->execute($values);
    return $query;
}
function h($str) {
	return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
function is_logged() {
	return check(@$_SESSION['login']) OR $_SERVER['SERVER_NAME'] == 'localhost';
}
function is_admin() {
	return is_logged() AND (int)@$_SESSION['permit'] === 1;
}
function check($var) {
    return isset($var) AND !empty($var) AND $var == true;
}
function redirect($url) {
	header('Location: '.$url);
	echo '<meta http-equiv="refresh" content="0; url='.h($url).'">';
	die;
}
function csrf_field() {
	return '<input type="hidden" name="token" value="'.h($_SESSION['token']).'"/>';
}
function csrf_ok($method='POST') {
	$token = ($method === 'POST') ? (isset($_POST['token']) ? $_POST['token'] : '') : (isset($_GET['token']) ? $_GET['token'] : '');
	return $token !== '' AND isset($_SESSION['token']) AND hash_equals($_SESSION['token'], $token);
}
function exportcsv($entries, $filename) {
    $out = fopen($filename, 'w');
    foreach ($entries as $entry) {
        fputcsv($out, $entry, ',', '"', '');
    }
    fclose($out);
}
function exportiramuteq($entries, $filename) {
	$iramuteq = '';
	$header = array_shift($entries);
	foreach($entries as $entry) {
		$iramuteq .= '**** *date_'.$entry[0].' *year_'.$entry[1].' *month_'.$entry[2].' *journal_'.$entry[3].' *category_'.$entry[4].PHP_EOL.$entry[5].PHP_EOL.$entry[6].PHP_EOL.PHP_EOL;
	}
	file_put_contents($filename, $iramuteq);
}
function europresse2standardtime($str) {
	$mois = array(
	  'janvier' => 1,
	  'février' => 2,
	  'mars' => 3,
	  'avril' => 4,
	  'mai' => 5,
	  'juin' => 6,
	  'juillet' => 7,
	  'août' => 8,
	  'septembre' => 9,
	  'octobre' => 10,
	  'novembre' => 11,
	  'décembre' => 12
	  );
	  if(!preg_match('/(\d{1,2}) (\S+) (\d{4})/', (string)$str, $matches)) {
	    return array('formated' => date('Y-m-d'), 'plain' => (string)$str);
	  }
	  $mois_num = isset($mois[mb_strtolower($matches[2])]) ? $mois[mb_strtolower($matches[2])] : 0;
	  return array (
		'formated' => $matches[3].'-'.str_pad($mois_num, 2, '0', STR_PAD_LEFT).'-'.str_pad($matches[1], 2, '0', STR_PAD_LEFT),
		'plain' => $matches[0]
		);
}

function quickchart($type, $labels=array('Q1', 'Q2', 'Q3', 'Q4'), $ds=array(array('label'=>'Dogs', 'data'=>array(1, 2, 3, 4)))) {
    $quickchart = [
        'type' => $type,
        'data' => [
            'labels' => $labels,
            'datasets' => $ds
        ]
    ];
    return '<img src="https://quickchart.io/chart?c='.urlencode(json_encode($quickchart)).'" alt="chart"/>';
}
function beautiful_url($url) {
	if(filter_var($url, FILTER_VALIDATE_URL)) {
		$parse = parse_url($url);
		return '<a href="'.h($url).'" rel="noopener">'.h($parse['host']).'</a>';
	}
	else {
		return h($url);
	}
}
function ZipDir($filename, $path) {
	$rootPath = realpath($path);
	$zip = new ZipArchive();
	$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($rootPath),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file) {
		if (!$file->isDir()) {
			$filePath = $file->getRealPath();
			$relativePath = substr($filePath, strlen($rootPath) + 1);
			$zip->addFile($filePath, $relativePath);
		}
	}
	$zip->close();
}

function tags($array) {
	$export = array();
	foreach($array as $categories) {
		$category = explode(" ", $categories);
		foreach($category as $cat) {
			$export[] = $cat;
		}
	}
	$export = array_unique($export);
	return $export;
}
function badge($text) {
	if($text=='à_trier') {
		$color = 'text-bg-warning';
		
	}
	else {
		$color = 'text-bg-light';
	}
	return '<span class="badge '.$color.'"><i class="fa fa-tags" aria-hidden="true"></i> '.h($text).'</span>';
}            
 function show_article($array=array('id'=>'', 'date'=>'', 'journal'=>'', 'category'=>'', 'source'=>'', 'content'=>''), $details=false) {
 	global $db;
 	$filter_cat = (isset($_GET['idp'])) ? ' WHERE id_collection='.intval($_GET['idp']): '';
	$return = '<article>
		    <header>
		    <h1><a href="index.php?do=view&article='.intval($array['id']).'"><i class="fa fa-link" aria-hidden="true"></i></a> '.h($array['title']).'</h1>
		    <aside>
		    <div class="d-print-none">';
        if(!is_logged()) {
        }
        else {
        	$tags = '<div class="btn-group">
				  <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="fa fa-tags" aria-hidden="true"></i> Assigner une catégorie
				  </button>
				  <ul class="dropdown-menu">';
					 $query = sql('SELECT DISTINCT LOWER(TRIM(category)) as category FROM presse '.$filter_cat.' ORDER BY LOWER(TRIM(category))');
					 $data = $query->fetchAll(PDO::FETCH_COLUMN);
					 foreach(tags($data) as $cat) {
							$tags .= '<li><a class="dropdown-item" href="api.php?do=update-category&id='.intval($array['id']).'&category='.urlencode($cat).'&token='.h($_SESSION['token']).'&idp='.intval(@$_GET['idp']).'" data-ajax="true">'.h($cat).'</a></li>';
					 }
					$tags .='</ul></div>';
				$return .= '<a href="?do=add&id='.intval($array['id']).'" class="btn btn-primary" target="_blank"><i class="fa fa-pencil" aria-hidden="true"></i> Éditer</a>
				<a  href="api.php?do=delete-entry&delete='.intval($array['id']).'&token='.h($_SESSION['token']).'&idp='.intval(@$_GET['idp']).'" class="btn btn-danger"  onclick="return confirm(\'Êtes-vous sûr ?\');" data-ajax="true"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</a>
			'.$tags.'';
		}
	    $return .='<p><i class="fa fa-calendar" aria-hidden="true"></i> <time>'.h($array['date']).'</time> ⋅ <i class="fa fa-newspaper-o" aria-hidden="true"></i> '.h($array['journal']).' ⋅ '.badge($array['category']).' ⋅ <i class="fa fa-link" aria-hidden="true"></i> '.beautiful_url($array['source']).' ⋅ '.str_word_count((string)$array['content'],0).' mots</p>
		    </aside>
		    </header>
		    <main>';
		$return .= ($details == false) ? nl2br(h($array['content'])) : '<details><summary>Développer</summary>'.nl2br(h($array['content'])).'</details>';
		$return .= '   </main>
		</article>';
		return $return;
	}

if(!file_exists('data')) {
		mkdir('data');
		mkdir('data/export');
		mkdir('data/import');
		$db = new PDO('sqlite:data/data.db');
		$db->query('
CREATE TABLE "collections" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT,
	"name"	TEXT
);');
		$db->query('
CREATE TABLE "presse" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT,
	"id_collection"	INTEGER,
	"date"	TEXT,
	"journal"	TEXT,
	"category"	TEXT,
	"title"	TEXT,
	"content"	TEXT,
	"source"	TEXT
);');
		$db->query('
CREATE TABLE "users" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT,
	"login"	TEXT,
	"password"	TEXT,
	"permit"	INTEGER
);');

	sql('INSERT INTO users (login, password, permit) VALUES (?, ?, ?)', array('admin', password_hash('admin', PASSWORD_DEFAULT), 1));
		file_put_contents('data/.htaccess', '<FilesMatch "\.(db|sqlite)$">'."\n".'Require all denied'."\n".'</FilesMatch>'."\n");
}
else {
    $db = new PDO('sqlite:data/data.db');
}

if (!isset($_SESSION['login'])) {
    $_SESSION['login'] = false;
}
if (!isset($_SESSION['permit'])) {
    $_SESSION['permit'] = 0;
}
if (!isset($_SESSION['token']) OR empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}
