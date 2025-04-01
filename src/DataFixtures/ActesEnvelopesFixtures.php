<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use S2low\Infrastructure\Persistence\Entity\ActesEnvelopes;
use S2low\Infrastructure\Persistence\Entity\Users;

class ActesEnvelopesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $enveloppesData = [
            [
                'reference' => 'enveloppe-1',
                'userId' => $this->getReference('user-user', Users::class)->getId(),
                'submissionDate' => '2024-10-01 09:00:00',
                'siren' => '12345678900001',
                'department' => '34',
                'district' => 'A',
                'authorityTypeCode' => 1,
                'name' => 'Ville de Montpellier',
                'telephone' => '0467000000',
                'email' => 'contact@montpellier.fr',
                'filePath' => '/var/actes/envelopes/2024/10/envelope42.zip',
                'fileSize' => 204800,
                'returnMail' => 'accuse@montpellier.fr',
                'warningSent' => 'N',
                'isInCloud' => true,
                'notAvailable' => false,
            ],
        ];

        foreach ($enveloppesData as $enveloppeData) {
            $enveloppeEntity = (new ActesEnvelopes())
                ->setUserId($enveloppeData['userId'])
                ->setSubmissionDate(new \DateTime($enveloppeData['submissionDate']))
                ->setSiren($enveloppeData['siren'])
                ->setDepartment($enveloppeData['department'])
                ->setDistrict($enveloppeData['district'])
                ->setAuthorityTypeCode($enveloppeData['authorityTypeCode'])
                ->setName($enveloppeData['name'])
                ->setTelephone($enveloppeData['telephone'])
                ->setEmail($enveloppeData['email'])
                ->setFilePath($enveloppeData['filePath'])
                ->setFileSize($enveloppeData['fileSize'])
                ->setReturnMail($enveloppeData['returnMail'])
                ->setWarningSent($enveloppeData['warningSent'])
                ->setIsInCloud($enveloppeData['isInCloud'])
                ->setNotAvailable($enveloppeData['notAvailable']);

            $this->addReference($enveloppeData['reference'], $enveloppeEntity);
            $manager->persist($enveloppeEntity);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UsersFixtures::class,
        ];
    }
}
