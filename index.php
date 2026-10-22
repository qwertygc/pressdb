<?php
include 'inc.php';
?>
<!doctype html>
<html>
<head>
<title>Presse</title>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<link href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="vendor/nnnick/chartjs/dist/chart.umd.js" onerror="var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/chart.js@4';document.head.appendChild(s);"></script>
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
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Menu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="index.php"><i class="fa fa-database" aria-hidden="true"></i> Base de données</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=collections"><i class="fa fa-book" aria-hidden="true"></i> Liste des collections</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=view"><i class="fa fa-eye" aria-hidden="true"></i> Voir</a>
      </li>
      <li class="nav-item d-flex align-items-center ms-2">
        <form method="get" action="index.php" class="d-flex" id="nav-collection-form">
          <input type="hidden" name="do" value="view"/>
          <select name="idp" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Collection">
            <option value="">Toutes les collections</option>
<?php
$collections_nav = sql('SELECT c.id, c.name, (SELECT COUNT(*) FROM presse p WHERE p.id_collection = c.id AND p.category = ?) as a_trier FROM collections c ORDER BY c.name', array('à_trier'));
while($col_nav = $collections_nav->fetch()):
  $sel = ((int)@$_GET['idp'] === (int)$col_nav['id']) ? ' selected' : '';
?>
            <option value="<?= (int)$col_nav['id'] ?>"<?= $sel ?>><?= h($col_nav['name']) ?><?= ((int)$col_nav['a_trier'] > 0) ? ' ('.(int)$col_nav['a_trier'].' à trier)' : '' ?></option>
<?php endwhile; ?>
          </select>
          <noscript><button type="submit" class="btn btn-sm btn-outline-secondary ms-1">Aller</button></noscript>
        </form>
      </li>
      <?php  if(is_logged()) : ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=add"><i class="fa fa-pencil" aria-hidden="true"></i> Ajouter un article</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=tools"><i class="fa fa-wrench" aria-hidden="true"></i> Outils</a>
      </li>
	<li class="nav-item">
        <a class="nav-link" href="index.php?do=import"><i class="fa fa-upload" aria-hidden="true"></i> Importer</a>
      </li>
      <?php if(is_admin()): ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=admin"><i class="fa fa-users" aria-hidden="true"></i> Administration</a>
      </li>
      <?php endif; ?>
    <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=login"><i class="fa fa-sign-in" aria-hidden="true"></i> <?php echo (!check(@$_SESSION['login'])) ? 'Connexion': 'Déconnexion'; ?></a>
      </li>
    </ul>
    </div>
</nav>
<main>
<?php

