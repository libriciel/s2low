<?php

class HeliosExportTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
	public function testExport(){

		$tmpFolder = new TmpFolder();
		$helios_responses_root = $tmpFolder->create();

		$pes_aller = __DIR__."/../../helios/fixtures/pes_aller_ok.xml";
		$pes_acquit = __DIR__."/../../helios/fixtures/pes_acquit.xml";

		$this->getObjectInstancier()->set(
			'helios_responses_root',
			$helios_responses_root
		);

		/** @var PesAllerRetriever $pesAllerRetriever */
		$pesAllerRetriever = $this->getObjectInstancier()->get(PesAllerRetriever::class);
		$filepath = $pesAllerRetriever->getPathForNonExistingFile(sha1_file($pes_aller));

		copy($pes_aller,$filepath);
		$heliosControler = $this->getObjectInstancier()->get(HeliosController::class);
		$transaction_id =  $heliosControler->importFile(8,$pes_aller,"pes_aller.xml");

		copy($pes_acquit,$this->getObjectInstancier()->get('helios_responses_root')."/pes_acquit.xml");

		$heliosTransactionSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

		$heliosTransactionSQL->setAcquitFilename($transaction_id,"pes_acquit.xml");

		$heliosExport = $this->getObjectInstancier()->get(HeliosExport::class);


		$tmp_folder = $tmpFolder->create();
		$heliosExport->export(1,$tmp_folder);

		$this->assertFileEquals($pes_aller,$tmp_folder."/$transaction_id/pes_aller.xml");
		$this->assertFileEquals($pes_acquit,$tmp_folder."/$transaction_id/pes_acquit.xml");

		$tmpFolder->delete($tmp_folder);
		$tmpFolder->delete($helios_responses_root);
	}
}