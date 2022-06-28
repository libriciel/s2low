<?php

require_once("../../../init/init.php");


// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
    Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

if (!$me->authenticate()) {
    Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || ! $me->canAccess($module->get("name"))) {
    Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$description = Helpers::getVarFromPost("intitule");
$num_prefix = Helpers::getVarFromPost("prefixe");

$zeBatch = new ActesBatch();

$zeBatch->set("description", $description);
$zeBatch->set("num_prefix", $num_prefix);
$zeBatch->set("user_id", $me->getId());

//Stéphane Sampaio Edit
//conversion du tableau $_FILES reçu pour adaptation au traitement
$converted = array();
$filerefs = array();
for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
    $filerefs[$_FILES['files']['name'][$i]] = $i;
    $converted[$_FILES['files']['name'][$i]] = array(
      'name' => '',
      'type' => '',
      'tmp_name' => '',
      'error' => 0,
      'size' => 0
    );
}
for ($i = 0; $i <= max($filerefs); $i++) {
    $name = $_FILES['files']['name'][$i];
    $converted[$name]['name'] = $name;
    $converted[$name]['type'] = $_FILES['files']['type'][$i];
    $converted[$name]['tmp_name'] = $_FILES['files']['tmp_name'][$i];
    $converted[$name]['error'] = $_FILES['files']['error'][$i];
    $converted[$name]['size'] = $_FILES['files']['size'][$i];
}

$logger->debug("Fichier reçu dans le lot", $converted);
//Fin
$alljson = array();

$elvl = 0;
$msg = '';
$id = 0;
if (count($converted) > 0) {
    if (!$zeBatch->importFilesFromForm($converted)) {
        $msg = $zeBatch->getErrorMsg();
        $elvl = 1;



      //Helpers::returnAndExit(1, $zeBatch->getErrorMsg(), WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
    } else {
        if (!$zeBatch->save()) {
            $msg = "Erreur lors de l'enregistrement du lot : " . $zeBatch->getErrorMsg();

            if (!Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, 'USER', $module->get("name"), $me)) {
                $msg .= "\nErreur de journalisation.";
            }

    //      Helpers::returnAndExit(1, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_add.php");
            $elvl = 1;
        } else {
            if (!Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
                $msg .= "\nErreur de journalisation.";
            }
            $elvl = 0;


            foreach ($converted as $kFile => $file) {
                   $jsontest = new stdClass();
                      $jsontest->name = $file['name'];   // Passage UTF8 : utf8_encode supprimé : probable bug de l'API
                      $jsontest->size = $file['size'];
                      $jsontest->type = $file['type'];
                      $alljson[] = $jsontest;
            }

            $id = $zeBatch->getId();
            $msg = "Lot n&deg;" . $id . " cr&eacute;&eacute; avec succ&egrave;s.";
         // Helpers::returnAndExit(0, $msg, WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=" . $zeBatch->getId(), $zeBatch->getId());
        }
    }
} else {
    $elvl = 1;
    $msg = "Aucun fichier soumis";
}
$alljson[] = array('msg' => $msg, 'elvl' => $elvl, 'id' => $id);




echo json_encode($alljson);
