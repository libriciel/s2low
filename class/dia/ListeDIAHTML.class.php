<?php 
class ListeDiaHTML {
	
	private $allCollectivite;
	private $filtreAuthority;
	private $fmin_submission_date;
	private $fmax_submission_date;
	
	private $status;
	private $fstatus;
	private $filename;
	
	public function addCollectivite($allCollectivite,$filtreAuthority){
		$this->allCollectivite = $allCollectivite;
		$this->filtreAuthority = $filtreAuthority;
	}
	

	public function setDate($fmin_submission_date,$fmax_submission_date){
		$this->fmin_submission_date = $fmin_submission_date;
		$this->fmax_submission_date = $fmax_submission_date;
	}
	
	public function setCritere($status,$fstatus,$filename){
		$this->status  = $status;
		$this->fstatus = $fstatus;
		$this->filename = $filename;
	}
	
	public function display($enveloppe){
		$this->displayForm();
		?>
		<h2>Liste des DIA</h2>
		<?php 
		if ($enveloppe) {
			 $this->displayList($enveloppe);
		} else {
			?> 
				Pas de transaction trouvée correspondante aux critères de filtrage
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
		global $status,	$fstatus,$filename,$fmin_submission_date,$fmax_submission_date;		
		?>
<h2 class="toggle_title" onclick="javascript:toggle_visibility('filtering_area');">Filtrage</h2>
<div id="filtering_area" style="display: block;">
	<form action="<?php echo WEBSITE_SSL ?>/modules/dia/index.php" method="get">
		<table>
			<tr>
				<td class="title">état&nbsp;:</td>
				<td class="value"><?php echo $this->getHTMLSelect("status", $status, $fstatus) ?></td>
				<td class="title">Le nom du fichier contient&nbsp;</td>
				<td class="value">
					<input type="text" name="num" size="20" maxlength="25" value="<?php echo $filename ?>" />
				</td>
			</tr>

				
			<tr>
				<td class="title">Date de postage minimale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmin_submission_date,'min_submission_date') ?>
				</td>
				<td class="title">Date de postage maximale&nbsp;:</td>
				<td class="value">
					<?php $this->datePicker($fmax_submission_date,'max_submission_date') ?>
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
					<a href="<?php echo WEBSITE_SSL ?>/modules/dia/index.php" class="bouton">
						Remise&nbsp;à &nbsp;zéro
					</a>
				</td>
			</tr>
		</table>
	</form>
</div>		


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
		
			<table class="transactions_list">
				<tr>
					<th>Identifiant</th>
					<th>Nom du fichier</th>
					<th>Date de réception</th>
					<th>État</th>
					
					<th>Détail</th>
				</tr>
			<?php foreach($envelopes as $i => $envelope) : ?>
				<tr>
					<td><?php echo $envelope['transaction_id'] ?></td>
					<td><?php echo $envelope['filename'] ?></td>
					<td><?php echo Helpers :: getDateFromBDDDate($envelope["submission_date"], true) ?></td>
					<td><?php echo TransactionDIA::getStatusName($envelope['last_status_id']) ?></td>
					<td>	<a href="<?php echo WEBSITE_SSL ?>/modules/dia/dia_detail.php?id=<?php echo $envelope['transaction_id']?>" 
									class="icon">
								<img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" 
										alt="image_modif" title="Afficher le détail" /></a>
						
				</tr>
			<?php endforeach;?>
			</table>
			

		</form>
		<?php 
	}

}


