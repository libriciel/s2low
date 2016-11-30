<h1><?php hecho($title)?></h1>
<p id="back-transaction-btn">
	<a class="btn btn-default" href="admin_authority_edit.php?id=<?php hecho($authority_id) ?>">Retour formulaire
		principal</a><br>
</p>
<h2>Gestion des numéro SIRET</h2>

<ul class="list-group col-xs-6">
<?php if (! $siret_list): ?>
	<li class="list-group-item">
    	Cette collectivité n'est associée à aucun numéro SIRET.
    </li>
<?php endif;?>

<?php foreach($siret_list as $siret_info) : ?>
	<li class="list-group-item">
	  	<?php hecho($siret_info['siret'])?>
	  	<?php if($this->me->isSuper()):?>
	    <form action='admin_authority_siret_del.php' method='post' class="pull-right">    
	    	<input type='hidden' name='authority_siret_id' value='<?php hecho($siret_info['id'])?>'/>
			<button class="btn btn-xs btn-warning" type='submit'>
			<span class="glyphicon glyphicon-trash"></span>
			</button>
		</form>  
		<?php endif;?>
	</li>
<?php endforeach;?>
</ul>


<div class='col-xs-6'>
<?php if($this->me->isSuper()):?>
<form action='admin_authority_siret_add.php' method='post'>
<input type='hidden' name='authority_id' value='<?php hecho($authority_id)?>' />
    <div class="input-group">
      <input type="text" class="form-control" name='siret' placeholder="Numéro SIRET" value='<?php hecho($siret)?>'>
      <span class="input-group-btn">
        <button class="btn btn-default" type="submit">Ajouter</button>
      </span>
    </div><!-- /input-group -->
</form>
<br/><br/>
<?php endif;?>
<div class="panel panel-default">
  <div class="panel-body">
	Ces numéros SIRET sont utilisés dans le cadre du protocole PES pour associer les PES Retour en provenance d'Hélios à la collectivité.
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-body">
  <?php if($this->me->isSuper()):?>
	<small>Exemple de numéro SIRET valide : <?php hecho($siret_exemple)?></small>
	<?php else :?>
	<small>Seul un super admin peut modifier cette liste.</small>
	<?php endif;?>
  </div>
</div>
</div>
