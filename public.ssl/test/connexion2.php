<?php

define("TESTING_ENVIRONNEMENT", true);
require_once(__DIR__ . "/../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$authenfication = ObjectInstancierFactory::getObjetInstancier()->get('Authentification');

$connexion_info = $authenfication->getAllConnexionInfo();

$authenfication_message = "";
$user_message = "";

try {
	$id = $authenfication->authenticate();
} catch (Exception $e) {
	$authenfication_message = $e->getMessage();
}

try {
	$user = new User();
	$user->authenticate();
} catch (Exception $e) {
	$user_message = $e->getMessage();
}

?>
<h1>Pré-requis</h1>

<table border="1">
	<tr>
		<th>Propriété</th>
		<th>Attendu</th>
		<th>Résultat</th>
	</tr>
	<tr>
		<td>$_SERVER['SSL_CLIENT_VERIFY']</td>
		<td>SUCCESS</td>
		<td><?php hecho($_SERVER['SSL_CLIENT_VERIFY']) ?></td>
	</tr>
	<tr>
		<td>$_SERVER['SSL_CLIENT_CERT']</td>
		<td>Certificat au format PEM</td>
		<td><?php echo nl2br(get_hecho($_SERVER['SSL_CLIENT_CERT'])) ?></td>
	</tr>
	<tr>
		<td>Hash certificat de connexion</td>
		<td>Un hash (obligatoire)</td>
		<td><?php print_r($connexion_info['certificate_hash']) ?></td>
	</tr>
	<tr>
		<td>ID</td>
		<td>Identifiant utilisateur trouvé (Authentification)</td>
		<td><?php print_r($id) ?></td>
	</tr>
	<tr>
		<td>Erreur (Authentification)</td>
		<td>(vide)</td>
		<td><?php print_r($authenfication_message) ?></td>
	</tr>
	<tr>
		<td>ID</td>
		<td>Identifiant utilisateur trouvé (User)</td>
		<td><?php print_r($user->getId()) ?></td>
	</tr>
	<tr>
		<td>Erreur (User)</td>
		<td>(vide)</td>
		<td><?php print_r($user_message) ?></td>
	</tr>

</table>

