<?php

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();
if (! $me->isAuthorityAdmin()) {
        exit;
}

if (! isset($_FILES['carnet'])) {
    $_SESSION['error'] = "Aucun fichier envoyé";
    header("Location: index.php?command=annuaire");
    exit;
}


$uploader = new FileUploader();
$result = $uploader->upload("carnet");

if (! $result) {
    $_SESSION['error'] = $uploader->getLastError();
    header("Location: import_annuaire_result.php");
    exit;
}

$bd = DatabasePool::getInstance();

$annuaire =  new Annuaire($bd, $me->get('authority_id'));
$annuaire->import($uploader);

$_SESSION['last_annuaire'] = $annuaire;

header("Location: import_annuaire_result.php");
