<?php

require_once __DIR__."/ActesCreator.php";


class ActesSignaturesTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;

	/**
	 * @throws Exception
	 */
	public function testSetSignature(){
		$tmpFolder = new TmpFolder();

		$tmp_dir = $tmpFolder->create();
		$actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

		$transaction_id = $actesCreator->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE,__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz",$tmp_dir);

		$actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
		$transction_info = $actesTransactionSQL->getInfo($transaction_id);

		$actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);
		$included_file_id = $actesIncludedFileSQL->addIncludedFile($transction_info['envelope_id'],$transaction_id,'application/pdf',42,'034-000000000-20170801-20170803E-AI-1-1_0.xml');


		$actesSignature = $this->getObjectInstancier()->get(ActesSignature::class);
		$actesSignature->setSignature($included_file_id,"ma signature");

		$actes_envelope_info = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->getInfo($transction_info['envelope_id']);


		$archivePath = $this->getObjectInstancier()->get('actes_files_upload_root').'/'.$actes_envelope_info['file_path'];

		$result_dir = $tmpFolder->create();
		$tgzExtractor = new TGZExtractor($result_dir);
		$tgzExtractor->extract($archivePath, false);

		$this->assertFileEquals(
			__DIR__."/fixtures/fichier-metier-signe.xml",
			$result_dir."/034-000000000-20170801-20170803E-AI-1-1_0.xml"
		);

		$tmpFolder->delete($tmp_dir);
		$tmpFolder->delete($result_dir);
	}

}