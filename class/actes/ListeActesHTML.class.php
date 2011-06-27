<?php 
class ListeActesHTML {
	
	private $allCollectivite;
	private $filtreAuthority;
	private $actionBox;
	private $fmin_submission_date;
	private $fmin_ack_date;
	private $fmax_submission_date;
	private $fmax_ack_date;
	private $transTypes;
	private $ftype;
	private $transNatures;
	private $fnature;
	private $status;
	private $fstatus;
	private $fnum;
	
	
	public function addCollectivite($allCollectivite,$filtreAuthority){
		$this->allCollectivite = $allCollectivite;
		$this->filtreAuthority = $filtreAuthority;
	}
	
	public function addActionBox(){
		$this->actionBox = true;
	}
	
	public function setDate($fmin_submission_date,$fmin_ack_date,$fmax_submission_date,$fmax_ack_date){
		$this->fmin_submission_date = $fmin_submission_date;
		$this->fmin_ack_date = $fmin_ack_date;
		$this->fmax_submission_date = $fmax_submission_date;
		$this->fmax_ack_date = $fmax_ack_date;
	}
	
	public function setCritere($transTypes, $ftype,$transNatures, $fnature,$status,$fstatus,$fnum){
		$this->transTypes = $transTypes; 
		$this->ftype = $ftype;
		$this->transNatures = $transNatures;
		$this->fnature = $fnature;
		$this->status  = $status;
		$this->fstatus = $fstatus;
		$this->num = $fnum;
	}
	
	public function display($enveloppe){
		$this->displayForm();
		?>
		<h2>Liste des enveloppes de transactions</h2>
		<?php 
		if ($enveloppe) {
			 $this->displayList($enveloppe);
		} else {
			?> 
				Pas de transaction trouvée correspondant aux critères de filtrage
			<?php 
		}
	}
	
	public function getHTMLSelect($name, $data, $selectedValue) {
		?>
		<select name="<?php echo $name ?>" >
			<option value="">Choisissez</option>	
	    	<?php foreach ($data as $key => $val) : ?>
				<option value="<?php echo $key ?>" <?php echo (strcmp($key, $selectedValue) == 0) ? 'selected="selected"' : "";?>>
					<?php echo $val?>
	      		</option>
	  		<?php endforeach;?>
		</select>	
	    <?php 
	}
	
	public function displayForm(){
		global $transTypes, $ftype,$transNatures, $fnature,$status, 
			$fstatus,$fnum,$fmin_submission_date,$fmin_ack_date,$fmax_submission_date,$fmax_ack_date;		
		?>
<h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering_area');">Filtrage</h2>
<div id="filtering_area" style="display: block;">
	<form action="<?php echo WEBSITE_SSL ?>/modules/actes/index.php" method="get">
		<table>
			<tr>
				<td class="title">Type de transaction&nbsp;:</td>
				<td class="value"><?php echo $this->getHTMLSelect("type", $transTypes, $ftype) ?></td>
				<td class="title">Nature d'actes&nbsp;:</td>
				<td class="value">
				<?php echo $this->getHTMLSelect("nature", $transNatures, $fnature) ?> </td>
			</tr>
			<tr>
				<td class="title">état&nbsp;:</td>
				<td class="value"><?php echo $this->getHTMLSelect("status", $status, $fstatus) ?></td>
				<td class="title">Le numéro contient&nbsp;</td>
				<td class="value">
					<input type="text" name="num" size="20" maxlength="25" value="<?php echo $fnum ?>" />
				</td>
			</tr>
			<tr>
				<td class="title">Date de postage minimale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmin_submission_date,'min_submission_date') ?>
				</td>
				<td class="title">Date d'acquittement minimale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmin_ack_date,'min_ack_date') ?>
				</td>
			</tr>
			<tr>
				<td class="title">Date de postage maximale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmax_submission_date,'max_submission_date') ?>
				</td>
				<td class="title">Date d'acquittement maximale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmax_ack_date,'max_ack_date') ?>
				</td>
			</tr>
			<?php if ($this->allCollectivite) : ?>
			<tr>
				<td class="title">Collectivité&nbsp;:</td>
				<td class="value">
					<?php $this->getHTMLSelect("authority",$this->allCollectivite, $this->filtreAuthority) ?>
				</td>
				<td >&nbsp;</td>
				<td >&nbsp;</td>
			</tr>
			<?php endif;?>
			<tr>
				<td colspan="2">
					<input class="submit_button" type="submit" value="Filtrer" />
				</td>
				<td colspan="2">
					<a href="<?php echo WEBSITE_SSL ?>/modules/actes/index.php" class="bouton">
						Remise&nbsp;à &nbsp;zéro
					</a>
				</td>
			</tr>
		</table>
	</form>
