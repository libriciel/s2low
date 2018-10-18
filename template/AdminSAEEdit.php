<h1>Configuration de la connexion SAE</h1>
<p id="back-transaction-btn">
	<a class="btn btn-default" href='admin_authority_edit.php?id=<?php echo $id ?>'>« revenir au formulaire standard</a><br/>
</p>
<h2>Modification des propriétés SAE (Pastell) de <?php echo $authorityInfo['name']?></h2>

<form class="form form-horizontal" action='admin_authority_sae_controler.php' method='post'>
	<input type='hidden' name='id' value='<?php echo $id ?>' />
	<?php foreach(AuthoritySQL::getSAEProperties() as $sae_name => $sae_label):
		?>
		<div class="form-group">
			<label class="col-md-4 label-form"><?php echo $sae_label ?>&nbsp;: </label>
			<div class="col-md-6">
				<input class="form-control"  type="text" size="30" name="<?php echo $sae_name ?>" value="<?php echo get_hecho($authorityInfo[$sae_name]) ?>" />
			</div>
		</div>
	<?php endforeach; ?>
	<div class="form-group">
		<input class="col-md-offset-4 col-md-6 btn btn-default" value="Modifier" type="submit" />
	</div>
</form>