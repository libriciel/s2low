<?php

require_once( __DIR__ . "/../../../init/init-www-helios.php");

if (! $droit->isSuperAdmin($userInfo)){
	header("Location: index.php");
	exit;
}

$me = new User();

if (!$me->authenticate()) {
	$_SESSION["error"] = "Échec de l'authentification";
	header("Location: " . WEBSITE);
	exit ();
}

$recuperateur = new Recuperateur($_GET);
$transaction_id = $recuperateur->getInt('id');

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);

$info = $heliosTransactionsSQL->getInfo($transaction_id);

$pesAllerRetriever = $objectInstancier->get("PesAllerRetriever");
$filename = $pesAllerRetriever->getPath($info['sha1']);

$pes_content = file_get_contents($filename);

$heliosPESValidation = new HeliosPESValidation(HELIOS_XSD_PATH);

$r = $heliosPESValidation->validate($pes_content);


$xadesSignature = new XadesSignature(
        XMLSEC1_PATH,
        new PKCS12(),
        new X509Certificate(),
        EXTENDED_VALIDCA_PATH,
        new XadesSignatureParser(),
        new PemCertificateFactory(),
        new VerifyPemCertificate(
                EXTENDED_VALIDCA_PATH,
            new \S2low\Services\ExtractIssuerHashCommand(),
            new \S2low\Services\ExtractCertificateSNCommand(),
            new \S2low\Services\CheckSnInCRLCommand(),
            new \S2low\Services\OpensslVerifyCommand(EXTENDED_VALIDCA_PATH)
        )
);
$verify_sign =  true;
try{
    $xadesSignature->verify($filename);
} catch(Exception $exception){
    $verify_sign = false;
    $verify_sign_message = $exception->getMessage();
}

$xades_output = $xadesSignature->getLastOutput();

$is_signed = $xadesSignature->isSigned($filename);


$doc = new HTMLLayout();

$doc->setTitle("Helios : visualisation de transactions pour un fichier");
$doc->addBody("<div class=\"container\"><div class=\"row\">");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>

	<p id="back-user-btn">
		<a
			href="<?php echo WEBSITE_SSL ?>/modules/helios/helios_transac_show.php?id=<?php hecho($transaction_id) ?>"
			class="btn btn-default"
			title="Retour à la transaction"
			>
			Retour à la transaction
		</a>
	</p>
	<h2>Helios - Validation d'un PES_Aller</h2>

	<?php if ($r) : ?>
		<div class="alert alert-success">Le fichier PES_Aller est valide !</div>
	<?php else: ?>
		<div class="alert alert-danger">Le fichier PES_Aller n'est pas valide !</div>
	<?php endif; ?>


<table class="table table-bordered">
	<tr>
		<th>Niveau</th>
		<th>Code</th>
		<th>Message</th>
		<th>Ligne</th>
		<th>Colonne</th>
	</tr>
<?php foreach($heliosPESValidation->getLastError() as $i => $info): ?>
	<tr>
		<td><?php hecho($info->level)?></td>
		<td><?php hecho($info->code)?></td>
		<td><?php hecho($info->message)?></td>
		<td><?php hecho($info->line)?></td>
		<td><?php hecho($info->column)?></td>
	</tr>
<?php endforeach; ?>
</table>

<div class="alert alert-warning">
	<strong>Attention</strong>, les erreurs de niveau 1 ne provoque pas l'invalidation du fichier PES_Aller !
</div>


<h2>Validation de la signature du PES</h2>

<?php if(! $is_signed): ?>
    <div class="alert alert-warning">Le fichier n'est pas signé !</div>
<?php elseif($verify_sign): ?>
	<div class="alert alert-success">La signature du fichier est valide !</div>
<?php else : ?>
	<div class="alert alert-danger">La signature du fichier n'est pas valide : <?php echo $verify_sign_message?></div>
<?php endif;?>

<div>
	<p>
		<?php echo $xades_output ?>
	</p>

</div>

<div class="alert alert-info">
    Outil externe de vérification de signatures :
    <a class="btn btn-info" href="http://dss.nowina.lu/validation" target="_blank">
        Validation de signature
    </a>
</div>

<?php



$html.= ob_get_contents();
ob_end_clean();

$html .= "</div>\n";
$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

