<?php

class ActesTransactionsSQLTest extends S2lowTestCase {

	use ActesUtilitiesTestTrait;
	use PastellConfigurationTestTrait;

    private const NOFILE = "nofile";
    private const ENVOYABLE = "Envoyable";
    private const NON_ENVOYABLE = "Non Envoyable";

    /**
     * @return ActesTransactionsSQL
     */
    private function getActesTransactionsSQL(){
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    /**
     * @return ActesEnvelopeSQL
     */
    private function getEnveloppeSQL(){
        return $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);
    }

	/**
	 * @throws Exception
	 */
    public function testGetLastArchiveFromStatus(){
		$this->configurePastell();
        $transaction_id =$this->createTransaction('14');
        $this->getActesTransactionsSQL()->updateStatus($transaction_id,12,"test");

        $result_1 = $this->getActesTransactionsSQL()->getArchiveFromStatusWithSAE(12);
        $this->assertNotEmpty($result_1);

        $result = $this->getActesTransactionsSQL()->getLastArchiveFromStatus(12,date("Y-m-d"));
        $this->assertEquals($result_1,$result);

    }

	/**
	 * @throws Exception
	 */
    public function testGetNbByStatus(){
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->assertEquals(1,$this->getActesTransactionsSQL()->getNbByStatus(ActesStatusSQL::STATUS_POSTE));
    }

	/**
	 * @throws Exception
	 */
    public function testCreateRelatedTransaction(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($transaction_id);
        $related_transaction_id = $this->getActesTransactionsSQL()->createRelatedTransaction(
            $transaction_info['envelope_id'],
            2,
            '2017-07-25',
            $transaction_id
        );
        $this->assertNotNull($related_transaction_id);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($related_transaction_id);
        $this->assertEquals($transaction_id,$transaction_info['related_transaction_id']);

    }

	/**
	 * @throws Exception
	 */
    public function testGuessUniqueId(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $unique_id = $this->getActesTransactionsSQL()->guessUniqueId($transaction_id);
        $this->assertEquals("034-000000000-20170701-20170728C-AI",$unique_id);
    }

	/**
	 * @throws Exception
	 */
    public function testUpdateStatus(){
		$transaction_id =$this->createTransaction('14');
		$this->getActesTransactionsSQL()->updateStatus($transaction_id,1,"foo");
		$info = $this->getActesTransactionsSQL()->getStatusInfo($transaction_id,1);
		$this->assertEquals("foo",$info['message']);
	}

	/**
	 * @throws Exception
	 */
    public function testUpdateStatusTooLong(){
		$transaction_id =$this->createTransaction('14');
		$message = str_repeat("1234567890",53);
		$this->getActesTransactionsSQL()->updateStatus($transaction_id,1,$message);
		$info = $this->getActesTransactionsSQL()->getStatusInfo($transaction_id,1);
		$this->assertEquals(512,strlen($info['message']));
	}

	/**
	 * @throws Exception
	 */
	public function testgetByStatusSinceDate(){
		$transaction_id =$this->createTransaction(ActesStatusSQL::STATUS_TRANSMIS);
		$this->assertEmpty(
			$this->getActesTransactionsSQL()->getByStatusSinceDate(
				ActesStatusSQL::STATUS_TRANSMIS,
				"1970-01-01")
		);
		$this->assertEquals(
			[$transaction_id],
			$this->getActesTransactionsSQL()->getByStatusSinceDate(
				ActesStatusSQL::STATUS_TRANSMIS,
				date("Y-m-d",strtotime("+2 days")))
			);
	}


    private function assertArrayContains($arrayHaystack,$arrayNeedle,$number){
        $numberFindings = 0;
        foreach ($arrayNeedle as $needle){
            if(in_array($needle,$arrayHaystack)){
                $numberFindings++;
            }
        }
        $this->assertEquals($number,$numberFindings);
    }

    private function assertArrayContainsNone($arrayHaystack,$arrayNeedle){
	    $contains = false;
        foreach ($arrayNeedle as $needle){
            if(in_array($needle,$arrayHaystack)){
                $contains = true;
            }
        }
        $this->assertEquals(false,$contains);
    }

