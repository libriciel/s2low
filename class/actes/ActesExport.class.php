<?php

use \Symfony\Component\Filesystem\Filesystem;

class ActesExport {

	private $s2lowLogger;
	private $authoritySQL;
	private $actesTransactionsSQL;
	private $actesRetriever;
	private $actesIncludedFileSQL;
	private $actesTamponne;

	private $tamponner_fichier = false;

	public function __construct(
		S2lowLogger $s2lowLogger,
		AuthoritySQL $authoritySQL,
		ActesTransactionsSQL $actesTransactionsSQL,
		ActesRetriever $actesRetriever,
		ActesIncludedFileSQL $actesIncludedFileSQL,
		ActeTamponne $acteTamponne
	) {
		$this->s2lowLogger = $s2lowLogger;
		$this->authoritySQL = $authoritySQL;
		$this->actesTransactionsSQL = $actesTransactionsSQL;
		$this->actesRetriever = $actesRetriever;
		$this->actesIncludedFileSQL = $actesIncludedFileSQL;
		$this->actesTamponne = $acteTamponne;
	}

	public function setTamponnerFichier(bool $tamponner_fichier){
		$this->tamponner_fichier = $tamponner_fichier;
	}

	/**
	 * @param int $authority_id
	 * @param string $output_directory
	 * @param int $min_transaction_id
	 * @param int $max_trasaction_id
	 * @throws Exception
	 */
	public function export(
		int $authority_id,
		string $output_directory,
		int $min_transaction_id = 0,
		int $max_trasaction_id=ActesTransactionsSQL::MAX_ID
	):void {

		$this->s2lowLogger->info(
			sprintf(
				"Export des transactions actes de la collectivité %d vers %s (min_transaction_id=%d, max_transaction_id=%d)",
				$authority_id,
				$output_directory,
				$min_transaction_id,
				$max_trasaction_id
			)
		);

		$authority_info = $this->checkAuthority($authority_id);
		$this->s2lowLogger->info(sprintf("Traitement des actes de la collectivité %s",$authority_info['name']));

		$this->checkOutputDirectory($output_directory);

		$transactions_list = $this->actesTransactionsSQL->getAllForExport($authority_id,$min_transaction_id,$max_trasaction_id);

		if (! $transactions_list){
			$this->s2lowLogger->info("Aucune transaction ne correspond aux critères");
			return;
		}

		$this->s2lowLogger->info(count($transactions_list)." transaction(s) trouvée(s)");


		foreach($transactions_list as $transaction_info){
			$this->exportOneTransaction($transaction_info,$output_directory);
		}
	}




	/**
	 * @param int $authority_id
	 * @return array
	 * @throws UnrecoverableException
	 */
	private function checkAuthority(int $authority_id):array{
		$authority_info = $this->authoritySQL->getInfo($authority_id);

		if (! $authority_info){
			throw new UnrecoverableException("La collectivité $authority_id n'existe pas");
		}
		return $authority_info;
	}


	/**
	 * @param string $output_directory
	 * @throws UnrecoverableException
	 */
	private function checkOutputDirectory(string $output_directory):void{
		$filesystem = new Filesystem();
		if (
			! $filesystem->exists($output_directory) ||
			! is_dir($output_directory) ||
			! is_writable($output_directory)
		){
			throw new UnrecoverableException(
				"Le répertoire $output_directory n'existe pas ou n'est pas accessible en écriture"
			);
		}
	}

	/**
	 * @param $transaction_info
	 * @param $output_directory
	 * @throws Exception
	 */
	private function exportOneTransaction($transaction_info,$output_directory){
		$this->s2lowLogger->info("Export de la transaction #ID {$transaction_info['id']}");

		$directory_name = $transaction_info['id'];

		$directory = $output_directory."/".$directory_name;
		$filesystem = new Filesystem();
		$filesystem->mkdir($directory);

		$actes_path = $this->actesRetriever->getPath($transaction_info['file_path']);

		$all_file = $this->actesIncludedFileSQL->getAll($transaction_info['id']);

		$tmpFolder = new TmpFolder();
		$tmp_folder = $tmpFolder->create();
		$tgzExctactor = new TGZExtractor($tmp_folder);

		foreach($all_file as $file_name){
			$file_name = $file_name['filename'];
			$tgzExctactor->extract($actes_path,$file_name);
			$destination_path = $directory."/".$file_name;
			if ($this->tamponner_fichier && pathinfo($tmp_folder."/$file_name",PATHINFO_EXTENSION) == 'pdf'){
				$file_string = $this->actesTamponne->tamponnerPDF(
					$tmp_folder."/$file_name",
					$transaction_info['id']
				);
				$this->s2lowLogger->debug("[TAMPON OK] $file_name");
				$filesystem->dumpFile($destination_path,$file_string);
			} else {
				$filesystem->copy($tmp_folder."/$file_name",$destination_path);
			}
			$this->s2lowLogger->debug("[COPIE OK] $file_name -> $destination_path");
		}

		$status_info = $this->actesTransactionsSQL->getStatusInfo(
			$transaction_info['id'],
			ActesStatusSQL::STATUS_ACQUITTEMENT_RECU
		);
		if ($status_info['flux_retour']) {
			$acquit_filepath = $directory . "/ACK_{$directory_name}.xml";
			$filesystem->dumpFile($acquit_filepath, $status_info['flux_retour']);
			$this->s2lowLogger->debug("[DUMP OK] $acquit_filepath");
		}
	}

}