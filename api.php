<?php
include 'inc.php';
$response = ['success' => false, 'message' => ''];

if(!check(@$_SESSION['login']) AND $_SERVER['SERVER_NAME'] !='localhost') {
	$response = ['success' => false, 'message' => 'Erreur d’authentification'];
}
if(isset($_GET['do'])) {
	switch($_GET['do']) {
		case 'update-category':
			if(isset($_GET['id']) AND isset($_GET['category'])  AND $_GET['token'] == $_SESSION['token'] AND $_GET['token'] !='') {
				sql('UPDATE presse SET category = ? WHERE id=?', array($_GET['category'], $_GET['id']));
				$response = ['success' => true, 'message' => 'Catégorie mise à jour'];
			}
			else {
				$response = ['success' => false, 'message' => 'Paramètre(s) manquant(s)'];
			}
		break;
		case 'delete-entry':
			if(isset($_GET['delete']) AND isset($_GET['token']) AND $_GET['token'] == $_SESSION['token'] AND $_GET['token'] !='') {
				sql('DELETE FROM presse WHERE id=?', array($_GET['delete']));
				$response = ['success' => true, 'message' => 'Article supprimé'];
			}
		break;
	}
}

echo json_encode($response);
exit;
