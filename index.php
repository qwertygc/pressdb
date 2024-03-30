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
        fputcsv($out, $entry);
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
		            
 function show_article($array=array('id'=>'', 'date'=>'', 'journal'=>'', 'category'=>'', 'source'=>'', 'content'=>'')) {
 	global $db;
 	global $_GET;
 	$filter_cat = (isset($_GET['idp'])) ? ' WHERE id_collection='.$_GET['idp']: '';
 	$tags = '<div class="btn-group">
  <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="fa fa-tags" aria-hidden="true"></i> Assigner une catégorie
  </button>
  <ul class="dropdown-menu">';
	 $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse '.$filter_cat.' ORDER BY category');
	 $data = $query->fetchAll(PDO::FETCH_COLUMN);
	foreach(tags($data) as $cat) {
		$tags .= '<li><a class="dropdown-item" href="?do=add&id='.$array['id'].'&category='.$cat.'&token='.$_SESSION['token'].'" target="_blank">'.$cat.'</a></li>';
	 }
	 /*
	 while($data = $query->fetch()) {
	 	$tags .= '<li><a class="dropdown-item" href="?do=add&id='.$array['id'].'&category='.$data['category'].'&token='.$_SESSION['token'].'" target="_blank">'.$data['category'].'</a></li>';
	 } */
	$tags .='</ul></div>';
	return '<article>
		    <header>
		    <h1><a href="index.php?do=view&article='.$array['id'].'"><i class="fa fa-link" aria-hidden="true"></i></a> '.$array['title'].'</h1>
		    <aside>
		    <div class="d-print-none">
				<a href="?do=add&id='.$array['id'].'" class="btn btn-primary" target="_blank"><i class="fa fa-pencil" aria-hidden="true"></i> Éditer</a>
				<a href="?do=add&delete='.$array['id'].'&token='.$_SESSION['token'].'" class="btn btn-danger"  onclick="return confirm(\'Êtes-vous sûr ?\');" target="_blank"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</a>
			'.$tags.'
		    <p><i class="fa fa-calendar" aria-hidden="true"></i> <time>'.$array['date'].'</time> ⋅ <i class="fa fa-newspaper-o" aria-hidden="true"></i> '.$array['journal'].' ⋅ <i class="fa fa-tags" aria-hidden="true"></i> '.$array['category'].' ⋅ <i class="fa fa-link" aria-hidden="true"></i> '.beautiful_url($array['source']).' ⋅ '.str_word_count((string)$array['content'],0).' mots</p>
		    </aside>
		    </header>
		    <main>
		    '.nl2br($array['content']).'
		    </main>
		</article>';
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
?>
<!doctype html>
<html>
<head>
<title>Presse</title>
<link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="vendor/forkawesome/fork-awesome/css/fork-awesome.min.css">
    <style>
@media screen {
    main {
        max-width:800px;
        margin:auto;
    }
}
@media print {
	 * {
		text-shadow: none !important;
		color: #000 !important;
		background: transparent !important;
		box-shadow: none !important;
	}
	body {
		width: auto!important;
		margin: auto!important;
        margin: 2cm;
		font-family: serif;
		font-size: 12pt;
		background-color: #fff!important;
		color: #000!important;
	}
    h1 {
        font-size:14pt;
    }
    main {
       text-align:justify;
        font-size: 12pt;
    }
}
    </style>
</head>
<body class="container-fluid">
<nav class="navbar navbar-expand-lg navbar-light bg-light d-print-none">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="index.php"><i class="fa fa-database" aria-hidden="true"></i> Base de données</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=collections"><i class="fa fa-book" aria-hidden="true"></i> Liste des collections</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=view"><i class="fa fa-eye" aria-hidden="true"></i> Voir</a>
      </li>
      <?php  if(check(@$_SESSION['login']) || $_SERVER['SERVER_NAME'] =='localhost') : ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=add"><i class="fa fa-pencil" aria-hidden="true"></i> Ajouter un article</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=tools"><i class="fa fa-wrench" aria-hidden="true"></i> Outils</a>
      </li>
	<li class="nav-item">
        <a class="nav-link" href="index.php?do=import"><i class="fa fa-upload" aria-hidden="true"></i> Importer</a>
      </li>
      <?php if($_SESSION['permit'] == 1): ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=admin"><i class="fa fa-users" aria-hidden="true"></i> Administration</a>
      </li>
      <?php endif; ?>
    <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=login"><i class="fa fa-sign-in" aria-hidden="true"></i> <?php echo (!check(@$_SESSION['login'])) ? 'Connexion': 'Déconnexion'; ?></a>
      </li>
    </ul>
</nav>
<main>
<?php

if(isset($_GET['do'])) {
    switch($_GET['do']) {
        case 'collections':
        if(!check(@$_SESSION['login']) AND $_SERVER['SERVER_NAME'] != 'localhost') {header('Location: index.php?do=login');}
        if(isset($_GET['delete']) AND isset($_GET['token']) AND $_GET['token'] == $_SESSION['token'] AND $_GET['token'] !='') {
        	sql('DELETE FROM collections WHERE id=?', array($_GET['delete']));
        	sql('DELETE FROM presse WHERE id_collection=?', array($_GET['delete']));
        }
        
        echo '<form method="post" class="d-print-none" action="?do=collections">
               <label>Nom de la collection</label><input type="text" name="collection_name" class="form-control" />
               <input type="submit" class="btn btn-primary mb-3"/>
                </form>';
            if(isset($_POST['collection_name'])) {
                sql('INSERT into collections (name) VALUES (?)', array($_POST['collection_name']));
            }
            echo '<table class="table">
                    <tr><th>Collections</th><th>Actions</th></tr>';
            $query = $db->query('SELECT * FROM collections ORDER BY id');
            while($data = $query->fetch()) {
                echo '<tr><td><a href="?do=view&idp='.$data['id'].'">'.$data['name'].'</a></td>
                    <td>
                        <a href="?do=view&idp='.$data['id'].'" class="btn btn-primary"><i class="fa fa-eye" aria-hidden="true"></i></a>
                        <a href="?do=add&idp='.$data['id'].'" class="btn btn-primary"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                        <a href="?do=collections&delete='.$data['id'].'&token='.$_SESSION['token'].'" class="btn btn-danger" onclick="return confirm(\'Êtes-vous sûr ?\');"><i class="fa fa-trash" aria-hidden="true"></i></a>
                    </td>
                </tr>';
            }
            echo '</table>';
        break;
        case 'view':
        	#if(!check(@$_SESSION['login']) AND $_SERVER['SERVER_NAME'] !='localhost') {header('Location: index.php?do=login');}
        	if(isset($_GET['article'])) {
        		$something = sql('SELECT * FROM presse WHERE id=?', array(intval($_GET['article'])));
                $something->setFetchMode(PDO::FETCH_BOTH);
                $data = $something->fetch();
                echo show_article($data);
        	}
        	else {
        		$filter_cat = (isset($_GET['idp'])) ? ' WHERE id_collection='.$_GET['idp']: '';
				$date_begin = (isset($_POST['date_begin'])) ? $_POST['date_begin'] : '1900-01-01';
				$date_end = (isset($_POST['date_end'])) ? $_POST['date_end'] : date('Y-m-d', time());
		        echo'<form method="post" class="d-print-none">
		            <label>Collection</label>
		            <select class="form-select" name="collection">';
		                $id_collection = (isset($_GET['idp'])) ? $_GET['idp'] : 0;
		                $query = $db->query('SELECT * FROM collections ORDER BY id');
		                while($data = $query->fetch()) {
				            $selected = ((int)$data['id'] == (int)$id_collection) ? 'selected' : '';
				          	echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
						}
		            echo '</select>
		            <label>Date de début</label><input type="date" name="date_begin" value="'.$date_begin.'" class="form-control" />
		            <label>Date de fin</label><input type="date" name="date_end" value="'.$date_end.'" class="form-control" />
		            <label>Journaux</label>       
		            <select class="form-select" multiple aria-label="multiple select" name="journal[]">';
		                    $journal = (isset($_POST['journal'])) ? $_POST['journal'] : array();
		                    $query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse '.$filter_cat.' ORDER BY journal');
		                    while($data = $query->fetch()) {
				               	$selected = (in_array($data['journal'], $journal)) ? 'selected' : '';
				          		echo '<option value="'.$data['journal'].'" '.$selected.'>'.$data['journal'].'</option>';
		              		}
		           echo ' </select>
		            <label>Catégories</label>
		            <select class="form-select" multiple aria-label="multiple select" name="category[]">';
		                $category = (isset($_POST['category'])) ? $_POST['category'] : array();
		                $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse '.$filter_cat.' ORDER BY category');
		                $data = $query->fetchAll(PDO::FETCH_COLUMN);
		                foreach(tags($data) as $cat) {
		                	$selected = (in_array($cat, $category)) ? 'selected' : '';
		                	echo '<option value="'.$cat.'" '.$selected.'>'.$cat.'</option>';
		                }
		           
		            echo '</select>
		            <label>Mot-clé</label> <input type="search" name="search" class="form-control"/>
		            <input type="submit" name="submit" value="Exécuter" class="btn btn-primary mb-3"/>
		            <input type="submit" name="submit_file" value="Exporter" class="btn btn-primary mb-3"/>
		            </form>';
		    if(isset($_POST['submit']) OR isset($_GET['htmlview']) OR isset($_POST['submit_file'])) {
		    	if(isset($_GET['htmlview'])) {
		    		$_POST['collection'] = $_GET['idp'];
		    	}
		    	  $period = (isset($_POST['date_begin']) AND isset($_POST['date_end'])) ? 'date >= date("'.$_POST['date_begin'].'") AND date <= date("'.$_POST['date_end'].'")' : '';
		        $journaux = '';
		        if(isset($_POST['journal'])) {
		            $journaux .= 'AND (';
		            foreach($_POST['journal'] as $journal) {
		                $journaux .= 'journal LIKE "'.$journal.'" OR ';
		            }
		            $journaux = substr($journaux, 0, -4);
		            $journaux .=')';
		        }

		        $categories = '';
		        if(isset($_POST['category'])) {
		            $categories .= 'AND (';
		            foreach($_POST['category'] as $category) {
		            	$cat_array = explode(" ", $category);
		            	foreach($cat_array as $cat) {
		                	$categories .= ' category LIKE "%'.$cat.'%" OR ';
		                }
		            }
		            $categories = substr($categories, 0, -4);
		            $categories .=')';
		        }
		        $search = '';
		        if(isset($_POST['search']) AND $_POST['search'] != '') {
		            $search .= "AND (title LIKE '%".$_POST['search']."%' OR content LIKE '%".$_POST['search']."%')"; 
		        }
		        $collection = (($period != '') ? 'AND ' : '').'id_collection='.$_POST['collection'];
		        $sql = 'SELECT * FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' ORDER BY date';
		        echo '<h1>Corpus de la revue de presse (généré le '.date('c', time()).')</h1>';
		        echo '<a href="javascript:window.print()">Imprimer</a>';
		        echo '<p><code>'.$sql.'</code></p>';
		        $query = $db->prepare($sql);
		        $query->execute();
		        $count = 0;
		        $html_output = '';
		        $raw_txt = '';
		        $export = array();
		        $export[] = array('date', 'year', 'month', 'journal', 'category', 'title', 'content', 'source');
		       
		        while($data = $query->fetch()) {
		        	$html_output .= show_article($data);
		            $export[] = array($data['date'], date('Y', strtotime($data['date'])), date('Y-m', strtotime($data['date'])),$data['journal'], $data['category'], $data['title'], $data['content'], $data['source']);
		            $raw_txt .= $data['title'].' '.$data['content'].' ';
		            $count++;
		        }
		        // STATISTIQUES
		        $sql_day = 'SELECT strftime(\'%Y-%m-%d\', date) as dates, count(*) as nb FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' GROUP BY strftime(\'%Y-%m-%d\', date);';
				$sql_month = 'SELECT strftime(\'%Y-%m\', date) as dates, count(*) as nb FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.'  '.$collection.' GROUP BY strftime(\'%Y-%m\', date);';
				$sql_year = 'SELECT strftime(\'%Y\', date) as dates, count(*) as nb FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' GROUP BY strftime(\'%Y\', date);';
				$sql_categories = 'SELECT category, count(*) as nb FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' GROUP BY category ORDER BY nb DESC';
				$sql_journaux = 'SELECT journal, count(*) as nb FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' GROUP BY journal ORDER BY nb DESC';
				$stats_output = '<h1>Occurences par jour</h1>
				<details>
					<summary>Details</summary>
					<table class="table"><tr><th>Dates</th><th>nb</th></tr>';
					$stats_output .= '<p><code>'.$sql_day.'</code></p>';
					$query = $db->query($sql_day);
					$chart_d = array();
					$chart_d['data']['label'] = 'Jour';
					while($data = $query->fetch()) {
					 $chart_d['category'][] = $data['dates'];
					 $chart_d['data']['data'][] = $data['nb'];
						$stats_output .= '<tr><td>'.$data['dates'].'</td><td>'.$data['nb'].'</td></tr>';
					}
				$stats_output .= '</table></details>';
				
				$stats_output .= quickchart('line', $chart_d['category'], array($chart_d['data']));
				$stats_output .= '<h1>Occurences par mois</h1>
				<details>
					<summary>Details</summary>
				<table class="table"><tr><th>Dates</th><th>nb</th></tr>';
				$stats_output .= '<p><code>'.$sql_month.'</code></p>';
				
				$query = $db->query($sql_month);
				$chart_d = array();
				$chart_d['data']['label'] = 'Mois';
				while($data = $query->fetch()) {
					 $chart_d['category'][] = $data['dates'];
					 $chart_d['data']['data'][] = $data['nb'];
					 $stats_output .= '<tr><td>'.$data['dates'].'</td><td>'.$data['nb'].'</td></tr>';
				}
				$stats_output .= '</table>
				</details>';
				$stats_output .= quickchart('line',$chart_d['category'], array($chart_d['data']));
				$stats_output .= '<h1>Occurences par année</h1>
				<details>
					<summary>Details</summary>
					<table class="table"><tr><th>Dates</th><th>nb</th></tr>';
					$stats_output .= '<p><code>'.$sql_year.'</code></p>';
					$query = $db->query($sql_year);
					$chart_d = array();
					$chart_d['data']['label'] = 'Année';
					while($data = $query->fetch()) {
					 $chart_d['category'][] = $data['dates'];
					 $chart_d['data']['data'][] = $data['nb'];
						$stats_output .= '<tr><td>'.$data['dates'].'</td><td>'.$data['nb'].'</td></tr>';
					}
				$stats_output .= '</table></details>';
				$stats_output .= quickchart('line', $chart_d['category'], array($chart_d['data']));
				/*
				$stats_output .= '<h1>Catégories</h1>
				<details>
				<summary>Details</summary>
				<table class="table"><tr><th>Catégorie</th><th>nb</th></tr>';
				$stats_output .= '<p><code>'.$sql_categories.'</code></p>';
				$query = $db->query($sql_categories);
				$chart_cat = array();
				$chart_cat['data']['label'] = 'Catégories';
				while($data = $query->fetch()) {
					$chart_cat['category'][] = $data['category'];
					$chart_cat['data']['data'][] = $data['nb'];
					$stats_output .= '<tr><td>'.$data['category'].'</td><td>'.$data['nb'].'</td></tr>';
				}
				$stats_output .= '</table></details>';
				$stats_output .= quickchart('bar', $chart_cat['category'], array($chart_cat['data']));
				*/
				$stats_output .= '<h1>Journaux</h1>
						<details>
						<summary>Details</summary>
						<table class="table"><tr><th>Journal</th><th>nb</th></tr>';
						$stats_output .= '<p><code>'.$sql_journaux.'</code></p>';
						$query = $db->prepare($sql_journaux);
						$query->execute();
						$chart_journaux = array();
						$chart_journaux['data']['label'] = 'Journaux';
						while($data = $query->fetch()) {
							$chart_journaux['category'][] = $data['journal'];
							$chart_journaux['data']['data'][] = $data['nb'];
							$stats_output .= '<tr><td>'.$data['journal'].'</td><td>'.$data['nb'].'</td></tr>';
						}
						$stats_output .= '</table></details>';
				$stats_output .= quickchart('pie', $chart_journaux['category'], array($chart_journaux['data']));
				 
				 if(isset($_POST['submit_file'])) {
		            $filename = date('Y-m-d-His', time());
		            mkdir('data/export/'.$filename);
		            file_put_contents('data/export/'.$filename.'/query.txt', $sql);
		            exportcsv($export, 'data/export/'.$filename.'/presse.csv');
		            exportiramuteq($export, 'data/export/'.$filename.'/presse_iramuteq.txt');
		            file_put_contents('data/export/'.$filename.'/presse_raw.txt', $raw_txt);
		            ZipDir('data/export/'.$filename.'/export-'.$filename.'.zip', 'data/export/'.$filename);
		           //redirect('data/export/'.$filename.'.zip');
		            
		            echo '
		            <a href="data/export/'.$filename.'/export-'.$filename.'.zip" class="btn btn-primary"><i class="fa fa-file-archive-o" aria-hidden="true"></i> Archive (zip)</a> 
		            <a href="data/export/'.$filename.'/query.txt" class="btn btn-primary"><i class="fa fa-database" aria-hidden="true"></i> Requête SQL</a> 
		            <a href="data/export/'.$filename.'/presse_iramuteq.txt" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Fichier Iramuteq</a> 
		            <a href="data/export/'.$filename.'/presse.csv" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Fichier CSV</a> 
		            <a href="data/export/'.$filename.'/presse_raw.txt" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Texte brut</a>';
		            
		        }
		        else {
		        	echo '<ul class="nav nav-tabs" id="myTab" role="tablist">
				  <li class="nav-item" role="presentation">
					<button class="nav-link active" id="html-tab" data-bs-toggle="tab" data-bs-target="#html-tab-pane" type="button" role="tab" aria-controls="html-tab-pane" aria-selected="true">Visualiser</button>
				  </li>
				  <li class="nav-item" role="presentation">
					<button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-tab-pane" type="button" role="tab" aria-controls="stats-tab-pane" aria-selected="false">Statistiques</button>
				  </li>
				</ul>
				<div class="tab-content" id="myTabContent">
				  <div class="tab-pane fade show active" id="html-tab-pane" role="tabpanel" aria-labelledby="html-tab" tabindex="0">'.$count.' résultat(s)'.$html_output.'</div>
				  <div class="tab-pane fade" id="stats-tab-pane" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">'.$stats_output.'</div>
				</div>';
		        }
		    }
		  }
        break;
        case 'add':
        if(!check(@$_SESSION['login']) AND $_SERVER['SERVER_NAME'] !='localhost') {header('Location: index.php?do=login');}
        	if(isset($_GET['delete']) AND isset($_GET['token']) AND $_GET['token'] == $_SESSION['token'] AND $_GET['token'] !='') {
				sql('DELETE FROM presse WHERE id=?', array($_GET['delete']));
				redirect('index.php');
				
			}
			if(isset($_GET['id']) AND isset($_GET['category'])) {
				sql('UPDATE presse SET category = ? WHERE id=?', array($category.' '.$_GET['category'], $_GET['id']));
				redirect('index.php?do=view&article='.$_GET['id']);
			}
	        if (isset($_GET['id'])) {
	            $something = sql('SELECT * FROM presse WHERE id=?', array(intval($_GET['id'])));
	            $something->setFetchMode(PDO::FETCH_BOTH);
	            $data = $something->fetch();
	            $date = $data['date'];
	            $journal = $data['journal'];
	            $category = $data['category'];
	            $title = $data['title'];
	            $content = $data['content'];
	            $source = $data['source'];
	            $id_collection = $data['id_collection'];
	            $id = $_GET['id'];
	        }
	        elseif(isset($_POST['quick'])) {
	            /*
	            $txtify = file_get_contents('https://txtify.it/'.$_POST['quick']);
	            $txtify_explode = explode("\n",$txtify);  

	            $date = date('Y-m-d', time());
	            $journal = '';
	            $category = '';
	            $title = ucfirst(strtolower($txtify_explode[0]));
	            $content = $txtify;
	            $source = $_POST['quick'];
	            $id = 0;
	        */
	            $graby = new Graby\Graby();
	            $result = $graby->fetchContent($_POST['quick']);
	            //print_r($result);
	            $date = date('Y-m-d', strtotime($result['date']));
	            $journal = parse_url($_POST['quick'], PHP_URL_HOST);
	            $category = '';
	            $title = $result['title'];
	            $content = strip_tags($result['html']);
	            $source = $_POST['quick'];
	            $id_collection = (isset($_POST['idp'])) ? $_POST['idp'] : 0;
	            $id = 0;
	        }
	        else {
	            $date = date('Y-m-d', time());
	            $journal = '';
	            $category = '';
	            $title = '';
	            $content = '';
	            $source = '';
	            $id_collection = 0;
	            $id = 0;
	        }

	        if (isset($_POST['content'])) {
	            echo "Ajout dans la base de données";
	            //si id_member = 0 on insert sinon on update
	            if ($_POST['id'] == 0) {
	                echo "nouvelle donnée";
	                sql('INSERT INTO presse (date, journal, category, title, content, source, id_collection) VALUES (?, ?, ?, ?, ?, ?, ?)', array($_POST['date'], $_POST['journal'],$_POST['category'],$_POST['title'],$_POST['content'], $_POST['source'], $_POST['collection']));
	            } else {
	                sql('UPDATE presse SET date = ?, journal = ?, category = ?, title=?, content = ?, source = ?, id_collection=? WHERE id=?', array($_POST['date'], $_POST['journal'], $_POST['category'], $_POST['title'], $_POST['content'], $_POST['source'], $_POST['collection'], $_POST['id']));
	            }
	            header('Location: index.php?do=view&id='.$_POST['id'].'');
	        }
	    echo '<form method="post" action="?do=add" class="d-print-none">
	       <label>Ajout rapide</label> <input type="url" name="quick" class="form-control"/>
	       <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	    </form>';
	   echo '<form method="post" action="?do=add" class="d-print-none">
	        <label>Collection</label>
	        <select class="form-select" name="collection">';
	            $id_collection = (isset($_GET['idp'])) ? $_GET['idp'] : 0;
	            $query = $db->query('SELECT * FROM collections ORDER BY id');
	            while($data = $query->fetch()) {
			        $selected = ((int)$data['id'] == (int)$id_collection) ? 'selected' : '';
			      	echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
	          	}
	           echo '</select>
	            <label>Titre</label> <input type="text" name="title" value="'.$title.'" class="form-control"/>
	            <label>Contenu</label><textarea class="form-control" name="content" rows="5">'.$content.'</textarea>
	            <label>Date de publication</label><input type="date" value="'.$date.'" name="date" value="" class="form-control" />
	            <label>Source</label> <input type="text" name="source" value="'.$source.'" class="form-control"/>
	            <label>Journal</label>       
	            <input type="text" name="journal" list="journal" value="'.$journal.'" class="form-control"/>
	            <datalist id="journal">';
	              $query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse ORDER BY journal');
	              while($data = $query->fetch()) {
	              	echo '<option value="'.$data['journal'].'" />';
	              }
	            echo '</datalist>
	            <label>Catégorie</label>
	            <input type="text" name="category" list="category" value="'.$category.'" class="form-control"/>
	            <datalist id="category">';
	             $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse ORDER BY category');
	             $data = $query->fetchAll(PDO::FETCH_COLUMN);
                foreach(tags($data) as $cat) {
                	echo '<option value="'.$cat.'">'.$cat.'</option>';
                }
	           echo '</datalist>
	            <input type="hidden" name="id" id="id" value="'.$id.'"/>
	            <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	            </form>';
        break;
        case 'tools':
        	echo '<h1>Renommer les journaux</h1>
        	<form method="post" action="?do=tools" class="d-print-none">
	        <label for="journal">Journaux</label>
	        <select class="form-select" multiple aria-label="multiple select" name="journal[]" style="height:50em">';
		                    $journal = (isset($_POST['journal'])) ? $_POST['journal'] : array();
		                    $query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse ORDER BY journal');
		                    while($data = $query->fetch()) {
				               	$selected = (in_array($data['journal'], $journal)) ? 'selected' : '';
				          		echo '<option value="'.$data['journal'].'" '.$selected.'>'.$data['journal'].'</option>';
		              		}
		    echo ' </select>
		    <label for="newname_journal">Nouveau nom</label>
		    <input type="text" name="newname_journal" id="newname_journal" class="form-control"/>
		    <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	        </form>';
	        if(isset($_POST['newname_journal']) AND $_POST['newname_journal'] != '') {
				 if(isset($_POST['journal'])) {
				        foreach($_POST['journal'] as $journal) {
				            sql("UPDATE presse SET journal =? WHERE journal=?", array($_POST['newname_journal'], $journal));
				        }
				    }
				header('Location: index.php?do=tools');
		     }
		     echo '<h1>Renommer les catégories</h1>
        	<form method="post" action="?do=tools" class="d-print-none">
	        <label for="category">Catégories</label>
	        <select class="form-select" multiple aria-label="multiple select" name="category[]" style="height:50em">';
		                    $category = (isset($_POST['category'])) ? $_POST['category'] : array();
		                    $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse ORDER BY category');
				            $data = $query->fetchAll(PDO::FETCH_COLUMN);
				            foreach(tags($data) as $cat) {
				            	echo '<option value="'.$cat.'">'.$cat.'</option>';
				            }
		                
		    echo ' </select>
		    <label for="newname_category">Nouveau nom</label>
		    <input type="text" name="newname_category" id="newname_category" class="form-control"/>
		    <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	        </form>';
	        if(isset($_POST['newname_category']) AND $_POST['newname_category'] != '') {
				 if(isset($_POST['category'])) {
				        foreach($_POST['category'] as $category) {
				        	//sql("UPDATE presse SET category =REPLACE(category, '".$category."', '".$_POST['newname_category']."') WHERE category LIKE '%$category%'");
				           sql("UPDATE presse SET category =REPLACE(category, :old, :new) WHERE category LIKE '%$category%'", array('old'=>$category, 'new'=>$_POST['newname_category']));
				        }
				    }
				header('Location: index.php?do=tools');
		     }
		     echo '<h1>Doublons</h1>';
		     $query_collection = $db->query('SELECT * FROM collections ORDER BY id');
            while($collections = $query_collection->fetch()) {
				 $query = $db->query('SELECT COUNT(*) AS nbr_doublon, title FROM  presse WHERE id_collection='.$collections['id'].' GROUP BY title HAVING COUNT(*) > 1');
				 echo '<h2>'.$collections['name'].'</h2>';
				 echo '<ol>';
		        while($data = $query->fetch()) {
		      		echo '<li>'.$data['title'].'('.$data['nbr_doublon'].')</li>';
		  		}
		  		echo '</ol>';
		  		echo '<a href="?do=tools&delete_doublon=yes&id_collection='.$collections['id'].'" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer les doublons</a>';
		  	}
      		if(isset($_GET['delete_doublon'])) {
      			sql('DELETE FROM presse WHERE id NOT IN  (SELECT MAX(id) FROM presse GROUP BY title) WHERE id_collection='.$_GET['id_collection'].';');
      		}
        break;
        case 'import':
        	if(!check(@$_SESSION['login']) AND $_SERVER['SERVER_NAME'] !='localhost') {header('Location: index.php?do=login');}
			if(isset($_POST['collection'])) {
			       $target = 'data/import/'.basename($_FILES["file"]["name"]);
			        move_uploaded_file($_FILES["file"]["tmp_name"], $target);

					/*
			        $csv = array_map('str_getcsv', file($target));
			        array_walk($csv, function(&$a) use ($csv) {
			          $a = array_combine($csv[0], $a);
			        });
			        array_shift($csv);
			        //print_r($csv);
					
			        foreach($csv as $article) {
			            sql('INSERT INTO presse (date, journal, title, content, source, id_collection) VALUES (?,  ?, ?, ?, ?, ?)', array($article['date'], $article['journal'],$article['title'],$article['content'], $article['source'], $_POST['collection']));
			        }
			        
			        */
			        if(mime_content_type($target) == 'text/csv') {
					    if (($handle = fopen($target, "r")) !== FALSE) {
							while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
								if($data['3'] != 'journal') {
									$category = ($data['4'] == '') ? 'à_trier' : $data['4'];
									sql('INSERT INTO presse (date, journal, title, content, source, category, id_collection) VALUES (?,  ?, ?, ?, ?, ?, ?)', array($data['0'], $data['3'],$data['5'],$data['6'],$data['7'], $category, $_POST['collection']));
								}
							}
							fclose($handle);
						}
			        }
			        elseif(mime_content_type($target) == 'text/html')  {
					    $dom = HtmlDomParser::file_get_html($target);
						$list_articles = array();
						foreach($dom->find('article') as $article) {
							$date_str = (string)$article->find('.DocHeader')->plaintext[0];
							$journal_str = str_replace(array('(',')','-','/',"'",';',':'), '', (string)$article->find('.DocPublicationName')->plaintext[0]);
							$journal_str = str_replace(' ', '_', $journal_str);
							$journal_str = strtolower($journal_str);
							$date =  europresse2standardtime($date_str)['formated'];
							$title = (string)$article->find('.titreArticleVisu')->plaintext[0];
							$content = (string)$article->find('.docOcurrContainer')->plaintext[0];
							sql('INSERT INTO presse (date, journal, title, content, source, category, id_collection) VALUES (?,  ?, ?, ?, ?, ?, ?)',
							array($date, $journal_str,$title,$content,'Europresse', 'à_trier', $_POST['collection']));
						}
			        }
			        else {
			        	echo '<div class="alert alert-warning" role="alert">Le fichier n’est pas au bon format !</div>';
			        }
			}
			echo'<form method="post" action="?do=import" enctype="multipart/form-data" class="d-print-none">
			    <label>Collection</label>
			    <select class="form-select" name="collection">';
			        $id_collection = (isset($_GET['idp'])) ? $_GET['idp'] : 0;
			        $query = $db->query('SELECT * FROM collections ORDER BY id');
			        while($data = $query->fetch()) {
			        	$selected = ((int)$data['id'] == (int)$id_collection) ? 'selected' : '';
			      		echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
			      }
			    echo'</select>
			   <label>Importer un fichier Europresse</label> <input type="file" name="file" id="file" class="form-control"/>
			   <p class="form-text">Le fichier doit être préalablement converti à l’aide du <a href="https://framagit.org/justinmponcet/presse/-/blob/main/euro2csv.py">convertisseur</a> idoine au format CSV.</p>

			   <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
			</form>';
        break;
        case 'admin':
        if((!check(@$_SESSION['login']) AND $_SESSION['permit'] ==1) AND $_SERVER['SERVER_NAME'] !='localhost') {header('Location: index.php?do=login');}
        
			if(isset($_GET['id'])) {
				$something = $db->prepare("SELECT * FROM users WHERE id=?");
				$something->execute(array((int)$_GET['id']));
				$data = $something->fetch();
				$courriel = $data['login'];
				$password = '';
				$id = $_GET['id'];
				$permit = ($data['permit'] == 1) ? 'checked' : '';
			}
			else {
				$courriel = '';
				$id = 0;
				$permit = '';
			}
			if(isset($_GET['delete']) AND isset($_GET['token']) AND $_GET['token'] == $_SESSION['token'] AND $_GET['token'] !='') {
				sql('DELETE FROM users WHERE id=?', array($_GET['delete']));
			}
			if(isset($_POST['login'])) {
				$_POST['permit'] = isset($_POST['permit']) ? 1 : 0;
				if($_POST['id'] > 0) {
					if($_POST['pwd'] != '') {
						sql('UPDATE users SET login = ?, password = ?, permit = ? WHERE id=?', array($_POST['login'], password_hash($_POST['pwd'], PASSWORD_DEFAULT), $_POST['permit'], $_POST['id']));
					}
					else {
						sql('UPDATE users SET login = ?, permit = ? WHERE id=?', array($_POST['login'], $_POST['permit'], $_POST['id']));
					}
				}
				else {
				   sql('INSERT INTO users (login, password, permit) VALUES (?, ?, ?)', array($_POST['login'], password_hash($_POST['pwd'], PASSWORD_DEFAULT), $_POST['permit']));
				}
			}
			echo'<form method="post" action="?do=admin" class="d-print-none">
			    <label>Courriel</label> <input type="text" name="login" value="'.$courriel.'" class="form-control"/>
			    <label>Phrase de passe</label> <input type="password" name="pwd" class="form-control"/>
			    <div class="form-check"><input type="checkbox" name="permit" class="form-check-input" '.$permit.'/><label class="form-check-label">Droits administrateur</label></div>
			    <input type="hidden" name="id" value="'.$id.'"/>
			   <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
			</form>';
			
			    echo '<table class="table">
			            <tr><th>Utilisateurs</th><th>Permissions</th><th>Actions</th></tr>';
			    $query = $db->query('SELECT * FROM users ORDER BY id');
			    while($data = $query->fetch()) {
			    	$permit = ($data['permit'] > 0) ? 'Administrateur⋅trice' : 'Utilisateur⋅trice';
			        echo '<tr><td>'.$data['login'].'</td>
			        		<td>'.$permit.'</td>
			            <td>
			                <a href="?do=admin&id='.$data['id'].'" class="btn btn-primary"><i class="fa fa-pencil" aria-hidden="true"></i></a>
			                <a href="?do=admin&delete='.$data['id'].'&token='.$_SESSION['token'].'" class="btn btn-danger"  onclick="return confirm(\'Êtes-vous sûr ?\');"><i class="fa fa-trash" aria-hidden="true"></i></a>
			            </td>
			        </tr>';
			    }
			    echo '</table>';
			    echo '<a href="data/data.db" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Télécharger la base de données</a>';
        break;
        case 'login':
           if(check(@$_SESSION['login'])) {
                unset($_SESSION['login']);
                unset($_SESSION['permit']);
                unset($_SESSION['token']);
                session_destroy();
                header('Location: index.php');
                exit();
            }
            if (isset($_POST['pwd'])) {
                $something = $db->prepare("SELECT * FROM users WHERE login=?");
				$something->execute(array($_POST['login']));
				$data = $something->fetch();
                if (password_verify($_POST['pwd'], $data['password']) AND $_POST['login'] == $data['login']) {
                    $_SESSION['login'] = true;
                    $_SESSION['permit'] = (int)$data['permit'];
                    $_SESSION['token'] = sha1(microtime());
                    header('Location: index.php');
                } else {
                    $_SESSION['login'] = false;
                    echo '<div class="alert alert-warning" role="alert">Erreur : mauvais mot de passe</div>';
                }
            }
        echo'<form method="post" action="?do=login" class="d-print-none">
            <label>Courriel</label> <input type="text" name="login" class="form-control"/>
            <label>Phrase de passe</label> <input type="password" name="pwd" class="form-control"/>
           <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
        </form>';
        break;
    }
}
else {
    header('Location: index.php?do=collections');
}
?>
</main>
<footer><a href="https://framagit.org/justinmponcet/pressdb">Code source</a></footer>
</body>
</html>
