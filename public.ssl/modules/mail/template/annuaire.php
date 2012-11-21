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
</script>
<div id="content">
	<h1> Carnet d'adresse </h1>  	
 	
 <?php if (isset($_SESSION["last_message"])) : ?>
<h3>		
	<?php echo $_SESSION["last_message"]; ?>
</h3>  	
<?php 
unset($_SESSION["last_message"]);
endif;?>
 
 	<div class='groupemail' >
 	 	
 		<?php if (count($groupeArray)) : ?>
	 		<div class='normal <?php if(! $groupe_id) echo "selected"?>' >
	 			<a class='normal' href='index.php?command=annuaire'>Tous les contacts</a><?php echo $annuaire->getNbContact(); ?>
	 		</div>
	 		<?php foreach($groupeArray as $groupe):?>
	 			<div class='normal <?php if($groupe_id == $groupe['id']) echo "selected"?>'>
	 				<span  ><a href='index.php?command=annuaire&groupe_id=<?php echo $groupe['id'] ?>'><?php hecho($groupe['name']) ?></a></span>
	 				<span ><?php echo $groupe['nb_contact']?></span>
	 				
	 			</div>
	 				
	 		<?php endforeach?>
 		<?php endif;?>
 	</div>
 	
 	<!-- <div class='entoure'>  -->
	  	<form action="index.php?command=annuaire" method="post" id='form_generique'> 	
	  	<input type='hidden' name='action_h' value=''>
	 	<div class='actionmail'>
			<a class='normal' href="ajouter-annuaire.php">Nouveau contact</a>&nbsp;|&nbsp;
			<a class='normal' href="ajouter-groupe.php">Nouveau groupe</a>&nbsp;|&nbsp;
			<?php if ($groupe_id): ?>
				<a class='normal' href="supprimer-groupe.php?groupe_id=<?php echo $groupe_id?>">Supprimer le groupe</a>&nbsp;|&nbsp;
			<?php endif;?>		
			<a class='normal' href="import_annuaire_result.php">Importer</a>
			<br/>
			
			<br/>
	  		Actions sur les contacts sélectionnés :
	  		<br/>
	  		 <input class="submit_button" value="Supprimer" type="submit" />
	  		<br/>
			<?php if (count($groupeArray)) : ?>  	
			<?php if ($groupe_id) : ?>
		 		<input class="submit_button" value="Retirer de <?php hecho($groupe_name) ?>" type="submit" onclick='javascript:retirer();'/>
				<input type='hidden' name='old_groupe_id' value='<?php echo $groupe_id?>' />
			<?php endif;?>	
			<br/>
			<select name='groupe_id' id='select_group' onchange='javascript:onChangeGroupSelect()'>
				<option value='0'>Ajouter à ... </option>
				<?php foreach($groupeArray as $groupe):?>
		 			<option value='<?php echo $groupe['id'] ?>'><?php hecho($groupe['name']) ?></option>
		 		<?php endforeach?>
			</select>
			<br/>
			
	
			<noscript>
				<input type='submit' value='changement de groupe'/>
			</noscript>
			<?php endif;?>
			<br/>	
	  	</div>
	
	  	<div class='listmail'>
		Sélectionner&nbsp;:&nbsp;<a class='normal' onclick='javascript:coche_case(true)'>Tous</a>,&nbsp;
		<a class='normal'  onclick='javascript:coche_case(false)'>aucun</a>	
		<?php foreach ($mailAnnuaireArray as $mailAnnuaire) : ?>
			<div class='normal' title='<?php echo $mailAnnuaire['mail_address']?>'>
				<input type="checkbox" name="checkbox_id[]" value="<?php echo $mailAnnuaire['id']; ?>" />
				<a href='edit-annuaire.php?id=<?php echo $mailAnnuaire['id']?>'><?php hecho($mailAnnuaire['description']?$mailAnnuaire['description']:$mailAnnuaire['mail_address']); ?>
				</a>
			</div>
			<?php endforeach?>
		</div>
		</form>
	<!--  </div>  -->
				
</div>