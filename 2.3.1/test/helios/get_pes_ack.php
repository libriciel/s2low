<?php 

$filename = $argv[1];

$content =<<<ACK
<?xml version="1.0" encoding="ISO-8859-1"?>
<n:PES_Acquit xmlns:n="http://www.minefi.gouv.fr/cp/helios/pes/Rev0/aller" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:cm="http://www.minefi.gouv.fr/cp/helios/pes/commun" xsi:schemaLocation="http://www.minefi.gouv.fr/cp/helios/pes/Rev0/aller ..Schemas_PESPES_V2Rev0PES_Ack.xsd">
<Enveloppe>
<Parametres>
<Version V="2"/>
<TypFic V="2"/>
<NomFic V="$filename"/>
</Parametres>
</Enveloppe>
<Acquit>
<NomFic V="$filename"/>
</Acquit>
</n:PES_Acquit>
ACK;

echo $content;