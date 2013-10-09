<?php 
require_once("include/init.php");

if (! $me->isAuthorityAdmin()){
  		exit;
  	}

require_once ("lib/MailLayout.class.php");
$doc = new MailLayout();
$doc->disableError(); 
$doc->setTitle("Gestion du carnet d'adresse");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$doc->DisplayHead();
?>
    <h1> Carnet d'adresse </h1>  	
    <h2> Ajout d'un groupe</h2>  	
    <div class="data_table">
        <form class="form" action="ajouter-groupe-controler.php" method="post">
            <div class="form-group">
                <label class="col-md-3 control-label" for="name">Nom du groupe :</label>
                <div class="col-md-4"> 
                    <input id="name" class="form-control" size="40" maxlength="128" name="name" type="text" />
                </div>
            </div>
            <div class="form-group">
                <input class="btn btn-primary" value="Ajouter un nouveau groupe" type="submit" />
            </div>
        </form>
    </div>
</div>

<?php 

$doc->closeContent(true);	
$doc->closeContainer(true);	

$doc->DisplayFoot();	