<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use S2low\Domain\Model\ValueObject\StatusTransaction;
use S2low\Infrastructure\Persistence\Entity\ActesEnvelopes;
use S2low\Infrastructure\Persistence\Entity\ActesTransactions;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\Users;

class ActesTransactionsFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $actesTransactionsList = [
            [
                'enveloppe' => $this->getReference('enveloppe-1', ActesEnvelopes::class),
                'type' => 'A01',
                'natureCode' => 'NC001',
                'natureDescr' => 'Transmission de document',
                'title' => 'Acte de vente 2023',
                'subject' => 'Vente appartement Montpellier',
                'number' => 'TRX-2023-0001',
                'classification' => 'Confidentiel',
                'classificationDate' => '2023-05-10 10:00:00',
                'decisionDate' => '2023-05-15 15:30:00',
                'uniqueId' => 'abc123xyz456',
                'autoBroadcasted' => false,
                'archiveUrl' => null,
                'broadcastEmails' => null,
                'broadcastSendSources' => 2,
                'broadcasted' => true,
                'typeReponse' => null,
                'lastStatusId' => StatusTransaction::TRANSMIS->value,
                'userId' => $this->getReference('user-user', Users::class)->getId(),
                'authorityId' => $this->getReference('collectivite', Authorities::class)->getId(),
                'saeTransferIdentifier' => 'SAE001-202305',
                'antivirusCheck' => true,
                'classificationString' => 'Niveau 3 - Interne',
                'documentPapier' => false,
                'lu' => true,
            ],
            [
                'enveloppe' => $this->getReference('enveloppe-1', ActesEnvelopes::class),
                'type' => 'B02',
                'natureCode' => 'NC002',
                'natureDescr' => 'Demande de validation',
                'title' => 'Validation permis de construire',
                'subject' => 'Projet lotissement',
                'number' => 'TRX-2023-0002',
                'classification' => 'Public',
                'classificationDate' => '2023-06-01 09:00:00',
                'decisionDate' => null,
                'uniqueId' => 'def456uvw789',
                'autoBroadcasted' => true,
                'archiveUrl' => 'https://archives.example.org/doc2',
                'broadcastEmails' => 'admin@example.org',
                'broadcastSendSources' => 1,
                'broadcasted' => true,
                'typeReponse' => 1,
                'lastStatusId' => StatusTransaction::ATTENTE_TRANSMISSION->value,
                'userId' => $this->getReference('user-user', Users::class)->getId(),
                'authorityId' => $this->getReference('collectivite', Authorities::class)->getId(),
                'saeTransferIdentifier' => 'SAE002-202306',
                'antivirusCheck' => true,
                'classificationString' => 'Niveau 1 - Public',
                'documentPapier' => false,
                'lu' => false,
            ],
            [
                'enveloppe' => $this->getReference('enveloppe-1', ActesEnvelopes::class),
                'type' => 'C03',
                'natureCode' => 'NC003',
                'natureDescr' => 'Accusé de réception',
                'title' => 'Accusé pour acte 2',
                'subject' => 'Accusé réception acte TRX-2023-0002',
                'number' => 'TRX-2023-0003',
                'classification' => 'Interne',
                'classificationDate' => '2023-06-02 12:00:00',
                'decisionDate' => '2023-06-02 12:30:00',
                'uniqueId' => 'ghi789rst012',
                'autoBroadcasted' => false,
                'archiveUrl' => 'https://archives.example.org/doc3',
                'broadcastEmails' => 'recipient@example.org',
                'broadcastSendSources' => 3,
                'broadcasted' => false,
                'typeReponse' => 2,
                'lastStatusId' => StatusTransaction::TRANSMIS->value,
                'userId' => $this->getReference('user-user', Users::class)->getId(),
                'authorityId' => $this->getReference('collectivite', Authorities::class)->getId(),
                'saeTransferIdentifier' => 'SAE003-202306',
                'antivirusCheck' => false,
                'classificationString' => 'Niveau 2 - Restreint',
                'documentPapier' => true,
                'lu' => false,
            ],
        ];

        $id = 0;
        foreach ($actesTransactionsList as $acteTransaction) {
            $id++;
            $transaction = (new ActesTransactions())
                ->setType($acteTransaction['type'])
                ->setEnvelope($acteTransaction['enveloppe'])
                ->setNatureCode($acteTransaction['natureCode'])
                ->setNatureDescr($acteTransaction['natureDescr'])
                ->setTitle($acteTransaction['title'])
                ->setSubject($acteTransaction['subject'])
                ->setNumber($acteTransaction['number'])
                ->setClassification($acteTransaction['classification'])
                ->setClassificationDate(new \DateTime($acteTransaction['classificationDate']))
                ->setDecisionDate(
                    $acteTransaction['decisionDate'] ? new \DateTime($acteTransaction['decisionDate']) : null
                )
                ->setUniqueId($acteTransaction['uniqueId'])
                ->setAutoBroadcasted($acteTransaction['autoBroadcasted'])
                ->setArchiveUrl($acteTransaction['archiveUrl'])
                ->setBroadcastEmails($acteTransaction['broadcastEmails'])
                ->setBroadcastSendSources($acteTransaction['broadcastSendSources'])
                ->setBroadcasted($acteTransaction['broadcasted'])
                ->setTypeReponse($acteTransaction['typeReponse'])
                ->setLastStatusId($acteTransaction['lastStatusId'])
                ->setUserId($acteTransaction['userId'])
                ->setAuthorityId($acteTransaction['authorityId'])
                ->setSaeTransferIdentifier($acteTransaction['saeTransferIdentifier'])
                ->setAntivirusCheck($acteTransaction['antivirusCheck'])
                ->setClassificationString($acteTransaction['classificationString'])
                ->setDocumentPapier($acteTransaction['documentPapier'])
                ->setLu($acteTransaction['lu']);

            $this->addReference('actesTransactions' . $id, $transaction);
            $manager->persist($transaction);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AuthoritiesFixtures::class,
            UsersFixtures::class,
            ActesEnvelopesFixtures::class,
        ];
    }
}
