<?php
include 'inc.php';
$response = ['success' => false, 'message' => ''];

if(!is_logged()) {
	$response = ['success' => false, 'message' => 'Erreur d’authentification'];
}
elseif(isset($_GET['do'])) {
	switch($_GET['do']) {
		case 'update-category':
			if(isset($_GET['id']) AND isset($_GET['category']) AND csrf_ok('GET')) {
				sql('UPDATE presse SET category = ? WHERE id=?', array($_GET['category'], intval($_GET['id'])));
				$response = ['success' => true, 'message' => 'Catégorie mise à jour'];
			}
			else {
				$response = ['success' => false, 'message' => 'Paramètre(s) manquant(s)'];
			}
		break;
		case 'delete-entry':
			if(isset($_GET['delete']) AND csrf_ok('GET')) {
				sql('DELETE FROM presse WHERE id=?', array(intval($_GET['delete'])));
				$response = ['success' => true, 'message' => 'Article supprimé'];
			}
			else {
				$response = ['success' => false, 'message' => 'Paramètre(s) manquant(s)'];
			}
		break;
		case 'fix-website':
			if(is_admin()) {
				sql("UPDATE presse SET journal = REPLACE(journal, 'www.', '') WHERE journal LIKE 'www.%';");
				sql("UPDATE presse SET journal = REPLACE(journal, '.com', '') WHERE journal LIKE '%.com';");
				sql("UPDATE presse SET journal = REPLACE(journal, '.fr', '') WHERE journal LIKE '%.fr';");
				sql("UPDATE presse SET journal = REPLACE(journal, '.eu', '') WHERE journal LIKE '%.eu';");
				sql("UPDATE presse SET journal = REPLACE(journal, '.org', '') WHERE journal LIKE '%.org';");
				$response = ['success' => true, 'message' => 'Sites web harmonisés'];
			}
			else {
				$response = ['success' => false, 'message' => 'Erreur d’authentification'];
			}
		break;
		case 'export-file':
			if(is_logged() AND csrf_ok('GET') AND isset($_GET['dir']) AND isset($_GET['file'])) {
				$dir = basename($_GET['dir']);
				$file = basename($_GET['file']);
				$path = 'data/export/'.$dir.'/'.$file;
				$realpath = realpath($path);
				$root = realpath('data/export');
				if($realpath !== false AND $root !== false AND strpos($realpath, $root) === 0 AND is_file($realpath)) {
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.$file.'"');
					header('Content-Length: '.filesize($realpath));
					readfile($realpath);
					exit;
				}
			}
			$response = ['success' => false, 'message' => 'Erreur d’authentification'];
		break;
		case 'export-db':
			if(is_admin() AND csrf_ok('GET') AND file_exists('data/data.db')) {
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="pressdb-'.date('Y-m-d').'.db"');
				header('Content-Length: '.filesize('data/data.db'));
				readfile('data/data.db');
				exit;
			}
			else {
				$response = ['success' => false, 'message' => 'Erreur d’authentification'];
			}
		break;
	}
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
