<?php 
class ListeDiaHTML {
	
	private $allCollectivite;
	private $filtreAuthority;
	private $actionBox;
	private $fmin_submission_date;
	private $fmax_submission_date;
	
	private $status;
	private $fstatus;
	private $filename;
	
	public function addCollectivite($allCollectivite,$filtreAuthority){
		$this->allCollectivite = $allCollectivite;
		$this->filtreAuthority = $filtreAuthority;
	}
	
	public function addActionBox(){
		$this->actionBox = true;
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
		<select class="form-control" name="<?php echo $name ?>" >
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
<div id="filtering-area" style="display: block;">
	<form class="form-horizontal" action="<?php echo WEBSITE_SSL ?>/modules/dia/index.php" method="get" role="form">
            <div class="form-group">
                <label for="statu" class="col-md-3 control-label">Etat</label>
                <div class="col-md-3">
                    <?php echo $this->getHTMLSelect("status", $status, $fstatus) ?>
                </div>
                <label for="filename-contain" class="col-md-3 control-label">Le nom du fichier contient</label>
                <div class="col-md-3">
                    <input id="filename-contain" class="form-control" type="text" name="num" size="20" maxlength="25" value="<?php echo $filename ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="min_submission_date" class="col-md-3">Date de postage minimale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($fmin_submission_date,'min_submission_date') ?>
                </div>
                <label for="max_submission_date" class="col-md-3">Date de postage maximale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($fmax_submission_date,'max_submission_date') ?>
                </div>
            </div>
            <?php if ($this->allCollectivite) : ?>
            <div class="form-group">
                <label for="authority" class="col-md-3">Collectivité</label>			
		<div class="col-md-3">
                    <?php $this->getHTMLSelect("authority",$this->allCollectivite, $this->filtreAuthority) ?>
                </div>
            </div>
            <?php endif;?>
            <div class="form-group">
                <button type="submit" class="col-md-offset-3 col-md-3 btn btn-default">Filtrer</button>
                <a href="<?php echo WEBSITE_SSL ?>/modules/dia/index.php" class="col-md-offset-3 col-md-3 btn btn-default">
                    Remise à zéro
                </a>
            </div>
	</form>
</div>		
<?php if ($this->actionBox) : ?>
	<div id="actions_area">
 		<h2>Actions</h2>
    	<a href="<?php echo  WEBSITE_SSL ?>/modules/dia/dia_transaction_add.php" class="bouton">Importer un DIA (test)</a>
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
				value="<?php hecho($date) ?>"/>
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
			Choisir une date&nbsp;
		<?php endif;?>
		</a>
		<div class="date_picker" style="display: none;" id="datepicker_<?php echo $name?>_calendar">
		</div>
	<?php 
	}
	
	public function displayList($envelopes){
		?>
                    <table id="transactions-list" class="data-table table table-bordered">
                        <thead>
                            <tr>
                                <th id="transaction-id">Identifiant</th>
                                <th id="filename">Nom du fichier</th>
                                <th id="submission-date">Date de réception</th>
                                <th id="last-status-id">État</th>
                                <th id="detail">Détail</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php foreach($envelopes as $i => $envelope) : ?>
                            <tr>
                                <td headers="transaction-id"><?php echo $envelope['transaction_id'] ?></td>
                                <td headers="filename"><?php echo $envelope['filename'] ?></td>
                                <td headers="submission-date"><?php echo Helpers :: getDateFromBDDDate($envelope["submission_date"], true) ?></td>
                                <td headers="last-status-id"><?php echo TransactionDIA::getStatusName($envelope['last_status_id']) ?></td>
                                <td headers="detail">	
                                    <a href="<?php echo WEBSITE_SSL ?>/modules/dia/dia_detail.php?id=<?php echo $envelope['transaction_id']?>" class="icon">
                                        <img src="<?php echo WEBSITE_SSL ?>/custom/images/erreur.png" alt="image_modif" title="Afficher le détail" />
                                    </a>
                                </td>
                            </tr>
                    <?php endforeach;?>
                        </tbody>
                    </table>
		<?php 
	}

}


