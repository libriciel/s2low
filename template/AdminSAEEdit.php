<?php
    /** @var PastellProperties $pastellProperties */
?>
<h1>Configuration de la connexion SAE</h1>
<p id="back-transaction-btn">
	<a class="btn btn-default" href='admin_authority_edit.php?id=<?php echo $id ?>'>« revenir au formulaire standard</a><br/>
</p>
<h2>Modification des propriétés SAE (Pastell) de <?php echo $authorityInfo['name']?></h2>

<form class="form form-horizontal" action='admin_authority_sae_controler.php' method='post'>
	<input type='hidden' name='id' value='<?php echo $id ?>' />

    <div class="form-group">
        <label class="col-md-4 label-form" for="pastell_url">URL Pastell&nbsp;: </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="pastell_url"
                   id="pastell_url"
                   value="<?php echo get_hecho($pastellProperties->url) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="pastell_login">Identifiant de connexion&nbsp;: </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="pastell_login"
                   id="pastell_login"
                   value="<?php echo get_hecho($pastellProperties->login) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="pastell_password">Mot de passe&nbsp;: </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="password" size="30"
                   name="pastell_password"
                   id="pastell_password"
                   value=""
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="pastell_id_e">Identifiant de l'entité (id_e) :</label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="pastell_id_e"
                   id="pastell_id_e"
                   value="<?php echo get_hecho($pastellProperties->id_e) ?>"
            />
        </div>
    </div>




    <h3>Actes</h3>
    <div class="form-group">
        <label class="col-md-4 label-form" for="actes_flux_id">Identifiant du flux à créer&nbsp;(actes-generique par défaut): </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="actes_flux_id"
                   id="actes_flux_id"
                   value="<?php echo get_hecho($pastellProperties->actes_flux_id) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="actes_action">Action à déclencher&nbsp;: (send-archive par défaut)</label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="actes_action"
                   id="actes_action"
                   value="<?php echo get_hecho($pastellProperties->actes_action) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="actes_send_auto">Automatiser l'envoi&nbsp;: </label>
        <div class="col-md-6">
            <input
                    type="checkbox"
                    name="actes_send_auto"
                    id="actes_send_auto"
                <?php echo $pastellProperties->actes_send_auto?"checked='checked'":""?>
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="actes_transaction_id_min">#ID minimum pour l'automatisation&nbsp;(inclu, min=0) : </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="actes_transaction_id_min"
                   id="actes_transaction_id_min"
                   value="<?php echo get_hecho($pastellProperties->actes_transaction_id_min) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="actes_transaction_id_max">#ID maximum pour l'automatisation&nbsp;(inclu, max=2147483647): </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="actes_transaction_id_max"
                   id="actes_transaction_id_max"
                   value="<?php echo get_hecho($pastellProperties->actes_transaction_id_max) ?>"
            />
        </div>
    </div>


    <h3>Hélios</h3>
    <div class="form-group">
        <label class="col-md-4 label-form" for="helios_flux_id">Identifiant du flux à créer&nbsp;(helios-generique): </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="helios_flux_id"
                   id="helios_flux_id"
                   value="<?php echo get_hecho($pastellProperties->helios_flux_id) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="helios_action">Action à déclencher&nbsp;(send-archive par défaut): </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="helios_action"
                   id="helios_action"
                   value="<?php echo get_hecho($pastellProperties->helios_action) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="helios_send_auto">Automatiser l'envoi&nbsp;: </label>
        <div class="col-md-6">
            <input
                   type="checkbox"
                   name="helios_send_auto"
                   id="helios_send_auto"
                   <?php echo $pastellProperties->helios_send_auto?"checked='checked'":""?>
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="helios_transaction_id_min">#ID minimum pour l'automatisation&nbsp;(inclu, min=0) : </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="helios_transaction_id_min"
                   id="helios_transaction_id_min"
                   value="<?php echo get_hecho($pastellProperties->helios_transaction_id_min) ?>"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-md-4 label-form" for="helios_transaction_id_max">#ID maximum pour l'automatisation&nbsp;(inclu, max=2147483647): </label>
        <div class="col-md-6">
            <input class="form-control"
                   type="text" size="30"
                   name="helios_transaction_id_max"
                   id="helios_transaction_id_max"
                   value="<?php echo get_hecho($pastellProperties->helios_transaction_id_max) ?>"
            />
        </div>
    </div>


    <div class="form-group">
		<input class="col-md-offset-4 col-md-6 btn btn-default" value="Modifier" type="submit" />
	</div>
</form>

<div id="actions-area">
    <h2>Actions</h2>
    <a class="btn btn-primary" href="admin_authority_sae_test_connexion.php?id=<?php echo $id?>" class="bouton">Tester la connexion</a>
    <br/><br/>
    <a class="btn btn-primary" href="admin_authority_sae_statistiques.php?id=<?php echo $id?>" class="bouton">Statistiques d'envoi</a>
</div>
