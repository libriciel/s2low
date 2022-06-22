<?php

//$filename = $argv[1];

$content = <<<ACK
<?xml version="1.0" encoding="ISO-8859-1"?>
<n:PES_Retour xmlns:n="http://www.minefi.gouv.fr/cp/helios/pes_v2/Rev0/retour" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.minefi.gouv.fr/cp/helios/pes_v2/Rev0/retour /opt/tx/users/edihls10/messages/XML/PES_V2/Externe/Schemas_PES/PES_V2/Rev0/PES_V2_DepenseRetour_Autonome.xsd">
<Enveloppe>
<Parametres>
<Version V="2"></Version>
<TypFic V="PESRETOUR_DEP"></TypFic>
<NomFic V="PES_V2_RETOUR_DEPENSE_054028_00300_20080215.xml"></NomFic>
</Parametres>
</Enveloppe>
<EnTetePES>
<DteStr V="2008-02-15"></DteStr>
<IdPost V="082008"></IdPost>
<LibellePoste V="CORBARIEU "></LibellePoste>
<IdColl V="123456789"></IdColl>
<CodCol V="221"></CodCol>
<CodBud V="00"></CodBud>
<LibelleColBud V="CORBARIEU "></LibelleColBud>
</EnTetePES>
<PES_DepenseRetour>
</PES_DepenseRetour>
</n:PES_Retour>
ACK;

echo $content;