if(isset($_GET['do'])) {
    switch($_GET['do']) {
        case 'collections':
        if(!is_logged()) {redirect('index.php?do=login');}
        if(isset($_GET['delete']) AND csrf_ok('GET')) {
        	sql('DELETE FROM collections WHERE id=?', array(intval($_GET['delete'])));
        	sql('DELETE FROM presse WHERE id_collection=?', array(intval($_GET['delete'])));
        }
        
        echo '<form method="post" class="d-print-none" action="?do=collections">
               '.csrf_field().'
               <label>Nom de la collection</label><input type="text" name="collection_name" class="form-control" />
               <input type="submit" class="btn btn-primary mb-3"/>
                </form>';
            if(isset($_POST['collection_name']) AND csrf_ok('POST')) {
                sql('INSERT into collections (name) VALUES (?)', array($_POST['collection_name']));
            }
            echo '<table class="table">
                    <tr><th>Collections</th><th>Actions</th></tr>';
            $query = $db->query('SELECT * FROM collections ORDER BY id');
            while($data = $query->fetch()) {
                echo '<tr><td><a href="?do=view&idp='.intval($data['id']).'">'.h($data['name']).'</a></td>
                    <td>
                    <div class="btn-group" role="group" aria-label="Basic example">
                        <a href="?do=view&idp='.intval($data['id']).'" class="btn btn-primary btn-sm"><i class="fa fa-eye" aria-hidden="true"></i> Voir</a>
                        <a href="?do=view&sort='.intval($data['id']).'" class="btn btn-primary btn-sm"><i class="fa fa-sort"></i> Trier</a>
                        <a href="?do=add&idp='.intval($data['id']).'" class="btn btn-primary btn-sm"><i class="fa fa-pencil" aria-hidden="true"></i> Ajouter un article</a>
                        <a href="?do=tools&idp='.intval($data['id']).'" class="btn btn-primary btn-sm"><i class="fa fa-wrench" aria-hidden="true"></i> Configurer</a>
                        <form method="post" action="?do=collections" style="display:inline" onsubmit="return confirm(\'Êtes-vous sûr ?\');">'.csrf_field().'<input type="hidden" name="delete" value="'.intval($data['id']).'"/><button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</button></form>
                    </div>
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
                $_GET['idp'] = $data['id_collection'];
                echo show_article($data, false);
        	}
        	elseif(isset($_GET['sort'])) {
        			$something = sql('SELECT count(*) as total FROM presse WHERE id_collection=? AND category="à_trier"', array(intval($_GET['sort'])));
			        $something->setFetchMode(PDO::FETCH_BOTH);
			        $count = $something->fetch();
        			$something = sql('SELECT * FROM presse WHERE id_collection=? AND category="à_trier" ORDER BY date ASC LIMIT 1', array(intval($_GET['sort'])));
			        $something->setFetchMode(PDO::FETCH_BOTH);
			        $data = $something->fetch();
			        $_GET['idp'] = $_GET['sort'];
			        echo '<p class="alert alert-light">Article(s) restant(s) : '.$count['total'].'</p>';
			        if($count['total'] >0) {
			       		echo show_article($data);
			        }
        	}
        	else {
        		if(!isset($_GET['collection']) AND isset($_GET['idp'])) {$_GET['collection'] = $_GET['idp'];}
        		if(isset($_GET['collection']) AND intval($_GET['collection']) > 0) {$_GET['idp'] = $_GET['collection'];}
        		$filter_cat = (isset($_GET['idp']) AND intval($_GET['idp']) > 0) ? ' WHERE id_collection='.intval($_GET['idp']): '';
				$date_begin = (isset($_GET['date_begin'])) ? $_GET['date_begin'] : '1900-01-01';
				$date_end = (isset($_GET['date_end'])) ? $_GET['date_end'] : date('Y-m-d', time());
$active_collection = (isset($_GET['collection']) AND intval($_GET['collection']) > 0) ? intval($_GET['collection']) : ((isset($_GET['idp']) AND intval($_GET['idp']) > 0) ? intval($_GET['idp']) : 0);
$active_badges = array();
if($active_collection > 0) {
	$cname = sql('SELECT name FROM collections WHERE id = ?', array($active_collection))->fetch();
	$active_badges[] = array('key' => 'collection', 'label' => ($cname ? h($cname['name']) : '#'.$active_collection));
}
if(!empty($_GET['date_begin']) AND $_GET['date_begin'] != '1900-01-01') {$active_badges[] = array('key' => 'date_begin', 'label' => 'dès '.h($_GET['date_begin']));}
if(!empty($_GET['date_end']) AND $_GET['date_end'] != date('Y-m-d')) {$active_badges[] = array('key' => 'date_end', 'label' => 'jusqu\'à '.h($_GET['date_end']));}
foreach((array)@$_GET['journal'] as $j) {$active_badges[] = array('key' => 'journal', 'label' => h($j));}
foreach((array)@$_GET['category'] as $c) {$active_badges[] = array('key' => 'category', 'label' => h($c));}
if(!empty($_GET['search'])) {$active_badges[] = array('key' => 'search', 'label' => '« '.h($_GET['search']).' »');}

echo '<div class="mb-3 d-print-none">';
if(count($active_badges) > 0) {
	echo '<p class="mb-1"><strong>Filtres actifs :</strong> ';
	foreach($active_badges as $b) {echo '<span class="badge text-bg-secondary">'.$b['label'].'</span> ';}
	echo '<a class="btn btn-sm btn-outline-secondary" href="index.php?do=view">Tout effacer</a>';
	echo '</p>';
}
echo '<div class="collapse" id="filterPanel">';
echo '<form method="get" class="card card-body bg-light">';
echo '<input type="hidden" name="do" value="view"/>';
echo '<div class="row g-2"><div class="col-md-4"><label class="form-label">Collection</label>';
echo '<select class="form-select" name="collection">';
$query = $db->query('SELECT * FROM collections ORDER BY name');
echo '<option value="">Toutes</option>';
while($data = $query->fetch()) {
	$selected = ((int)$data['id'] === $active_collection) ? ' selected' : '';
	echo '<option value="'.intval($data['id']).'"'.$selected.'>'.h($data['name']).'</option>';
}
echo '</select></div>';
echo '<div class="col-md-4"><label class="form-label">Date de début</label><input type="date" name="date_begin" value="'.h($date_begin).'" class="form-control"/></div>';
echo '<div class="col-md-4"><label class="form-label">Date de fin</label><input type="date" name="date_end" value="'.h($date_end).'" class="form-control"/></div>';
echo '</div>';
echo '<div class="row g-2 mt-1"><div class="col-md-6"><label class="form-label">Journaux</label>';
echo '<select class="form-select" multiple size="5" aria-label="multiple select" name="journal[]">';
$journal = (isset($_GET['journal'])) ? $_GET['journal'] : array();
$query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse '.$filter_cat.' ORDER BY journal');
while($data = $query->fetch()) {
	$selected = (in_array($data['journal'], $journal)) ? 'selected' : '';
	echo '<option value="'.h($data['journal']).'" '.$selected.'>'.h($data['journal']).'</option>';
}
echo ' </select></div>';
echo '<div class="col-md-6"><label class="form-label">Catégories</label>';
echo '<select class="form-select" multiple size="5" aria-label="multiple select" name="category[]">';
$category = (isset($_GET['category'])) ? $_GET['category'] : array();
foreach(categories_list(($active_collection > 0) ? $active_collection : null) as $cat) {
	$selected = (in_array($cat, $category)) ? 'selected' : '';
	echo '<option value="'.h($cat).'" '.$selected.'>'.h($cat).'</option>';
}
echo '</select></div></div>';
echo '<div class="row g-2 mt-1 align-items-end"><div class="col-md"><label class="form-label">Mot-clé</label>';
echo '<input type="search" name="search" value="'.h(@$_GET['search']).'" class="form-control"/></div>';
echo '<div class="col-md-auto"><button type="submit" name="submit" value="1" class="btn btn-primary"><i class="fa fa-play" aria-hidden="true"></i> Exécuter</button> ';
echo '<button type="submit" name="submit_file" value="1" class="btn btn-outline-primary"><i class="fa fa-download" aria-hidden="true"></i> Exporter</button></div></div>';
echo '</form></div></div>';
		    if(isset($_GET['submit']) OR isset($_GET['htmlview']) OR isset($_GET['submit_file'])) {
		    	if(isset($_GET['htmlview'])) {
		    		$_GET['collection'] = intval(@$_GET['idp']);
		    	}
		    	$params = array();
		    	$period = (isset($_GET['date_begin']) AND isset($_GET['date_end'])) ? 'date >= date(?) AND date <= date(?)' : '';
		    	if($period != '') {$params[] = $_GET['date_begin']; $params[] = $_GET['date_end'];}
		        $journaux = '';
		        if(isset($_GET['journal']) AND is_array($_GET['journal']) AND count($_GET['journal']) > 0) {
		            $journaux .= 'AND (';
		            foreach($_GET['journal'] as $journal) {
		                $journaux .= 'journal LIKE ? OR ';
		                $params[] = $journal;
		            }
		            $journaux = substr($journaux, 0, -4);
		            $journaux .=')';
		        }

		        $categories = '';
		        if(isset($_GET['category']) AND is_array($_GET['category']) AND count($_GET['category']) > 0) {
		            $categories .= 'AND id IN (SELECT id_presse FROM presse_categories pc JOIN categories c ON c.id = pc.id_category WHERE c.name IN (';
		            $first = true;
		            foreach($_GET['category'] as $category) {
		            	if(!$first) {$categories .= ', ';}
		            	$categories .= '?';
		            	$params[] = trim($category);
		            	$first = false;
		            }
		            $categories .= '))';
		        }
		        $search = '';
		        if(isset($_GET['search']) AND $_GET['search'] != '') {
		            $search .= 'AND (title LIKE ? OR content LIKE ? OR title LIKE ? OR content LIKE ?)';
		            $params[] = '%'.$_GET['search'].'%';
		            $params[] = '%'.$_GET['search'].'%';
		            $params[] = '%'.no_accents($_GET['search']).'%';
		            $params[] = '%'.no_accents($_GET['search']).'%';
		        }
		        $collection = (($period != '') ? 'AND ' : '').'id_collection=?';
		        $params[] = intval(@$_GET['collection']);
		        $sortable = array('date' => 'date', 'journal' => 'journal', 'title' => 'title');
		        $order_col = (isset($_GET['orderby']) AND isset($sortable[$_GET['orderby']])) ? $sortable[$_GET['orderby']] : 'date';
		        $order_dir = (isset($_GET['orderdir']) AND strtolower($_GET['orderdir']) === 'desc') ? 'DESC' : 'ASC';
		        $per_page = 50;
		        $current_page = (isset($_GET['page'])) ? max(1, intval($_GET['page'])) : 1;
		        $sql_count = 'SELECT count(*) as total FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection;
		        $total_rows = (int)sql($sql_count, $params)->fetch()['total'];
		        $offset = ($current_page - 1) * $per_page;
		        $sql = 'SELECT * FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' ORDER BY '.$order_col.' '.$order_dir.' LIMIT '.$per_page.' OFFSET '.$offset.' ';
		        $export_sql = 'SELECT * FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' ORDER BY '.$order_col.' '.$order_dir.' ';
		        if(isset($_POST['bulk_action']) AND is_logged() AND csrf_ok('POST')) {
		        	$ids = (isset($_POST['bulk_ids']) AND is_array($_POST['bulk_ids'])) ? array_map('intval', $_POST['bulk_ids']) : array();
		        	$ids = array_filter($ids);
		        	if(count($ids) > 0) {
		        		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		        		if($_POST['bulk_action'] == 'delete') {
		        			sql('DELETE FROM presse WHERE id IN ('.$placeholders.')', $ids);
		        			echo '<div class="alert alert-success">'.count($ids).' article(s) supprimé(s).</div>';
		        		}
		        		elseif($_POST['bulk_action'] == 'move') {
		        			sql('UPDATE presse SET id_collection = ? WHERE id IN ('.$placeholders.')', array_merge(array(intval($_POST['bulk_collection'])), $ids));
		        			echo '<div class="alert alert-success">'.count($ids).' article(s) déplacé(s).</div>';
		        		}
		        		elseif($_POST['bulk_action'] == 'categorize') {
		        			$newcats = array_filter(array_map('trim', explode(' ', (string)$_POST['bulk_category'])));
		        			if(count($newcats) > 0) {
		        				foreach($ids as $bid) {article_set_categories($bid, $newcats);}
		        				echo '<div class="alert alert-success">'.count($ids).' article(s) catégorisé(s).</div>';
		        			}
		        		}
		        	}
		        }
		        echo '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">';
		        echo '<h1 class="h4 mb-0">Revue de presse <span class="badge text-bg-primary">'.$total_rows.'</span></h1>';
		        echo '<div class="d-flex gap-2 d-print-none">';
		        echo '<a class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" href="#filterPanel" role="button" aria-expanded="false" aria-controls="filterPanel"><i class="fa fa-filter" aria-hidden="true"></i> Filtres</a> ';
		        echo '<button type="button" id="densityToggle" class="btn btn-sm btn-outline-secondary"><i class="fa fa-list" aria-hidden="true"></i> <span id="densityLabel">Vue détaillée</span></button>';
		        echo '<div class="btn-group btn-sm" role="group" aria-label="Tri">';
		        foreach(array('date' => 'Date', 'journal' => 'Journal', 'title' => 'Titre') as $col => $label) {
		        	$active = ($order_col == $col) ? 'btn-secondary' : 'btn-outline-secondary';
		        	$arrow = ($order_col == $col) ? (($order_dir == 'ASC') ? ' <i class="fa fa-sort-asc" aria-hidden="true"></i>' : ' <i class="fa fa-sort-desc" aria-hidden="true"></i>') : '';
		        	$next = ($order_col == $col AND $order_dir == 'ASC') ? 'desc' : 'asc';
		        	echo '<a class="btn btn-sm '.$active.'" href="'.h($sort_url.'&orderby='.$col.'&orderdir='.$next).'">'.$label.$arrow.'</a>';
		        }
		        echo '</div>';
		        echo '<a class="btn btn-sm btn-outline-primary" href="?do=view&sort='.intval(@$_GET['idp']).'"><i class="fa fa-sort" aria-hidden="true"></i> À trier</a>';
		        echo '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print" aria-hidden="true"></i> Imprimer</button>';
		        echo '</div></div>';
		        echo '<p class="text-muted small mb-2">Généré le '.date('d/m/Y H:i').' — page '.$current_page.' / '.max(1, $total_pages_display).'</p>';
		        $query = sql($sql, $params);
		        $count = 0;
		        $base_qs = $_GET;
		        unset($base_qs['page']);
		        $base_url = 'index.php?'.http_build_query($base_qs);
		        $sort_qs = $base_qs; unset($sort_qs['orderby'], $sort_qs['orderdir']);
		        $sort_url = 'index.php?'.http_build_query($sort_qs);
		        $html_output = '';
		        $raw_txt = '';
		        $export = array();
		        $export[] = array('date', 'year', 'month', 'journal', 'category', 'title', 'content', 'source');
		       
		        $page_rows = $query->fetchAll();
		        $dupe_map = array();
		        if(count($page_rows) > 0) {
		        	$titles = array();
		        	foreach($page_rows as $r) {$titles[] = $r['title'];}
		        	$qs = implode(',', array_fill(0, count($titles), '?'));
		        	$dupes = sql('SELECT lower(trim(title)) as t, count(*) as n FROM presse WHERE lower(trim(title)) IN ('.$qs.') GROUP BY lower(trim(title)) HAVING count(*) > 1', $titles)->fetchAll();
		        	foreach($dupes as $d) {$dupe_map[$d['t']] = (int)$d['n'] - 1;}
		        }
		        foreach($page_rows as $data) {
		        	$data['dupe_count'] = (isset($dupe_map[strtolower(trim((string)$data['title']))])) ? $dupe_map[strtolower(trim((string)$data['title']))] : 0;
		        	$html_output .= show_article($data, true, true);
		        	$count++;
		        }
		        $export_query = sql($export_sql, $params);
		        foreach($export_query->fetchAll() as $data_export) {
		            $export[] = array($data_export['date'], date('Y', strtotime($data_export['date'])), date('Y-m', strtotime($data_export['date'])),$data_export['journal'], $data_export['category'], $data_export['title'], $data_export['content'], $data_export['source']);
		            $raw_txt .= $data_export['title'].' '.$data_export['content'].' ';
		        }
		        if($total_rows == 0) {
		        	$html_output = '<div class="alert alert-info">Aucun article ne correspond à ces critères.</div>';
		        }
		        $pagination_html = paginate_links($base_url, $current_page, $per_page, $total_rows);
		        $total_pages_display = (int)ceil($total_rows / $per_page);
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
					$query = sql($sql_day, $params);
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
				
				$query = sql($sql_month, $params);
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
					$query = sql($sql_year, $params);
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
				$query = sql($sql_categories, $params);
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
						$query = sql($sql_journaux, $params);
						$chart_journaux = array();
						$chart_journaux['data']['label'] = 'Journaux';
						while($data = $query->fetch()) {
							$chart_journaux['category'][] = $data['journal'];
							$chart_journaux['data']['data'][] = $data['nb'];
							$stats_output .= '<tr><td>'.$data['journal'].'</td><td>'.$data['nb'].'</td></tr>';
						}
						$stats_output .= '</table></details>';
				$stats_output .= quickchart('pie', $chart_journaux['category'], array($chart_journaux['data']));
				 
				 if(isset($_GET['submit_file'])) {
		            $filename = date('Y-m-d-His', time());
		            mkdir('data/export/'.$filename);
		            file_put_contents('data/export/'.$filename.'/query.txt', $sql);
		            exportcsv($export, 'data/export/'.$filename.'/presse.csv');
		            exportiramuteq($export, 'data/export/'.$filename.'/presse_iramuteq.txt');
		            file_put_contents('data/export/'.$filename.'/presse_raw.txt', $raw_txt);
		            ZipDir('data/export/'.$filename.'/export-'.$filename.'.zip', 'data/export/'.$filename);
		           //redirect('data/export/'.$filename.'.zip');
		            
		            echo '
		            <a href="api.php?do=export-file&dir='.$filename.'&file=export-'.$filename.'.zip&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-file-archive-o" aria-hidden="true"></i> Archive (zip)</a> 
		            <a href="api.php?do=export-file&dir='.$filename.'&file=query.txt&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-database" aria-hidden="true"></i> Requête SQL</a> 
		            <a href="api.php?do=export-file&dir='.$filename.'&file=presse_iramuteq.txt&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Fichier Iramuteq</a> 
		            <a href="api.php?do=export-file&dir='.$filename.'&file=presse.csv&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Fichier CSV</a> 
		            <a href="api.php?do=export-file&dir='.$filename.'&file=presse_raw.txt&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Texte brut</a>';
		            
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
				  <div class="tab-pane fade show active" id="html-tab-pane" role="tabpanel" aria-labelledby="html-tab" tabindex="0">'
				  .((is_logged()) ? '<form method="post" action="index.php?do=view" class="d-print-none mb-3">'.csrf_field().'<div class="input-group"><span class="input-group-text">Tout <input type="checkbox" onclick="document.querySelectorAll(\'input[name^=bulk_ids]\').forEach(c => c.checked = this.checked)"/></span><select name="bulk_action" class="form-select"><option value="categorize">Catégoriser</option><option value="move">Déplacer</option><option value="delete">Supprimer</option></select><input type="text" name="bulk_category" placeholder="catégories (espaces)" class="form-control"/><select name="bulk_collection" class="form-select">' : '')
				  .((is_logged()) ? implode('', array_map(function($c) {return '<option value="'.intval($c['id']).'">'.h($c['name']).'</option>';}, sql('SELECT id, name FROM collections ORDER BY name', array())->fetchAll())) : '')
				  .((is_logged()) ? '</select><button type="submit" class="btn btn-primary">Appliquer à la sélection cochée</button></div></form>' : '')
				  .$pagination_html.$html_output.((is_logged()) ? '</form>' : '').$pagination_html.'</div>
				  <div class="tab-pane fade" id="stats-tab-pane" role="tabpanel" aria-labelledby="stats-tab" tabindex="0">'.$stats_output.'</div>
				</div>';
		        }
		    }
		  }
        break;
        case 'add':
        if(!is_logged()) {redirect('index.php?do=login');}
        	
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
	        elseif(isset($_POST['quick']) AND !url_allowed($_POST['quick'])) {
	            echo '<div class="alert alert-warning" role="alert">URL non autorisée</div>';
	            $date = date('Y-m-d', time()); $journal = ''; $category = ''; $title = ''; $content = ''; $source = ''; $id_collection = 0; $id = 0;
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
	            $id_collection = (isset($_POST['idp'])) ? intval($_POST['idp']) : 0;
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

	        if (isset($_POST['content']) AND csrf_ok('POST')) {
	            $dupe = sql('SELECT id, title FROM presse WHERE id != ? AND lower(trim(title)) = lower(trim(?)) LIMIT 1', array(intval($_POST['id']), $_POST['title']))->fetch();
	            if($dupe AND @$_POST['force_save'] != 'yes') {
	                echo '<div class="alert alert-warning">Un article au titre identique existe déjà (#'.intval($dupe['id']).' — '.h($dupe['title']).'). <a class="btn btn-sm btn-outline-warning" href="index.php?do=view&article='.intval($dupe['id']).'">Le voir</a></div>';
	            }
	            if(!$dupe OR @$_POST['force_save'] == 'yes') {
	            echo "Ajout dans la base de données";
	            $new_id = intval($_POST['id']);
	            if ($new_id == 0) {
	                sql('INSERT INTO presse (date, journal, category, title, content, source, id_collection) VALUES (?, ?, ?, ?, ?, ?, ?)', array($_POST['date'], $_POST['journal'], '', $_POST['title'], $_POST['content'], $_POST['source'], intval($_POST['collection'])));
	                $new_id = (int)$db->lastInsertId();
	            } else {
	                sql('UPDATE presse SET date = ?, journal = ?, title=?, content = ?, source = ?, id_collection=? WHERE id=?', array($_POST['date'], $_POST['journal'], $_POST['title'], $_POST['content'], $_POST['source'], intval($_POST['collection']), $new_id));
	            }
	            $cats = (isset($_POST['category']) AND is_array($_POST['category'])) ? $_POST['category'] : array();
	            if(isset($_POST['category_new']) AND trim($_POST['category_new']) !== '') {
		            $cats = array_merge($cats, explode(' ', $_POST['category_new']));
	            }
	            article_set_categories($new_id, $cats);
	            redirect('index.php?do=view&article='.$new_id);
	            }
	        }
	    echo '<form method="post" action="?do=add" class="d-print-none">
	       '.csrf_field().'
	       <label>Ajout rapide</label> <input type="url" name="quick" class="form-control"/>
	       <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	    </form>';
	   echo '<form method="post" action="?do=add" class="d-print-none">
	        '.csrf_field().'
	        <label>Collection</label>
	        <select class="form-select" name="collection">';
	            //$id_collection = (isset($_GET['idp'])) ? $_GET['idp'] : 0;
	            $query = $db->query('SELECT * FROM collections ORDER BY id');
	            while($data = $query->fetch()) {
			        $selected = ((int)$data['id'] == (int)$id_collection) ? 'selected' : '';
			      	echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
	          	}
	           echo '</select>
	            <label>Titre</label> <input type="text" name="title" value="'.h($title).'" class="form-control"/>
	            <label>Contenu</label><textarea class="form-control" name="content" rows="5">'.h($content).'</textarea>
	            <label>Date de publication</label><input type="date" value="'.h($date).'" name="date" class="form-control" />
	            <label>Source</label> <input type="text" name="source" value="'.h($source).'" class="form-control"/>
	            <label>Journal</label>       
	            <input type="text" name="journal" list="journal" value="'.h($journal).'" class="form-control"/>
	            <datalist id="journal">';
	              $add_col = (isset($id_collection)) ? ' WHERE id_collection='.intval($id_collection) : '';
	              $query = $db->query('SELECT DISTINCT lower(trim(journal)) as journal FROM presse'.$add_col.' ORDER BY journal');
	              while($data = $query->fetch()) {
	              	echo '<option value="'.h($data['journal']).'" />';
	              }
	            echo '</datalist>
	            <label>Catégories (Ctrl+clic pour en sélectionner plusieurs)</label>
	            <select name="category[]" multiple class="form-control">';
	             $current_cats = article_categories((int)$id);
	             foreach(categories_list(null) as $cat) {
	             	$selected = (in_array($cat, $current_cats)) ? 'selected' : '';
	             	echo '<option value="'.h($cat).'" '.$selected.'>'.h($cat).'</option>';
	             }
	           echo '</select>
	            <label>Nouvelle catégorie (optionnel, séparée par des espaces)</label>
	            <input type="text" name="category_new" value="" class="form-control"/>
	            <input type="hidden" name="id" id="id" value="'.intval($id).'"/>
	            <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	            </form>';
        break;
        case 'tools':
        	if(!is_logged()) {redirect('index.php?do=login');}
        	$idp = (isset($_GET['idp'])) ? ' WHERE id_collection='.intval($_GET['idp']): '';
        	$idp_col = (isset($_GET['idp'])) ? ' WHERE id='.intval($_GET['idp']): '';
        	echo '<h1>Renommer les journaux</h1>
        	<a href="api.php?do=fix-website" data-ajax="true" class="btn btn-primary">Harmoniser les sites web</a>
        	<form method="post" action="?do=tools" class="d-print-none">
	        '.csrf_field().'
	        <label for="journal">Journaux</label>
	        <select class="form-select" multiple aria-label="multiple select" name="journal[]" style="height:50em">';
		                    $journal = (isset($_POST['journal'])) ? $_POST['journal'] : array();
		                    $query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse '.$idp.' ORDER BY journal');
		                    while($data = $query->fetch()) {
				               	$selected = (in_array($data['journal'], $journal)) ? 'selected' : '';
				          		echo '<option value="'.h($data['journal']).'" '.$selected.'>'.h($data['journal']).'</option>';
		              		}
		    echo ' </select>
		    <label for="newname_journal">Nouveau nom</label>
		    <input type="text" name="newname_journal" id="newname_journal" class="form-control"/>
		    <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	        </form>';
	        if(isset($_POST['newname_journal']) AND $_POST['newname_journal'] != '' AND csrf_ok('POST')) {
				 if(isset($_POST['journal'])) {
				        foreach($_POST['journal'] as $journal) {
				            sql("UPDATE presse SET journal =? WHERE journal=?", array($_POST['newname_journal'], $journal));
				        }
				    }
				redirect('index.php?do=tools');
		     }

		     echo '<h1>Renommer les catégories</h1>
        	<form method="post" action="?do=tools" class="d-print-none">
	        '.csrf_field().'
	        <label for="category">Catégories</label>
	        <select class="form-select" multiple aria-label="multiple select" name="category[]" style="height:50em">';
		                    $category = (isset($_POST['category'])) ? $_POST['category'] : array();
		                    foreach(categories_list((isset($_GET['idp']) AND intval($_GET['idp']) > 0) ? intval($_GET['idp']) : null) as $cat) {
			            	$selected = (in_array($cat, $category)) ? 'selected' : '';
			            	echo '<option value="'.h($cat).'" '.$selected.'>'.h($cat).'</option>';
			            }
		                
		    echo ' </select>
		    <label for="newname_category">Nouveau nom</label>
		    <input type="text" name="newname_category" id="newname_category" class="form-control"/>
		    <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	        </form>';
	        if(isset($_POST['newname_category']) AND $_POST['newname_category'] != '' AND csrf_ok('POST')) {
				 if(isset($_POST['category'])) {
				        foreach($_POST['category'] as $category) {
				           sql('UPDATE categories SET name = :new WHERE name = :old', array('old'=>$category, 'new'=>$_POST['newname_category']));
				        }
				        sql('UPDATE presse SET category = (SELECT GROUP_CONCAT(c.name, \' \') FROM presse_categories pc JOIN categories c ON c.id = pc.id_category WHERE pc.id_presse = presse.id GROUP BY pc.id_presse)', array());
				    }
				redirect('index.php?do=tools');
		     }
		     echo '<h1>Doublons</h1>';
		     $query_collection = $db->query('SELECT * FROM collections '.$idp_col.'  ORDER BY id');
            while($collections = $query_collection->fetch()) {
				 $query = sql('SELECT COUNT(*) AS nbr_doublon, title FROM presse WHERE id_collection=? GROUP BY title HAVING COUNT(*) > 1', array($collections['id']));
				 echo '<h2>'.h($collections['name']).'</h2>';
				 echo '<ol>';
		        while($data = $query->fetch()) {
		      		echo '<li>'.h($data['title']).'('.intval($data['nbr_doublon']).')</li>';
		  		}
		  		echo '</ol>';
		  		echo '<form method="post" action="?do=tools" onsubmit="return confirm(\'Êtes-vous sûr ?\');">'.csrf_field().'<input type="hidden" name="delete_doublon" value="yes"/><input type="hidden" name="id_collection" value="'.intval($collections['id']).'"/><button type="submit" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer les doublons</button></form>';
		  	}
      		if(isset($_POST['delete_doublon']) AND is_logged() AND csrf_ok('POST')) {
      			sql('DELETE FROM presse WHERE id NOT IN (SELECT MAX(id) FROM presse GROUP BY title, id_collection) AND id_collection=?', array(intval($_POST['id_collection'])));
      			redirect('index.php?do=tools');
      		}

        echo '<h1>Purger les exports</h1>';
        $export_dirs = glob('data/export/*', GLOB_ONLYDIR);
        $export_count = ($export_dirs === false) ? 0 : count($export_dirs);
        echo '<p>'.$export_count.' dossier(s) d\'export stocké(s) sur le serveur.</p>';
        echo '<form method="post" action="?do=tools" onsubmit="return confirm(\'Supprimer les exports de plus de 30 jours ?\');">'.csrf_field().'<button type="submit" name="purge_exports" value="yes" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i> Purger les exports de plus de 30 jours</button></form>';
        if(isset($_POST['purge_exports']) AND csrf_ok('POST')) {
        	$purged = 0;
        	foreach((array)$export_dirs as $dir) {
        		if(is_dir($dir) AND filemtime($dir) < strtotime('-30 days')) {
        			foreach(glob($dir.'/*') as $f) {if(is_file($f)) {unlink($f);}}
        			if(rmdir($dir)) {$purged++;}
        		}
        	}
        	echo '<div class="alert alert-success">'.$purged.' dossier(s) d\'export purgé(s).</div>';
        }

        echo '<h1>Fusionner deux collections</h1>';
        echo '<form method="post" action="?do=tools" onsubmit="return confirm(\'Fusionner les collections ? Les articles seront déplacés et la collection source supprimée.\');">'.csrf_field();
        echo '<label>Collections à fusionner</label><select class="form-select" name="merge_from">';
        $merge_cols = sql('SELECT id, name FROM collections ORDER BY name', array())->fetchAll();
        foreach($merge_cols as $mc) {echo '<option value="'.intval($mc['id']).'">'.h($mc['name']).'</option>';}
        echo '</select>';
        echo '<label>Dans la collection</label><select class="form-select" name="merge_into">';
        foreach($merge_cols as $mc) {echo '<option value="'.intval($mc['id']).'">'.h($mc['name']).'</option>';}
        echo '</select>';
        echo '<button type="submit" name="merge_collections" value="yes" class="btn btn-primary"><i class="fa fa-compress" aria-hidden="true"></i> Fusionner</button></form>';
        if(isset($_POST['merge_collections']) AND csrf_ok('POST')) {
        	$from = intval($_POST['merge_from']);
        	$into = intval($_POST['merge_into']);
        	if($from > 0 AND $into > 0 AND $from != $into) {
        		sql('UPDATE presse SET id_collection = ? WHERE id_collection = ?', array($into, $from));
        		sql('DELETE FROM collections WHERE id = ?', array($from));
        		echo '<div class="alert alert-success">Collections fusionnées.</div>';
        	}
        	else {
        		echo '<div class="alert alert-warning">Impossible : choisissez deux collections différentes.</div>';
        	}
        }
        break;
        case 'import':
        	if(!is_logged()) {redirect('index.php?do=login');}
			if(isset($_POST['collection']) AND csrf_ok('POST')) {
				$upload_error = (isset($_FILES['file']['error'])) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
				$target = '';
				if($upload_error === UPLOAD_ERR_OK AND is_uploaded_file($_FILES['file']['tmp_name'])) {
					$target = 'data/import/'.basename($_FILES['file']['name']);
					if(!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {$target = '';}
				}
				if($target === '') {
					echo '<div class="alert alert-warning" role="alert">Téléversement impossible (fichier manquant ou trop volumineux)</div>';
				}

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
			        if($target !== '' AND mime_content_type($target) == 'text/csv') {
					    if (($handle = fopen($target, "r")) !== FALSE) {
							while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
								if($data['3'] != 'journal') {
									$category = ($data['4'] == '') ? 'à_trier' : $data['4'];
									sql('INSERT INTO presse (date, journal, title, content, source, category, id_collection) VALUES (?,  ?, ?, ?, ?, ?, ?)', array($data['0'], $data['3'],$data['5'],$data['6'],$data['7'], $category, intval($_POST['collection'])));
									article_set_categories((int)$db->lastInsertId(), array($category));
								}
							}
							fclose($handle);
						}
			        }
			        elseif($target !== '' AND mime_content_type($target) == 'text/html')  {
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
							array($date, $journal_str,$title,$content,'Europresse', 'à_trier', intval($_POST['collection'])));
							article_set_categories((int)$db->lastInsertId(), array('à_trier'));
						}
			        }
			        else {
			        	echo '<div class="alert alert-warning" role="alert">Le fichier n’est pas au bon format !</div>';
			        }
			}
			echo'<form method="post" action="?do=import" enctype="multipart/form-data" class="d-print-none">
			    <label>Collection</label>
			    <select class="form-select" name="collection">';
			        $id_collection = (isset($_GET['idp'])) ? intval($_GET['idp']) : 0;
			        $query = $db->query('SELECT * FROM collections ORDER BY id');
			        while($data = $query->fetch()) {
			        	$selected = ((int)$data['id'] == (int)$id_collection) ? 'selected' : '';
			      		echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
			      }
			    echo'</select>
			   '.csrf_field().'
			   <label>Importer un fichier Europresse</label> <input type="file" name="file" id="file" class="form-control"/>
			   <p class="form-text">Le fichier doit être préalablement converti à l’aide du <a href="https://framagit.org/justinmponcet/presse/-/blob/main/euro2csv.py">convertisseur</a> idoine au format CSV.</p>

			   <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
			</form>';
        break;
        case 'admin':
        if(!is_admin()) {redirect('index.php?do=login');}
        
			if(isset($_GET['id'])) {
				$something = $db->prepare("SELECT * FROM users WHERE id=?");
				$something->execute(array((int)$_GET['id']));
				$data = $something->fetch();
				$courriel = $data['login'];
				$password = '';
				$id = intval($_GET['id']);
				$permit = ($data['permit'] == 1) ? 'checked' : '';
			}
			else {
				$courriel = '';
				$id = 0;
				$permit = '';
			}
			if(isset($_POST['delete']) AND csrf_ok('POST')) {
				sql('DELETE FROM users WHERE id=?', array(intval($_POST['delete'])));
			}
			if(isset($_POST['login']) AND csrf_ok('POST')) {
				$_POST['permit'] = isset($_POST['permit']) ? 1 : 0;
				if(intval($_POST['id']) > 0) {
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
			    '.csrf_field().'
			    <label>Courriel</label> <input type="text" name="login" value="'.h($courriel).'" class="form-control"/>
			    <label>Phrase de passe</label> <input type="password" name="pwd" class="form-control"/>
			    <div class="form-check"><input type="checkbox" name="permit" class="form-check-input" '.$permit.'/><label class="form-check-label">Droits administrateur</label></div>
			    <input type="hidden" name="id" value="'.intval($id).'"/>
			   <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
			</form>';
			
			    echo '<table class="table">
			            <tr><th>Utilisateurs</th><th>Permissions</th><th>Actions</th></tr>';
			    $query = $db->query('SELECT * FROM users ORDER BY id');
			    while($data = $query->fetch()) {
			    	$permit = ($data['permit'] > 0) ? 'Administrateur⋅trice' : 'Utilisateur⋅trice';
			        echo '<tr><td>'.h($data['login']).'</td>
			        		<td>'.$permit.'</td>
			            <td>
			                <a href="?do=admin&id='.intval($data['id']).'" class="btn btn-primary"><i class="fa fa-pencil" aria-hidden="true"></i></a>
			                <form method="post" action="?do=admin" style="display:inline" onsubmit="return confirm(\'Êtes-vous sûr ?\');">'.csrf_field().'<input type="hidden" name="delete" value="'.intval($data['id']).'"/><button type="submit" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i></button></form>
			            </td>
			        </tr>';
			    }
			    echo '</table>';
			    echo '<a href="api.php?do=export-db&token='.h($_SESSION['token']).'" class="btn btn-primary"><i class="fa fa-download" aria-hidden="true"></i> Télécharger la base de données</a>';
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
            if (isset($_POST['pwd']) AND csrf_ok('POST')) {
                if(!login_throttle_ok()) {
                    echo '<div class="alert alert-warning" role="alert">Trop de tentatives, réessayez plus tard</div>';
                }
                else {
                $something = $db->prepare("SELECT * FROM users WHERE login=?");
				$something->execute(array($_POST['login']));
				$data = $something->fetch();
                if ($data AND password_verify($_POST['pwd'], $data['password']) AND $_POST['login'] == $data['login']) {
                    login_throttle_reset();
                    session_regenerate_id(true);
                    $_SESSION['login'] = true;
                    $_SESSION['permit'] = (int)$data['permit'];
                    $_SESSION['token'] = bin2hex(random_bytes(16));
                    redirect('index.php');
                } else {
                    $_SESSION['login'] = false;
                    login_throttle_fail();
                    echo '<div class="alert alert-warning" role="alert">Erreur : mauvais identifiant ou mot de passe</div>';
                }
                }
            }
        echo'<form method="post" action="?do=login" class="d-print-none">
            '.csrf_field().'
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
<footer class="mt-4 py-3 border-top d-print-none">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
        <span><i class="fa fa-database" aria-hidden="true"></i>
<?php
$footer_stats = sql('SELECT (SELECT count(*) FROM presse) as articles, (SELECT count(*) FROM collections) as collections', array())->fetch();
echo (int)$footer_stats['articles'].' article(s) dans '.(int)$footer_stats['collections'].' collection(s)';
?>
        </span>
        <span>
            <a class="link-secondary" href="https://framagit.org/justinmponcet/pressdb"><i class="fa fa-code" aria-hidden="true"></i> Code source</a>
            · PressDB <em>alpha</em>
        </span>
    </div>
</footer>
<script>
document.querySelectorAll('a[data-ajax]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault(); // Empêche la navigation normale
        const url = this.getAttribute('href');

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'toast-container position-fixed top-0 end-0 p-3';
                    wrapper.innerHTML = '<div class="toast align-items-center text-bg-success border-0 show" role="alert"><div class="d-flex"><div class="toast-body">' + (data.message || 'Action effectuée') + '</div></div></div>';
                    document.body.appendChild(wrapper);
                    setTimeout(() => wrapper.remove(), 3000);
                    const art = link.closest('article');
                    if (art) {art.remove();}
                    setTimeout(() => location.reload(), 1200);
                } else {
                    alert('Erreur : ' + (data.message || 'Action impossible.'));
                }
            })
            .catch(error => {
                console.error('Erreur réseau :', error);
                alert('Erreur réseau. Voir la console pour plus de détails.');
            });
    });
});
</script>
<script>
window.pressdbCharts = <?= json_encode($chart_configs ?: array()) ?>;
window.pressdbChartInstances = window.pressdbChartInstances || {};
window.pressdbInitCharts = function() {
    if(typeof Chart === 'undefined') {return false;}
    Object.keys(window.pressdbCharts).forEach(function(id) {
        const el = document.getElementById(id);
        if(!el) {return;}
        if(window.pressdbChartInstances[id]) {
            try {window.pressdbChartInstances[id].resize();} catch(e) {}
            return;
        }
        const pane = el.closest('.tab-pane');
        if(pane && !pane.classList.contains('active')) {return;}
        window.pressdbChartInstances[id] = new Chart(el, window.pressdbCharts[id]);
    });
    return true;
};
(function waitChart() {
    if(window.pressdbInitCharts && window.pressdbInitCharts()) {return;}
    setTimeout(waitChart, 150);
})();
document.addEventListener('shown.bs.tab', function(e) {
    if(window.pressdbInitCharts) {setTimeout(window.pressdbInitCharts, 50);}
});
</script>
<script>
(function() {
    const KEY = 'pressdb-density';
    const apply = function(mode) {
        document.body.classList.toggle('density-compact', mode === 'compact');
        document.querySelectorAll('details').forEach(d => {d.open = (mode !== 'compact');});
        const label = document.getElementById('densityLabel');
        if(label) {label.textContent = (mode === 'compact') ? 'Vue compacte' : 'Vue détaillée';}
        localStorage.setItem(KEY, mode);
    };
    const saved = localStorage.getItem(KEY) || 'full';
    if(saved === 'compact') {apply('compact');}
    const btn = document.getElementById('densityToggle');
    if(btn) {btn.addEventListener('click', function() {
        apply(document.body.classList.contains('density-compact') ? 'full' : 'compact');
    });}
})();
</script>
<style>
@media screen {
    body.density-compact article .article-compact {display: none;}
    body.density-compact article details {display: none;}
}
</style>
</body>
</html>
