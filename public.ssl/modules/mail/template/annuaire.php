<script language='javascript'>
<!--
function onChangeGroupSelect() {
    form = document.getElementById('form_generique');
    form.submit();
}

function coche_case(value){

    var tab = document.getElementsByName("checkbox_id[]");
    
    for (i = 0; i<tab.length; ++i) {
        tab[i].checked = value;
    }
}

function retirer(){
    form = document.getElementById('form_generique');
    form.groupe_id.value=form.old_groupe_id.value;
    form.submit();
}
-->
<?php
/**
 * @var array $groupeArray
 * @var int $groupe_id
 * @var Annuaire $annuaire
 * @var array $mailAnnuaireArray
 * @var array $groupe_name
 */
use S2lowLegacy\Mail\Annuaire;
?>
</script>

    <h1> Carnet d'adresses </h1>
    
    <?php if (isset($_SESSION["last_message"])) : ?>
    <div class="alert alert-success" >
            <?php echo $_SESSION["last_message"]; ?>
    </div>      
        <?php
        unset($_SESSION["last_message"]);
    endif;?>

<?php if (isset($_SESSION["last_error"])) : ?>
    <div class="alert alert-danger" >
        <?php echo $_SESSION["last_error"]; ?>
    </div>
    <?php
    unset($_SESSION["last_error"]);
endif;?>


    <div id="address-book" class="row">    
        <div class="col-md-9">
            <ul class="nav nav-tabs">
                <?php if (count($groupeArray)) : ?>
                <li <?php if (! $groupe_id) {
                    echo " class=\"active\"";
                    }?>>
                    <a href='index.php?command=annuaire'>Tous les contacts (<?php echo $annuaire->getNbContact(); ?>)</a>
                </li>
                    <?php foreach ($groupeArray as $groupe) :?>
                <li<?php if ($groupe_id == $groupe['id']) {
                    echo " class=\"active\"";
                   }?>>
                    <a href='index.php?command=annuaire&groupe_id=<?php echo $groupe['id'] ?>'><?php hecho($groupe['name']) ?> (<?php echo $groupe['nb_contact']?>)</a>
                </li>
                    <?php endforeach?>
                <?php endif;?>
            </ul>
            <form action="index.php?command=annuaire" method="post" id='form_generique'>    
            <input type='hidden' name='action_h' value=''>    
            <div class='contact-list'>
                Sélectionner <a class="btn btn-default" onclick='javascript:coche_case(true)'>Tous</a>
                <a class="btn btn-default"  onclick='javascript:coche_case(false)'>Aucun</a>    
                <?php foreach ($mailAnnuaireArray as $mailAnnuaire) : ?>
                    <div class='contact' title='<?php echo $mailAnnuaire['mail_address']?>'>
                        <input type="checkbox" name="checkbox_id[]" value="<?php echo $mailAnnuaire['id']; ?>" />
                        <a href='edit-annuaire.php?id=<?php echo $mailAnnuaire['id']?>'><?php hecho($mailAnnuaire['description'] ? $mailAnnuaire['description'] : $mailAnnuaire['mail_address']); ?></a>
                    </div>
                <?php endforeach?>
            </div>
        </div>
        <div class="col-md-3">
            <div id="actions">  
                <div class='actionmail'>
                    <h2> Actions générales </h2>
                    <ul>
                    <?php if ($groupe_id) : ?>
                        <li><a class="btn btn-primary btn-xs" href="supprimer-groupe.php?groupe_id=<?php echo $groupe_id?>">Supprimer le groupe</a></li>
                    <?php endif;?>      
                        <li><a class="btn btn-primary btn-xs" href="export_contacts.php">[Beta] Exporter les contacts</a></li>
                    </ul>    
                    <h2> Actions sur les contacts sélectionnés </h2>
                    <ul>
                        <li><input class="btn btn-primary btn-xs" value="Supprimer" type="submit" /></li>
                    <?php if (count($groupeArray)) : ?>     
                        <?php if ($groupe_id) : ?>
                        <li><input class="btn btn-primary btn-xs" value="Retirer de <?php hecho($groupe_name) ?>" type="submit" onclick='javascript:retirer();'/></li>
                            <input type='hidden' name='old_groupe_id' value='<?php echo $groupe_id?>' />
                        <?php endif;?>  
                        <li>
                            <select class="form-control" name='groupe_id' id='select_group' onchange='javascript:onChangeGroupSelect()'>
                                <option value='0'>Ajouter à ... </option>
                                <?php foreach ($groupeArray as $groupe) :?>
                                        <option value='<?php echo $groupe['id'] ?>'><?php hecho($groupe['name']) ?></option>
                                <?php endforeach?>
                            </select>
                        </li>
                    </ul>    
                    <noscript>
                            <input type='submit' value='changement de groupe'/>
                    </noscript>
                    <?php endif;?>
                </div>
            </div>
        </div>
    </form>

</div>
