<h1><?php hecho($this->title)?></h1>
<p id="back-transaction-btn">
    <a class="btn btn-default" href="admin_authority_edit.php?id=<?php hecho($this->authority_id) ?>">Retour formulaire
        principal</a><br>
</p>
<h2>Gestion des numéro SIRET</h2>

<div class="col-xs-6">
<ul class="list-group">
    <li class="list-group-item">
        <strong>SIRET activé</strong>
    </li>
<?php if (! $this->siret_list) : ?>
    <li class="list-group-item">
        Cette collectivité n'est associée à aucun numéro SIRET.
    </li>
<?php endif;?>

<?php foreach ($this->siret_list as $siret_info) : ?>
    <li class="list-group-item">
        <?php hecho($siret_info['siret'])?>
        <?php if ($this->userContext->me->isSuper()) :?>
            <form action='admin_authority_siret_del.php' method='post' class="pull-right">
                <input type='hidden' name='authority_siret_id' value='<?php hecho($siret_info['id'])?>'/>
                <button class="btn btn-xs btn-warning" type='submit' title="Supprimer ce numéro SIRET">
                <span class="glyphicon glyphicon-trash"></span>
                </button>
            </form>

            <span class="pull-right">&nbsp;</span>

            <form action='admin_authority_siret_block.php' method='post' class="pull-right">
                <input type='hidden' name='authority_siret_id' value='<?php hecho($siret_info['id'])?>'/>
                <button class="btn btn-xs btn-info" type='submit' title="Bloquer ce numéro SIRET">
                    <span class="glyphicon glyphicon-thumbs-down"></span>
                </button>
            </form>


        <?php endif;?>
    </li>
<?php endforeach;?>


</ul>
<ul class="list-group">
<li class="list-group-item">
    <strong>SIRET bloqués</strong>
</li>

    <?php if (! $this->siret_blocked_list) : ?>
        <li class="list-group-item">
            Cette collectivité n'a pas de SIRET bloqué.
        </li>
    <?php endif;?>
    <?php foreach ($this->siret_blocked_list as $siret_info) : ?>
        <li class="list-group-item">
            <?php hecho($siret_info['siret'])?>
            <?php if ($this->userContext->me->isSuper()) :?>
                <form action='admin_authority_siret_del.php' method='post' class="pull-right">
                    <input type='hidden' name='authority_siret_id' value='<?php hecho($siret_info['id'])?>'/>
                    <button class="btn btn-xs btn-warning" type='submit' title="Supprimer ce numéro SIRET">
                        <span class="glyphicon glyphicon-trash"></span>
                    </button>
                </form>

                <span class="pull-right">&nbsp;</span>

                <form action='admin_authority_siret_unblock.php' method='post' class="pull-right">
                    <input type='hidden' name='authority_siret_id' value='<?php hecho($siret_info['id'])?>'/>
                    <button class="btn btn-xs btn-info" type='submit' title="Débloquer ce numéro SIRET">
                        <span class="glyphicon glyphicon-thumbs-up"></span>
                    </button>
                </form>


            <?php endif;?>
        </li>
    <?php endforeach;?>
</ul>

</div>

<div class='col-xs-6'>
<?php if ($this->userContext->me->isSuper()) :?>
<form action='admin_authority_siret_add.php' method='post'>
<input type='hidden' name='authority_id' value='<?php hecho($this->authority_id)?>' />
    <div class="input-group">
      <input type="text" class="form-control" name='siret' placeholder="Numéro SIRET" value='<?php hecho($this->siret)?>'>
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit">Ajouter</button>
      </span>
    </div><!-- /input-group -->
</form>
<br/><br/>
<?php endif;?>
<div class="panel panel-default">
  <div class="panel-body">
      <p>Ces numéros SIRET sont utilisés dans le cadre du protocole PES pour associer les PES Retour en provenance d'Hélios à la collectivité.</p>
      <p>Lorsqu'un PES Retour arrive on selectionne la collectivité avec le <strong>SIRET activé</strong></p>
      <p>Les <strong>SIRET bloqué</strong> permettent de ne pas selectionner la collectivité (erreur dans le numéro SIRET sur le PES Aller)</p>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-body">
  <?php if ($this->userContext->me->isSuper()) :?>
    <small>Exemple de numéro SIRET valide : <?php hecho($this->siret_exemple->getValue())?></small>
  <?php else :?>
    <small>Seul un super admin peut modifier cette liste.</small>
  <?php endif;?>
  </div>
</div>
</div>
