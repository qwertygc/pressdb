<?php
include 'inc.php';
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
        <a class="nav-link" href="index.php"><i class="fa fa-database" aria-hidden="true"></i> Base de données</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=collections"><i class="fa fa-book" aria-hidden="true"></i> Liste des collections</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="index.php?do=view"><i class="fa fa-eye" aria-hidden="true"></i> Voir</a>
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
                        <a href="?do=collections&delete='.intval($data['id']).'&token='.h($_SESSION['token']).'" class="btn btn-danger btn-sm" onclick="return confirm(\'Êtes-vous sûr ?\');"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer</a>
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
        		$filter_cat = (isset($_GET['idp'])) ? ' WHERE id_collection='.$_GET['idp']: '';
				$date_begin = (isset($_GET['date_begin'])) ? $_GET['date_begin'] : '1900-01-01';
				$date_end = (isset($_GET['date_end'])) ? $_GET['date_end'] : date('Y-m-d', time());
		        echo'<form method="get" class="d-print-none">
		            <label>Collection</label>
		            <select class="form-select" name="collection">';
		                //$id_collection = (isset($_GET['idp'])) ? $_GET['idp'] : 0;
		                $query = $db->query('SELECT * FROM collections ORDER BY id');
		                while($data = $query->fetch()) {
				            $selected = ((int)$data['id'] == (int)$_GET['idp']) ? 'selected' : '';
				          	echo '<option value="'.$data['id'].'" '.$selected.'>'.$data['name'].'</option>';
						}
		            echo '</select>
		            <label>Date de début</label><input type="date" name="date_begin" value="'.$date_begin.'" class="form-control" />
		            <label>Date de fin</label><input type="date" name="date_end" value="'.$date_end.'" class="form-control" />
		            <label>Journaux</label>       
		            <select class="form-select" multiple aria-label="multiple select" name="journal[]">';
		                    $journal = (isset($_GET['journal'])) ? $_GET['journal'] : array();
		                    $query = $db->query('SELECT DISTINCT trim(journal) as journal FROM presse '.$filter_cat.' ORDER BY journal');
		                    while($data = $query->fetch()) {
				               	$selected = (in_array($data['journal'], $journal)) ? 'selected' : '';
				          		echo '<option value="'.h($data['journal']).'" '.$selected.'>'.h($data['journal']).'</option>';
		              		}
		           echo ' </select>
		            <label>Catégories</label>
		            <select class="form-select" multiple aria-label="multiple select" name="category[]">';
		                $category = (isset($_GET['category'])) ? $_GET['category'] : array();
		                $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse '.$filter_cat.' ORDER BY category');
		                $data = $query->fetchAll(PDO::FETCH_COLUMN);
		                foreach(tags($data) as $cat) {
		                	$selected = (in_array($cat, $category)) ? 'selected' : '';
		                	echo '<option value="'.$cat.'" '.$selected.'>'.$cat.'</option>';
		                }
		           
		            echo '</select>
		            <label>Mot-clé</label> <input type="search" name="search" class="form-control"/>
		            <input type="hidden" name="do" value="view"/>
		            <input type="hidden" name="idp" value="'.$_GET['idp'].'"/>
		            <input type="submit" name="submit" value="Exécuter" class="btn btn-primary mb-3"/>
		            <input type="submit" name="submit_file" value="Exporter" class="btn btn-primary mb-3"/>
					<a href="?do=view&sort='.$_GET['idp'].'" class="btn btn-primary mb-3"><i class="fa fa-sort"></i> Trier</a>
		            </form>';
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
		            $categories .= 'AND (';
		            foreach($_GET['category'] as $category) {
		            	$cat_array = explode(" ", $category);
		            	foreach($cat_array as $cat) {
		                	$categories .= ' category LIKE ? OR ';
		                	$params[] = '%'.$cat.'%';
		                }
		            }
		            $categories = substr($categories, 0, -4);
		            $categories .=')';
		        }
		        $search = '';
		        if(isset($_GET['search']) AND $_GET['search'] != '') {
		            $search .= 'AND (title LIKE ? OR content LIKE ?)';
		            $params[] = '%'.$_GET['search'].'%';
		            $params[] = '%'.$_GET['search'].'%';
		        }
		        $collection = (($period != '') ? 'AND ' : '').'id_collection=?';
		        $params[] = intval(@$_GET['collection']);
		        $sql = 'SELECT * FROM presse WHERE '.$period.' '.$journaux.' '.$categories.' '.$search.' '.$collection.' ORDER BY date';
		        echo '<h1>Corpus de la revue de presse (généré le '.date('c', time()).')</h1>';
		        echo '<a href="javascript:window.print()">Imprimer</a>';
		        $query = sql($sql, $params);
		        $count = 0;
		        $html_output = '';
		        $raw_txt = '';
		        $export = array();
		        $export[] = array('date', 'year', 'month', 'journal', 'category', 'title', 'content', 'source');
		       
		        while($data = $query->fetch()) {
		        	$html_output .= show_article($data, true);
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
	            echo "Ajout dans la base de données";
	            //si id_member = 0 on insert sinon on update
	            if (intval($_POST['id']) == 0) {
	                sql('INSERT INTO presse (date, journal, category, title, content, source, id_collection) VALUES (?, ?, ?, ?, ?, ?, ?)', array($_POST['date'], $_POST['journal'],$_POST['category'],$_POST['title'],$_POST['content'], $_POST['source'], intval($_POST['collection'])));
	            } else {
	                sql('UPDATE presse SET date = ?, journal = ?, category = ?, title=?, content = ?, source = ?, id_collection=? WHERE id=?', array($_POST['date'], $_POST['journal'], $_POST['category'], $_POST['title'], $_POST['content'], $_POST['source'], intval($_POST['collection']), intval($_POST['id'])));
	            }
	            redirect('index.php?do=view&article='.intval($_POST['id']));
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
	              $query = $db->query('SELECT DISTINCT lower(trim(journal)) as journal FROM presse ORDER BY journal');
	              while($data = $query->fetch()) {
	              	echo '<option value="'.h($data['journal']).'" />';
	              }
	            echo '</datalist>
	            <label>Catégorie</label>
	            <input type="text" name="category" list="category" value="'.h($category).'" class="form-control"/>
	            <datalist id="category">';
	             $query = $db->query('SELECT DISTINCT lower(trim(category)) as category FROM presse ORDER BY category');
	             $data = $query->fetchAll(PDO::FETCH_COLUMN);
                foreach(tags($data) as $cat) {
                	echo '<option value="'.h($cat).'">'.h($cat).'</option>';
                }
	           echo '</datalist>
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
		                    $query = $db->query('SELECT DISTINCT trim(category) as category FROM presse '.$idp.' ORDER BY category');
				            $data = $query->fetchAll(PDO::FETCH_COLUMN);
				            foreach(tags($data) as $cat) {
				            	echo '<option value="'.h($cat).'">'.h($cat).'</option>';
				            }
		                
		    echo ' </select>
		    <label for="newname_category">Nouveau nom</label>
		    <input type="text" name="newname_category" id="newname_category" class="form-control"/>
		    <input type="submit" name="submit" value="Envoyer" class="btn btn-primary mb-3"/>
	        </form>';
	        if(isset($_POST['newname_category']) AND $_POST['newname_category'] != '' AND csrf_ok('POST')) {
				 if(isset($_POST['category'])) {
				        foreach($_POST['category'] as $category) {
				        	//sql("UPDATE presse SET category =REPLACE(category, '".$category."', '".$_POST['newname_category']."') WHERE category LIKE '%$category%'");
				           sql('UPDATE presse SET category = REPLACE(category, :old, :new) WHERE category LIKE :like', array('old'=>$category, 'new'=>$_POST['newname_category'], 'like'=>'%'.$category.'%'));
				        }
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
		  		echo '<a href="?do=tools&delete_doublon=yes&id_collection='.intval($collections['id']).'&token='.h($_SESSION['token']).'" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i> Supprimer les doublons</a>';
		  	}
      		if(isset($_GET['delete_doublon']) AND is_logged() AND csrf_ok('GET')) {
      			sql('DELETE FROM presse WHERE id NOT IN (SELECT MAX(id) FROM presse GROUP BY title) AND id_collection=?', array(intval($_GET['id_collection'])));
      			redirect('index.php?do=tools');
      		}
        break;
        case 'import':
        	if(!is_logged()) {redirect('index.php?do=login');}
			if(isset($_POST['collection']) AND csrf_ok('POST')) {
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
									sql('INSERT INTO presse (date, journal, title, content, source, category, id_collection) VALUES (?,  ?, ?, ?, ?, ?, ?)', array($data['0'], $data['3'],$data['5'],$data['6'],$data['7'], $category, intval($_POST['collection'])));
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
							array($date, $journal_str,$title,$content,'Europresse', 'à_trier', intval($_POST['collection'])));
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
			if(isset($_GET['delete']) AND csrf_ok('GET')) {
				sql('DELETE FROM users WHERE id=?', array(intval($_GET['delete'])));
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
			                <a href="?do=admin&delete='.intval($data['id']).'&token='.h($_SESSION['token']).'" class="btn btn-danger"  onclick="return confirm(\'Êtes-vous sûr ?\');"><i class="fa fa-trash" aria-hidden="true"></i></a>
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
            if (isset($_POST['pwd'])) {
                $something = $db->prepare("SELECT * FROM users WHERE login=?");
				$something->execute(array($_POST['login']));
				$data = $something->fetch();
                if ($data AND password_verify($_POST['pwd'], $data['password']) AND $_POST['login'] == $data['login']) {
                    $_SESSION['login'] = true;
                    $_SESSION['permit'] = (int)$data['permit'];
                    $_SESSION['token'] = bin2hex(random_bytes(16));
                    redirect('index.php');
                } else {
                    $_SESSION['login'] = false;
                    echo '<div class="alert alert-warning" role="alert">Erreur : mauvais identifiant ou mot de passe</div>';
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
<footer><a href="https://framagit.org/justinmponcet/pressdb">Code source</a> - Version alpha</footer>
<script>
document.querySelectorAll('a[data-ajax]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault(); // Empêche la navigation normale
        const url = this.getAttribute('href');

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Action effectuée avec succès !');
                    location.reload();
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
</body>
</html>