</div>		
<?php if ($this->actionBox) : ?>
<div id="actions_area">
 		<h2>Actions</h2>
    	<a href="<?php echo  WEBSITE_SSL ?>/modules/actes/actes_transac_add.php" class="bouton">Créer une transaction</a>
    	<a href="<?php echo  WEBSITE_SSL ?>/modules/actes/actes_transac_import.php" class="bouton">Importer une enveloppe</a>
		<a href="<?php echo  WEBSITE_SSL ?>/modules/actes/actes_batch_handle.php" class="bouton">Traitement par lots</a>
  	</div>
<?php endif;?>

		<?php 
	}
	
	private function datePicker($date,$name){
		global $fancyDate;
	?>
		<input id="<?php echo $name ?>" 
				name="<?php echo $name ?>" 
				type="hidden" 
				value="<?php echo htmlspecialchars($date) ?>"/>
		<script type="text/javascript">
		obj_<?php echo $name?> = new DatePicker('<?php echo $name?>', 'fr');
		</script>
		<a href="#datepicker" 
			id="datepicker_<?php echo $name?>_link" 
			class="datepicker_link" 
			onclick="javascript:obj_<?php echo $name?>.toggleDatePicker(); return false;">
		<?php if($date) : ?>
			<?php echo $fancyDate->getDateFrancais($date); ?>
		<?php else : ?>
			[&nbsp;Choisir une date&nbsp;]
		<?php endif;?>
		</a>
		<div class="date_picker" style="display: none;" id="datepicker_<?php echo $name?>_calendar">
		</div>
	<?php 
	}
	
	public function displayList($envelopes){
		?>
		<form id="div_chck" onsubmit="return afficheWarning()" action="<?php echo WEBSITE_SSL ?>/modules/actes/actes_transac_close.php" method="post">
			<div>
				<a href="#tedetis" onclick="javascript:show_all();" title="Déplier toutes les enveloppes">[&nbsp;Tout déplier&nbsp;]</a>
				<a href="#tedetis" onclick="javascript:hide_all();" title="Replier toutes les enveloppes">[&nbsp;Tout replier&nbsp;]</a>
			</div>
			<dl class="envelopes_list">
			<?php foreach($envelopes as $i => $envelope) : ?>
				<?php $this->displayEnvelope($envelope,$i);?>
			<?php endforeach;?>
			</dl>
			<div>
				<a href="#tedetis" onclick="GereChkbox('div_chck','1');" title="Tout sélectionner">[&nbsp;Tout sélectionner&nbsp;]</a>
				<a href="#tedetis" onclick="GereChkbox('div_chck','0');" title="Tout désélectionner">[&nbsp;Tout desélectionner&nbsp;]</a>
				<a href="#tedetis" onclick="GereChkbox('div_chck','2');" title="Inverser la sélection">[&nbsp;Inverser la sélection&nbsp;]</a>
			</div>
			<div class="action">
				Passer les transactions sélectionnées en état&nbsp;
				<select name="status">
					<option value="valid">Validé</option>
					<option value="invalid">Refusé</option>
				</select>
				<input type="submit" class="submit_button" value="Exécuter"/>
			</div>
		</form>
		<?php 
	}

	public function displayEnvelope($envelope,$i){
		global $sortWay;
		?>
			<dt>
				<a href="#tedetis" onclick="toggle_envelope_content(<?php echo $i ?>);" id="expander_<?php echo $i?>" class="expander">-</a>
				1 transaction de l'enveloppe n°<a href="<?php echo get_url(array("order" => "id","sortway" => $sortWay=='asc'?'desc':'asc')) ?>" 
								title="Trier par identifiant"><?php echo $envelope["envelope_id"] ?></a> 
				déposée le <a href="<?php echo get_url(array("order" => "submission_date","sortway" => $sortWay=='asc'?'desc':'asc')) ?>" 
								title="Trier par date de dépôt"><?php echo Helpers :: getDateFromBDDDate($envelope["submission_date"], true) ?></a>
				<?php if ($this->allCollectivite) : ?>
      				de la collectivité <?php echo htmlspecialchars($envelope['authority_name']) ?>
				<?php endif;?>
			</dt>
			<dd id="envelope_content_<?php echo $i ?>" class="envelope_content" style="display: block">
				<table class="transactions_list">
					<tr>
						<th>Sél.</th>
						<th>Type de transaction</th>
						<th>Numéro de l'acte</th>
						<th>Numéro Interne de l'acte</th>
						<th>Objet</th>
						<th>Nature</th>
						<th>Etat</th>
						<th>courrier ministère</th>
						<th>Suivie par</th>
						<th>Actions</th>
					</tr>
				
					<tr>
						 <td>
						 <?php if ($envelope['type'] == 1 && $envelope['current_status'] == 4) : ?>
						 	<input type="checkbox" 
						 			name="liste_id[]" 
						 			value="<?php echo  htmlspecialchars($envelope['transaction_id']) ;?>" 
						 			id="checkbox<?php echo  htmlspecialchars($envelope['transaction_id']) ;?>" />
						 <?php else: ?>
						 	&nbsp;
						 <?php endif; ?>
						</td>
						<td><?php echo $envelope['type_str'] ?></td>
						<td><?php echo $envelope['transaction_id'] ?></td>
						<td><?php echo  htmlspecialchars($envelope['number']) ?></td>
						<td class="long_field"><?php echo nl2br(htmlspecialchars(Helpers :: truncateString($envelope['subject']))) ?></td>
						<td><?php echo $envelope["nature_descr"] ?></td>
						<td><?php echo $envelope['current_status_name'] ?></td>
						<td>
							<?php 
							foreach ($envelope['courrier_info'] as $id => $info): ?>
								<a href="<?php echo WEBSITE_SSL ?>/modules/actes/actes_transac_show.php?id=<?php echo $id ?>">
									<?php echo $info["type_str"] ?>
									(<?php echo $info["sens"] ?>) 
								</a>
								<br/>
							<?php endforeach; ?>
						</td>
						<td><?php echo $envelope['givenname']." ".$envelope['name'] ?></td>
						<td>
							<a href="<?php echo WEBSITE_SSL ?>/modules/actes/actes_transac_show.php?id=<?php echo $envelope['transaction_id']?>" 
									class="icon">
								<img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" 
										alt="image_modif" title="Afficher le détail" />
							</a>
						 	<?php if ($envelope["archive_url"]) : ?>
						 		 <a href="<?php echo $envelope["archive_url"] ?>" 
						 		 	class="icon">
						 		 	<img src="<?php echo WEBSITE_SSL ?>/custom/images/icone_archivage.png" 
						 		 			alt="image_archivage" title="Accéder à  l'archivage de cette transaction" />
						 		 </a>
						 	<?php endif;?>
						 </td>
					</tr>
						
				</table>
			</dd>
		<?php 
	}
}


