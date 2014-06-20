<?php
require_once( __DIR__ . "/../../../init/init-www-helios.php");

if (! $moduleSQL->hasDroit($moduleInfo['id'],$connexion->getId(),'CS')){
	Helpers::returnAndExit(1, "Vous ne disposez pas du droit de signature.", WEBSITE_SSL . "/modules/helios/index.php");
}

$liste_id = Helpers::getVarFromPost("liste_id");

if (!$liste_id){
	Helpers::returnAndExit(1, "Vous devez sélectionner au moins une transaction à signer.", WEBSITE_SSL . "/modules/helios/index.php");
}


$heliosTransactionSQL = new HeliosTransactionsSQL($sqlQuery);
$transaction_list = array();

$heliosSignature = new HeliosSignature();

foreach ($liste_id as $transaction_id){
	try{
	 	$transactionInfo = $heliosTransactionSQL->getInfo($transaction_id);
	 	if ($transactionInfo['authority_id'] != $userInfo['authority_id']){
	 		Helpers::returnAndExit(1, "Vous n'avez pas le droit de signature sur la transaciton n°{$transactionInfo['id']}", WEBSITE_SSL . "/modules/helios/index.php");
	 	}
	 	$signature = $heliosSignature->getInfoForSignature(HELIOS_FILES_UPLOAD_ROOT."/".$transactionInfo['sha1']);
	 	$transactionInfo['bordereau_hash'] = $signature['bordereau_hash'];
	 	$transactionInfo['bordereau_id'] = $signature['bordereau_id'];
	 	$transaction_list[] = $transactionInfo;
	} catch (Exception $e){
		Helpers::returnAndExit(1, "Impossible de signer la transaction $transaction_id : le fichier PES contient un bordereau qui n'a pas d'identifiant", WEBSITE_SSL . "/modules/helios/index.php");
		
	}
}


$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle("Tedetis : Signature de plusieurs fichier PES");
$doc->openContainer();
$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
$doc->closeSideBar();
$doc->openContent();


$html .= "<h1>HELIOS - Signature de plusieurs PES</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"bouton\">Retour liste transactions</a></p>\n";

$html .= "<h2>Liste des fichiers à signer</h2>\n";

$html .= "<div id=\"lot-area\">\n";
$html .= "<table class=\"data-table table table-striped\">";
$html .= "<caption>Liste des lots de transactions<caption>\n";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"numero_helios\" class=\"data\">Numéro du fichier</th>\n";
$html .= " <th id=\"fichier_helios\" class=\"data\">Fichier</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

$i = 0;

foreach ($transaction_list as $transactionInfo) {
	
	$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
	$html .= " <td headers=\"numero_acte\"><a href=\"" . WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $transactionInfo['id'] . "\" title=\"Visualiser le PES\">" . $transactionInfo['id'] . "</a></td>\n";
	$html .= " <td headers=\"fichier_helios\">"; 
	$html .= "<a href=\"" . WEBSITE_SSL . "/modules/helios/helios_download_file.php?id=" . $transactionInfo['id'] . "\" title=\"Télécharger le fichier\">" . $transactionInfo['filename']. "</a>";				
	$html .= "</td>\n";
	$html .= "</tr>\n";

	$i = 1 - $i;
}
$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";



$html .= "<h3>Signature des fichiers PES</h3>";



ob_start();
?><div class='action'>
	<applet codebase = "<?php echo LIBERSIGN_URL ?>"
			code = "org/adullact/parapheur/applets/splittedsign/Main.class" 
			archive = "SplittedSignatureApplet.jar, lib/bcmail-jdk16-138.jar, lib/bcprov-jdk16-138.jar, lib/xom-1.1.jar" 
			name = "appletsignature"
			width = "500"
			height = "257" >
			
			<param value="all-permissions" name="permissions"></param>
    <param value="false" name="codebase_lookup"></param>
    <param value="true" name="display_cancel"></param>
    <param value="javascript" name="cancel_mode"></param>
    <param value="<?php echo count($transaction_list)?>" name="hash_count"></param>
    <?php foreach($transaction_list as $i => $transactionInfo) : ?>
   	 	<param value="<?php echo $transactionInfo['bordereau_hash']?>" name="hash_<?php echo $i+1?>"></param>
    	<param value="<?php echo $transactionInfo['bordereau_id']?>" name="pesid_<?php echo $i+1?>"></param>
    	<param value="<?php echo $transactionInfo['id']?>" name="iddoc_<?php echo $i+1?>"></param>
    	<param value="urn:oid:1.2.250.1.131.1.5.18.21.1.4" name="pespolicyid_<?php echo $i+1?>"></param>
    	<param value="Politique de signature Helios de la DGFiP" name="pespolicydesc_<?php echo $i+1?>"></param>
    	<param value="Jkdb+aba0Hz6+ZPKmKNhPByzQ+Q=" name="pespolicyhash_<?php echo $i+1?>"></param>
    	<param value="https://portail.dgfip.finances.gouv.fr/documents/PS_Helios_DGFiP.pdf" name="pesspuri_<?php echo $i+1?>"></param>
    	<param value="France" name="pescountryname_<?php echo $i+1?>"></param>
    	<param value="Ordonnateur" name="pesclaimedrole_<?php echo $i+1?>"></param>
    	<param value="null" name="p7s_<?php echo $i+1?>"></param>
    	<param value="iso-8859-1" name="pesencoding_<?php echo $i+1?>"></param>
    	<param value="XADES-env" name="format_<?php echo $i+1?>"></param>
		<param value="<?php hecho($authorityInfo['city'])?>" name="pescity_<?php echo $i+1?>"></param>
    	<param value="<?php hecho($authorityInfo['postal_code'])?>" name="pespostalcode_<?php echo $i+1?>"></param>
    <?php endforeach;?>
    <param value="form" name="return_mode"></param>
   
    </applet>
	 </div>
<script type="text/javascript" src="/javascript/jfu/js/jquery.min.js"></script> 
<form action='<?php echo WEBSITE_SSL?>modules/helios/helios_transac_sign.php' id='form_sign' method='post'>
	<input type='hidden' name='nb_signature'  value='<?php echo count($transaction_list)?>'/>
	<input type='hidden' name='id' id='form_sign_id' value='<?php echo 999 ?>'/>
	
	<?php foreach($transaction_list as $i=>$transactionInfo) :?>
	<input type='hidden' name='id_<?php echo $i+1 ?>' value='<?php echo $transactionInfo['id'] ?>'/>
	<input type='hidden' name='signature_id_<?php echo $i+1 ?>' value='<?php echo $transactionInfo['bordereau_id'] ?>' />
	<input type='hidden' name='signature_<?php echo $i+1 ?>' id='signature_<?php echo $i+1?>' value=''/>
	<?php endforeach;?>
</form>
<script>
function injectSignature() {
    <?php foreach($transaction_list as $i => $transactionInfo) : ?> 
	signature = document.applets[0].returnSignature("<?php echo $transactionInfo['id'] ?>");
	$("#signature_<?php echo $i+1 ?>").val(signature);
	<?php endforeach;?>
	$("#form_sign").submit();
}
</script>
	 
	<?php 	
		$html .= ob_get_contents();
		ob_end_clean();


$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();
