<?php 

require_once( __DIR__ . "/../../../init/init-www-actes.php");


$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id');

$authorityInfo = $authoritySQL->getInfo($id);

if (! $authorityInfo  || ! $droit->hasDroit($userInfo,$authorityInfo)){
	sortir("Accès refusé");
}
$xml_default="<Description></Description>
    <Identification>INFORMATION_OBLIGATOIRE</Identification>
    <Name></Name>
    <Contact>
      <PersonName></PersonName>
      <Responsibility></Responsibility>
    </Contact>
    <Address>
      <BuildingNumber></BuildingNumber>
      <CityName></CityName>
      <Postcode></Postcode>
      <StreetName></StreetName>
    </Address>";

$menuHTML = new MenuHTML();

$doc = new HTMLLayout();
$doc->setTitle("Configuration de la connexion SAE - S²low");
$doc->openContainer();
$doc->addBody($menuHTML->getMenu($userInfo,$modulesInfo));
$doc->closeSideBar();
$doc->openContent();

ob_start();
?>
        <h1>ACTES - Dématérialisation du contrôle de légalité</h1>
        <p id="back-transaction-btn">
            <a class="btn btn-default" href='admin_authority_edit.php?id=<?php echo $id ?>'>« revenir au formulaire standard</a><br/>
        </p>
        <h2>Modification des propriétés SAE de <?php echo $authorityInfo['name']?></h2>
        
        <form class="form form-horizontal" action='admin_authority_sae_controler.php' method='post'>
            <input type='hidden' name='id' value='<?php echo $id ?>' />
                <?php foreach(AuthoritySQL::getSAEProperties() as $sae_name => $sae_label):
                        ?>
                        <div class="form-group">
                            <label class="col-md-4 label-form"><?php echo $sae_label ?>&nbsp;: </label>
                            <div class="col-md-8">
                                <?php if (AuthoritySQL::isTextarea($sae_name)) : ?>
                                    <textarea class="form-control"  cols="60" rows="10" name='<?php echo $sae_name?>'><?php echo htmlspecialchars($authorityInfo[$sae_name]?:$xml_default) ?></textarea>
                                    <?php else : ?>
                                    <input class="form-control"  type="text" size="30" name="<?php echo $sae_name ?>" value="<?php echo htmlspecialchars($authorityInfo[$sae_name]) ?>" />
                                    <?php endif;?>
                            </div>
                        </div>
                <?php endforeach; ?>
                <div class="form-group">
                    <input class="btn btn-primary" value="Modifier" type="submit" />
                </div>
        </form>
<?php 
$html = ob_get_contents();
ob_end_clean();

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();
    
$doc->buildFooter();
$doc->display();

