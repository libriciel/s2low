<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use PHPUnit\S2lowTestCase;
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
        /** @var ActesStatistiques $actesStatistiques */
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);

        /** @var ActesCreator $actesCreator */
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

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
        /** @var ActesStatistiques $actesStatistiques */
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);

        /** @var ActesCreator $actesCreator */
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

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

    public function testGetVolumeWithTransmittedTransactionWithGroupAdminUser()
    {
        /** @var ActesStatistiques $actesStatistiques */
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);
        $actesStatistiques->setGroup(1);

        /** @var ActesCreator $actesCreator */
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

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
        /** @var ActesStatistiques $actesStatistiques */
        $actesStatistiques = $this->getObjectInstancier()->get(ActesStatistiques::class);
        $actesStatistiques->setGroup(2);

        /** @var ActesCreator $actesCreator */
        $actesCreator = $this->getObjectInstancier()->get(ActesCreator::class);

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
