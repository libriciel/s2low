<?php

use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Lib\SQLQuery;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

/** @var SQLQuery $sqlQuery */
$sqlQuery = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(SQLQuery::class);

if (! $me->isAuthorityAdmin()) {
        exit;
}

$sqlMails = 'SELECT id, mail_address, description
FROM   mail_annuaire
WHERE  mail_annuaire.authority_id = ?  ';

$sqlGroupes = 'SELECT mail_groupe.name
    FROM   mail_groupe
       JOIN mail_user_groupe
         ON mail_groupe.id = mail_user_groupe.id_groupe
    WHERE  mail_user_groupe.id_user = ?  ';

$contacts = $sqlQuery->query($sqlMails, $me->get('authority_id'));

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=contacts.csv");

echo "adresse mail;description;groupes\n";

foreach ($contacts as $contact) {
    $lineToPrint = $contact['mail_address'] . ';' . $contact['description'];
    $groupes = $sqlQuery->query($sqlGroupes, $contact["id"]);

    foreach ($groupes as $groupe) {
        $lineToPrint = $lineToPrint . ';' . $groupe['name'];
    }
    echo $lineToPrint . "\n";
}
