<?php
use voku\helper\HtmlDomParser;
include 'vendor/autoload.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off')]);
session_start();

function sql($query='', $values=array()) {
    global $db;
    $query = $db->prepare($query);
    $query->execute($values);
    return $query;
}
function no_accents($str) {
	$converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$str);
	if($converted === false) {return strtolower((string)$str);}
	return strtolower($converted);
}
function paginate_links($base_url, $current_page, $per_page, $total) {
	$pages = (int)ceil($total / $per_page);
	if($pages <= 1) {return '';}
	$out = '<nav><ul class="pagination">';
	for($p = 1; $p <= $pages; $p++) {
		$active = ($p == $current_page) ? ' active' : '';
		$sep = (strpos($base_url, '?') === false) ? '?' : '&';
		$out .= '<li class="page-item'.$active.'"><a class="page-link" href="'.h($base_url.$sep.'page='.$p).'">'.$p.'</a></li>';
	}
	return $out.'</ul></nav>';
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
function url_allowed($url) {
	if(!filter_var($url, FILTER_VALIDATE_URL)) {return false;}
	$parts = parse_url($url);
	if(!isset($parts['scheme']) OR !in_array(strtolower($parts['scheme']), array('http', 'https'))) {return false;}
	if(!isset($parts['host']) OR $parts['host'] === '') {return false;}
	$host = strtolower($parts['host']);
	if(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$ip = $host;
	}
	elseif(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$ip = $host;
	}
	else {
		$ip = gethostbyname($host);
		if($ip === $host) {return false;}
	}
	$priv4 = array('10', '127', '0');
	if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$o = explode('.', $ip);
		if($o[0] == '10' OR $o[0] == '127' OR $o[0] == '0' OR ($o[0] == '192' AND $o[1] == '168') OR ($o[0] == '172' AND $o[1] >= 16 AND $o[1] <= 31) OR ($o[0] == '169' AND $o[1] == '254')) {return false;}
	}
	elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$low = strtolower($ip);
		if(strpos($low, '::1') === 0 OR strpos($low, 'fc') === 0 OR strpos($low, 'fd') === 0 OR strpos($low, 'fe80') === 0) {return false;}
	}
	return true;
}
function login_throttle_ok() {
	$fails = (isset($_SESSION['login_fails'])) ? (int)$_SESSION['login_fails'] : 0;
	return $fails < 5;
}
function login_throttle_fail() {
	$_SESSION['login_fails'] = ((isset($_SESSION['login_fails'])) ? (int)$_SESSION['login_fails'] : 0) + 1;
}
function login_throttle_reset() {
	unset($_SESSION['login_fails']);
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

$chart_configs = array();
function chart_counter() {
    static $n = 0;
    $n++;
    return 'chart'.$n;
}
function quickchart($type, $labels=array('Q1', 'Q2', 'Q3', 'Q4'), $ds=array(array('label'=>'Dogs', 'data'=>array(1, 2, 3, 4)))) {
    global $chart_configs;
    if(!isset($chart_configs) OR !is_array($chart_configs)) {$chart_configs = array();}
    $config = [
        'type' => $type,
        'data' => [
            'labels' => (is_array($labels) ? $labels : array()),
            'datasets' => (is_array($ds) ? $ds : array(array('label' => 'Occurrences', 'data' => array())))
        ]
    ];
    $id = chart_counter();
    $chart_configs[$id] = $config;
    return '<canvas id="'.$id.'"></canvas>';
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
  function show_article($array=array('id'=>'', 'date'=>'', 'journal'=>'', 'category'=>'', 'source'=>'', 'content'=>''), $details=false, $bulk=false) {
 	global $db;
 	$filter_cat = (isset($_GET['idp']) AND intval($_GET['idp']) > 0) ? ' WHERE id_collection='.intval($_GET['idp']): '';
	$id = intval($array['id']);
	$return = '<article class="border rounded p-2 mb-2">
	    <header class="d-flex flex-wrap align-items-start gap-2">
	    '.(($bulk AND is_logged()) ? '<input type="checkbox" name="bulk_ids[]" value="'.$id.'" aria-label="Sélectionner"/> ' : '').'<h2 class="h6 flex-grow-1 mb-0"><a href="index.php?do=view&article='.$id.'"><i class="fa fa-link" aria-hidden="true"></i></a> '.h($array['title']).'</h2>';
        if(is_logged()) {
        	$tags = '<li class="dropdown-header">Assigner une catégorie</li>';
			 foreach(categories_list((isset($_GET['idp']) AND intval($_GET['idp']) > 0) ? intval($_GET['idp']) : null) as $cat) {
					$tags .= '<li><a class="dropdown-item" href="api.php?do=update-category&id='.$id.'&category='.urlencode($cat).'&token='.h($_SESSION['token']).'&idp='.intval(@$_GET['idp']).'" data-ajax="true">'.h($cat).'</a></li>';
			 }
			$return .= '<div class="dropdown d-print-none">
				<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></button>
				<ul class="dropdown-menu">
				<li><a class="dropdown-item" href="?do=add&id='.$id.'"><i class="fa fa-pencil" aria-hidden="true"></i> Éditer</a></li>
				<li><hr class="dropdown-divider"/></li>
				'.$tags.'
				<li><hr class="dropdown-divider"/></li>
				<li><a class="dropdown-item text-danger" href="api.php?do=delete-entry&delete='.$id.'&token='.h($_SESSION['token']).'&idp='.intval(@$_GET['idp']).'" onclick="return confirm(\'Êtes-vous sûr ?\');" data-ajax="true"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</a></li>
				</ul>
			</div>';
		}
	    $return .='</header>
	    <p class="mb-1 mt-1 small text-muted"><time datetime="'.h($array['date']).'"><strong>'.h($array['date']).'</strong></time> ⋅ <i class="fa fa-newspaper-o" aria-hidden="true"></i> '.h($array['journal']).' ⋅ '.badge($array['category']).' ⋅ <i class="fa fa-link" aria-hidden="true"></i> '.beautiful_url($array['source']).((!empty($array['dupe_count'])) ? ' ⋅ <span class="badge text-bg-warning" title="Titre partagé avec '.(int)$array['dupe_count'].' autre(s) article(s)"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> doublon ×'.((int)$array['dupe_count'] + 1).'</span>' : '').' ⋅ '.str_word_count((string)$array['content'],0).' mots</p>';
	    $main_class = ($details == true) ? 'article-full' : 'article-compact';
	    $return .= '<main class="'.$main_class.'">';
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
	"name"	TEXT,
	"public"	INTEGER DEFAULT 0
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
		$db->query('
CREATE TABLE "categories" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT,
	"name"	TEXT UNIQUE
);');
		$db->query('
CREATE TABLE "presse_categories" (
	"id_presse"	INTEGER,
	"id_category"	INTEGER,
	UNIQUE(id_presse, id_category)
);');
		$db->query('
CREATE TABLE "users_collections" (
	"id_user"	INTEGER,
	"id_collection"	INTEGER,
	UNIQUE(id_user, id_collection)
);');

	sql('INSERT INTO users (login, password, permit) VALUES (?, ?, ?)', array('admin', password_hash('admin', PASSWORD_DEFAULT), 1));
}
else {
    $db = new PDO('sqlite:data/data.db');
}

function category_id($name) {
	global $db;
	$name = trim((string)$name);
	if($name === '') {return 0;}
	$exists = sql('SELECT id FROM categories WHERE name = ?', array($name))->fetch();
	if($exists) {return (int)$exists['id'];}
	sql('INSERT INTO categories (name) VALUES (?)', array($name));
	return (int)$db->lastInsertId();
}
function article_set_categories($id_presse, $names) {
	global $db;
	if(!is_array($names)) {$names = array_filter(array_map('trim', explode(' ', (string)$names)));}
	$id_presse = (int)$id_presse;
	sql('DELETE FROM presse_categories WHERE id_presse = ?', array($id_presse));
	foreach($names as $name) {
		$name = trim((string)$name);
		if($name === '') {continue;}
		$id_cat = category_id($name);
		if($id_cat > 0) {sql('INSERT OR IGNORE INTO presse_categories (id_presse, id_category) VALUES (?, ?)', array($id_presse, $id_cat));}
	}
	$sql = 'UPDATE presse SET category = ? WHERE id = ?';
	$category_text = implode(' ', array_filter(array_map('trim', $names), function($n){return $n !== '';}));
	sql($sql, array($category_text, $id_presse));
}
function article_categories($id_presse) {
	$id_presse = (int)$id_presse;
	if($id_presse <= 0) {return array();}
	return sql('SELECT c.name FROM presse_categories pc JOIN categories c ON c.id = pc.id_category WHERE pc.id_presse = ? ORDER BY c.name', array($id_presse))->fetchAll(PDO::FETCH_COLUMN);
}
function public_collections_clause($alias) {
	if(is_admin()) {return '';}
	if(is_logged()) {
		if(empty($_SESSION['user_id'])) {return ' AND ('.$alias.'.public = 1)';}
		return ' AND ('.$alias.'.public = 1 OR '.$alias.'.id IN (SELECT uc.id_collection FROM users_collections uc WHERE uc.id_user = '.(int)$_SESSION['user_id'].'))';
	}
	return ' AND ('.$alias.'.public = 1)';
}
function categories_list($id_collection = null) {
	if($id_collection !== null) {
		return sql('SELECT DISTINCT c.name FROM categories c JOIN presse_categories pc ON pc.id_category = c.id JOIN presse p ON p.id = pc.id_presse WHERE p.id_collection = ? ORDER BY c.name', array((int)$id_collection))->fetchAll(PDO::FETCH_COLUMN);
	}
	return sql('SELECT name FROM categories ORDER BY name', array())->fetchAll(PDO::FETCH_COLUMN);
}
function migrate_categories() {
	$data = sql('SELECT id, category FROM presse WHERE category IS NOT NULL AND TRIM(category) != ""', array())->fetchAll();
	foreach($data as $row) {
		article_set_categories($row['id'], $row['category']);
	}
}
$uc_table = sql("SELECT count(*) as n FROM sqlite_master WHERE type='table' AND name = 'users_collections'", array())->fetch();
if((int)$uc_table['n'] === 0) {
	$db->query('
CREATE TABLE IF NOT EXISTS "users_collections" (
	"id_user"	INTEGER,
	"id_collection"	INTEGER,
	UNIQUE(id_user, id_collection)
);');
}
$col_public = sql("SELECT count(*) as n FROM pragma_table_info('collections') WHERE name = 'public'", array())->fetch();
if((int)$col_public['n'] === 0) {
	$db->query('ALTER TABLE collections ADD COLUMN public INTEGER DEFAULT 0;');
}
$cat_tables = sql("SELECT count(*) as n FROM sqlite_master WHERE type='table' AND name IN ('categories', 'presse_categories')", array())->fetch();
if((int)$cat_tables['n'] < 2) {
	$db->query('
CREATE TABLE IF NOT EXISTS "categories" (
	"id"	INTEGER PRIMARY KEY AUTOINCREMENT,
	"name"	TEXT UNIQUE
);');
	$db->query('
CREATE TABLE IF NOT EXISTS "presse_categories" (
	"id_presse"	INTEGER,
	"id_category"	INTEGER,
	UNIQUE(id_presse, id_category)
);');
	migrate_categories();
}

if(!file_exists('data/.htaccess')) {
	file_put_contents('data/.htaccess', '<FilesMatch "\.(db|sqlite)$">'."\n".'Require all denied'."\n".'</FilesMatch>'."\n");
}
if(!file_exists('data/import/.htaccess')) {
	if(!is_dir('data/import')) {mkdir('data/import', 0775, true);}
	file_put_contents('data/import/.htaccess', 'php_flag engine off'."\n".'SetHandler none'."\n".'Require all denied'."\n");
}
if(!file_exists('data/export/.htaccess')) {
	if(!is_dir('data/export')) {mkdir('data/export', 0775, true);}
	file_put_contents('data/export/.htaccess', 'php_flag engine off'."\n".'SetHandler none'."\n");
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
