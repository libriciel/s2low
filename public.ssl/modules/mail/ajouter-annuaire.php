<?php

use Legacy\MailLayout;

list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();
if (! $me->isAuthorityAdmin()) {
        exit;
}
$doc = new MailLayout();
$doc->disableError();
$doc->setTitle("Gestion du carnet d'adresses");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$doc->DisplayHead();
?>
<h1> Carnet d'adresses </h1>
    
<h2> Ajout d'email</h2>     


    <div id="add-user">
    <form action="index.php?command=annuaire" method="post" class="form-horizontal">
            <div class="form-group">
                <label class="col-md-2 control-label" for="name">Nom</label>
                <div class="col-md-4"> <input id="name" class="form-control" size="40" maxlength="128" name="description" type="text" /></div>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label" for="email">Adresse email</label>
                <div class="col-md-4"> 
                    <input id="email" class="form-control" size="40" maxlength="128" name="email" type="text" />
                </div>
            </div>
            <div class="form-group">
                <input class="btn btn-primary" value="Ajouter une nouvelle adresse" type="submit" />
            </div>
    </form>
    </div>

</div>


<?php

$doc->closeContent(true);
$doc->closeContainer(true);

$doc->DisplayFoot();
