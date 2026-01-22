<?php
session_start();
use voku\helper\HtmlDomParser;
include 'vendor/autoload.php';
error_reporting(-1);

function sql($query='', $values=array()) {
    global $db;
    $query = $db->prepare($query);
    $query->execute($values);
    return $query;
}
function redirect($url) {
	@header('Location: '.$url);
	echo '<meta http-equiv="refresh" content="0; url='.$url.'">';
	die;
}
function exportcsv($entries, $filename) {
   /* ob_end_clean();
    header("Content-type: application/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    ob_start(); */
    $out = fopen($filename, 'w');
   // $out = fopen('php://output', 'w');
    foreach ($entries as $entry) {
        fputcsv($out, $entry, ',', '"', '');
    }
   // fclose($out);
    //die();
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
	  'f&#233;vrier' => 2,
	  'mars' => 3,
	  'avril' => 4,
	  'mai' => 5,
	  'juin' => 6,
	  'juillet' => 7,
	  'ao&#251;t' => 8,
	  'septembre' => 9,
	  'octobre' => 10,
	  'novembre' => 11,
	  'd&#233;cembre' => 12,
	  ''=>0
	  );
	 preg_match('/(\d{1,2}) (\S+) (\d{4})/', $str, $matches, PREG_OFFSET_CAPTURE);
	  return array (
		'formated' => $matches[3][0].'-'.str_pad($mois[$matches[2][0]], 2, '0', STR_PAD_LEFT).'-'.str_pad($matches[1][0], 2, '0', STR_PAD_LEFT),
		'plain' => $matches[0][0]
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
		return '<a href="'.$url.'">'.$parse['host'].'</a>';
	}
	else {
		return $url;
	}
}
function check($var) {
    if ((isset($var) || !empty($var)) and $var == true) {
        return true;
    } else {
        return false;
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
		            
 function show_article($array=array('id'=>'', 'date'=>'', 'journal'=>'', 'category'=>'', 'source'=>'', 'content'=>''), $details=false) {
 	global $db;
 	global $_GET;
 	$filter_cat = (isset($_GET['idp'])) ? ' WHERE id_collection='.$_GET['idp']: '';
 	$tags = '<div class="btn-group">
  <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fa fa-tags" aria-hidden="true"></i> Assigner une catégorie
  </button>
  <ul class="dropdown-menu">';
	 $query = $db->query('SELECT DISTINCT LOWER(TRIM(category)) as category FROM presse '.$filter_cat.' ORDER BY LOWER(TRIM(category))');
	 $data = $query->fetchAll(PDO::FETCH_COLUMN);
	foreach(tags($data) as $cat) {
		$tags .= '<li><a class="dropdown-item" href="api.php?do=update-category&id='.$array['id'].'&category='.$cat.'&token='.$_SESSION['token'].'&idp='.$_GET['idp'].'" data-ajax="true">'.$cat.'</a></li>';
	 }
	 /*
	 while($data = $query->fetch()) {
	 	$tags .= '<li><a class="dropdown-item" href="?do=add&id='.$array['id'].'&category='.$data['category'].'&token='.$_SESSION['token'].'" target="_blank">'.$data['category'].'</a></li>';
	 } */
	$tags .='</ul></div>';
	$return = '<article>
		    <header>
		    <h1><a href="index.php?do=view&article='.$array['id'].'"><i class="fa fa-link" aria-hidden="true"></i></a> '.$array['title'].'</h1>
		    <aside>
		    <div class="d-print-none">
				<a href="?do=add&id='.$array['id'].'" class="btn btn-primary" target="_blank"><i class="fa fa-pencil" aria-hidden="true"></i> Éditer</a>
				<a  href="api.php?do=delete-entry&delete='.$array['id'].'&token='.$_SESSION['token'].'&idp='.$_GET['idp'].'" class="btn btn-danger"  onclick="return confirm(\'Êtes-vous sûr ?\');" data-ajax="true"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</a>
			'.$tags.'
		    <p><i class="fa fa-calendar" aria-hidden="true"></i> <time>'.$array['date'].'</time> ⋅ <i class="fa fa-newspaper-o" aria-hidden="true"></i> '.$array['journal'].' ⋅ <i class="fa fa-tags" aria-hidden="true"></i> '.$array['category'].' ⋅ <i class="fa fa-link" aria-hidden="true"></i> '.beautiful_url($array['source']).' ⋅ '.str_word_count((string)$array['content'],0).' mots</p>
		    </aside>
		    </header>
		    <main>';
		$return .= ($details == false) ? nl2br((string)$array['content']) : '<details><summary>Développer</summary>'.nl2br((string)$array['content']).'</details>';
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
		"id_collection" INTEGER,
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
}
else {
    $db = new PDO('sqlite:data/data.db');
}

if (!isset($_SESSION['login'])) {
    $_SESSION['login'] = false;
}
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $_SESSION['token'] = sha1(microtime());
    $_SESSION['permit'] = 1;
}
