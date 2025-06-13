<?php

use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesStatistiques;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;

class ActesStatiqtiquesTest extends S2lowTestCase
{
    public function testGetVolumeWithoutTransactions()
    {
        /** @var ActesStatistiques $actesStatistiques */
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);
        $infos = $actesStatistiques->getInfo();

        $expected = [
            date("Y-m-01") => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            date("Y-01-01") => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            '1970-01-01' => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ]
        ];

        $this->assertEquals(
            $infos,
            $expected
        );
    }

    public function testGetVolumeWithTransaction()
    {
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);

        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            null,
            ""
        );

        $infos = $actesStatistiques->getInfo();

        $expected = [
            date("Y-m-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            date("Y-01-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            '1970-01-01' => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ]
        ];

        $this->assertEquals(
            $infos,
            $expected
        );
    }

    public function testGetVolumeWithTransmittedTransaction()
    {
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);

        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            null,
            ""
        );

        $actesTransactionsSQL->updateStatus($transaction_id, ActesStatusSQL::STATUS_TRANSMIS, "");

        $infos = $actesStatistiques->getInfo();

        $expected = [
            date("Y-m-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ],
            date("Y-01-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ],
            '1970-01-01' => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ]
        ];

        $this->assertEquals(
            $infos,
            $expected
        );
    }

    public function testGetVolumeWithTransmittedTransactionWithGroupAdminUser()
    {
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);
        $actesStatistiques->setGroup(1);

        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            null,
            ""
        );

        $actesTransactionsSQL->updateStatus($transaction_id, ActesStatusSQL::STATUS_TRANSMIS, "");

        $infos = $actesStatistiques->getInfo();

        $expected = [
            date("Y-m-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ],
            date("Y-01-01") => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ],
            '1970-01-01' => [
                'nb_envelope' => 1,
                'nb_envelope_poste' => 1,
                'volume' => null,
                'volume_poste' => null
            ]
        ];

        $this->assertEquals(
            $infos,
            $expected
        );
    }

    public function testGetVolumeWithTransmittedTransactionWithGroupAdminUserOtherColl()
    {
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);
        $actesStatistiques->setGroup(2);

        $actesCreator = new ActesCreator(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
        );
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_id = $actesCreator->createTransaction(
            ActesStatusSQL::STATUS_POSTE,
            null,
            ""
        );

        $actesTransactionsSQL->updateStatus($transaction_id, ActesStatusSQL::STATUS_TRANSMIS, "");

        $infos = $actesStatistiques->getInfo();

        $expected = [
            date("Y-m-01") => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            date("Y-01-01") => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ],
            '1970-01-01' => [
                'nb_envelope' => 0,
                'nb_envelope_poste' => 0,
                'volume' => null,
                'volume_poste' => null
            ]
        ];

        $this->assertEquals(
            $infos,
            $expected
        );
    }
}
