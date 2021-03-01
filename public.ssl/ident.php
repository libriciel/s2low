<?php
// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

//$login = Helpers::getVarFromPost("login");
//$password = Helpers::getVarFromPost("password");

unset($_SESSION['error']);

$me = new User();

//$environnment = $objectInstancier->get(Environnement::class);

//$environnment->server()->set('PHP_AUTH_USER',$login);     //=>'login',
//$environnment->server()->set('PHP_AUTH_PW',$password);    // => 'password',

//var_dump($environnment->server()->get('PHP_AUTH_USER'));
/*if (! $me->login($login,md5($password))) {
	$_SESSION["error"] = "Échec de l'authentification";
	header("Location: " . WEBSITE_SSL);
	exit;
}*/

//$me->retrieveInfoFromClientCertificate();               //TODO : CHECK IF NECESSARY

if (! $me->authenticate(Authentification::AUTHENTIFICATION_BY_FORM)) {
  $_SESSION["error"] = "Échec de l'authentification";
}

$_SESSION['id_login'] = $me->getId();


// TODO : modifier controller/AdminUserController.class.php     216
// TODO : modifier model/UserSQL.class.php				        143
// TODO : modifierpublic.ssl/ident.php				            13
// TODO : modifier le nom du champ password en password_hash
// TODO : modifier la longueur du champ pour correspondre à la documentation
// https://www.php.net/manual/fr/function.password-hash.php
// " Notez que cette constante est concue pour changer dans le temps, au fur
//  et à mesure que des algorithmes plus récents et plus forts sont ajoutés à
//  PHP. Pour cette raison, la longueur du résultat issu de cet algorithme peut
//  changer dans le temps, il est donc recommandé de stocker le résultat dans
//  une colonne de la base de données qui peut contenir au moins 60 caractères
//  (255 caractères peut être un très bon choix). "

header("Location: " . WEBSITE_SSL);