    /**
     * @param array $transactions
     * @return array
     */
    private function initAutorites(array $transactions){
        $autorites= array_unique (array_map( function ($u) {return $u[0]; }, $transactions));

        foreach ($autorites as $autorite){
            $this->configurePastell($autorite);
        }
    }

    /**
     * @param array $transactions
     * @return array
     */
    private function initDatabase(array $transactions): array
    {
        $transactionsIdParAutoriteEnvoyable = [];
        foreach ($transactions as $transaction)
        {
            list($autorite, $envoyable, $statut) = $transaction;
                $enveloppe_id = $this->getEnveloppeSQL()->create("1", self::NOFILE);
                $transactionsIdParAutoriteEnvoyable[$autorite][$envoyable][] =  $this->getActesTransactionsSQL()->create($enveloppe_id, $statut, 1, $autorite);
        }
        return $transactionsIdParAutoriteEnvoyable;
    }

    /**
     * @param $transactions
     * @param $limit
     * @param $attendu
     * @dataProvider providerForNombreTransactionsAEnvoyer
     * @throws Exception
     *
     * Ce test vérifie :
     *  -> que le nombre de transactions par autorite correspond bien à ce qui est attendu
     *  -> qu'aucune transaction non envoyable n'est envoyée
     */
    
    public function testNombreTransactionsAEnvoyer($transactions, $limit, $attendu){

        $this->initAutorites($transactions);

        $transactionsIdParAutoriteEnvoyable = $this->initDatabase($transactions);

        $idsTransactionsAEnvoyer = $this->getActesTransactionsSQL()->getTransactionToSendSAEWithLimit($limit);

        foreach ($transactionsIdParAutoriteEnvoyable as $autorite=>$transactionsIdParEnvoyable) {
            if(isset($transactionsIdParEnvoyable[self::NON_ENVOYABLE])) {
                $this->assertArrayContainsNone($idsTransactionsAEnvoyer,$transactionsIdParEnvoyable[self::NON_ENVOYABLE]);
            }
            if(isset($transactionsIdParEnvoyable[self::ENVOYABLE])) {
                $this->assertArrayContains($idsTransactionsAEnvoyer,$transactionsIdParEnvoyable[self::ENVOYABLE],$attendu[$autorite]);
            }
        }
    }

    /**
     * @return array
     * structure de l'array :
     *  [ [ array $transactions , int $limit, array $attendu ] ]
     * $transactions renseigne les transactions à créer en base de données :
     *    [ int autorite, const envoyable, const status ]
     * $limite donne le nombre limite par autorité de transactions en cours de transmission au SAE simultanément
     * $attendu a la forme
     * [ autorite => int nbTransactions ]
     *  avec nbTransactions le nombre de transactions à transmettre
     */
    public function providerForNombreTransactionsAEnvoyer(){
        return [
            // TEST 1
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ]
                ],
                4,
                ["1"=>4]
            ],
            // TEST 2
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::NON_ENVOYABLE, ActesStatusSQL::STATUS_ENVOYE_AU_SAE ],
                    [ 1, self::NON_ENVOYABLE, ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ],
                    [ 1, self::NON_ENVOYABLE, ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ]
                ],
                4,
                ["1"=>1]
            ],
            // testSAEDifferentsEtatsAvecLimite
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::NON_ENVOYABLE,ActesStatusSQL::STATUS_ENVOYE_AU_SAE ],
                    [ 1, self::NON_ENVOYABLE,ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ],
                    [ 1, self::NON_ENVOYABLE,ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ]
                ],
                3,
                ["1"=>0]
            ],
            // testSAEDeuxAutoritesAvecLimite
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 2, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 2, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ]
                ],
                1,
                ["1"=>1,"2"=>1]
            ],
            // testSAEDeuxAutoritesAvecLimite2
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 2, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 2, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ]
                ],
                2,
                ["1"=>2,"2"=>2]
            ],
            // testSAEDeuxAutorites3
            [
                [
                    [ 1, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 1, self::NON_ENVOYABLE, ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ],
                    [ 2, self::ENVOYABLE, ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ],
                    [ 2, self::NON_ENVOYABLE, ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ]
                ],
                2,
                ["1"=>1,"2"=>1]
            ]
        ];
    }
}