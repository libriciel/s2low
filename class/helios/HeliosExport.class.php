<?php

use \Symfony\Component\Filesystem\Filesystem;

class HeliosExport {

	private $s2lowLogger;
	private $authoritySQL;
	private $heliosTransactionsSQL;
	private $pesAllerRetriever;
	private $helios_responses_root;
	private $cloudStorageFactory;

	public function __construct(
		S2lowLogger $s2lowLogger,
		AuthoritySQL $authoritySQL,
		HeliosTransactionsSQL $heliosTransactionsSQL,
		PesAllerRetriever $pesAllerRetriever,
		$helios_responses_root,
		CloudStorageFactory $cloudStorageFactory
	) {
		$this->s2lowLogger = $s2lowLogger;
		$this->authoritySQL = $authoritySQL;
		$this->heliosTransactionsSQL = $heliosTransactionsSQL;
		$this->pesAllerRetriever = $pesAllerRetriever;
		$this->helios_responses_root = $helios_responses_root;
		$this->cloudStorageFactory = $cloudStorageFactory;
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
				"Export des PES ALLER et PES Acquit de la collectivité %d vers %s (min_transaction_id=%d, max_transaction_id=%d)",
				$authority_id,
				$output_directory,
				$min_transaction_id,
				$max_trasaction_id
			)
		);

		$authority_info = $this->checkAuthority($authority_id);
		$this->s2lowLogger->info(sprintf("Traitement des actes de la collectivité %s",$authority_info['name']));

		$this->checkOutputDirectory($output_directory);

		$transactions_list = $this->heliosTransactionsSQL->getAllForExport($authority_id,$min_transaction_id,$max_trasaction_id);

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

		$filesystem = new Filesystem();
		$filesystem->mkdir($output_directory."/".$directory_name);

		$pes_aller_path = $this->pesAllerRetriever->getPath($transaction_info['sha1']);
		$pes_aller_destination = $output_directory."/$directory_name/{$transaction_info['filename']}";
		$filesystem->copy($pes_aller_path,$pes_aller_destination);
		$this->s2lowLogger->debug("[COPIE OK] $pes_aller_path -> $pes_aller_destination");

		if ($transaction_info['acquit_filename']){
			$pesAcquitCloudStorage = $this->cloudStorageFactory->getInstanceByClassName(PESAcquitCloudStorage::class);
			$pes_acquit_path = $pesAcquitCloudStorage->getPath($transaction_info['id']);
			$pes_acquit_destintation = $output_directory."/$directory_name/{$transaction_info['acquit_filename']}";
			$filesystem->copy($pes_acquit_path,$pes_acquit_destintation);
			$this->s2lowLogger->debug("[COPIE OK] $pes_acquit_path -> $pes_acquit_destintation");
		}
	}

}