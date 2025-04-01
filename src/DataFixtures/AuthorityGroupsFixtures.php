<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;

class AuthorityGroupsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $authorityGroups = [
            [
                'name' => 'authority-group-1',
                'status' => '1',
                'reference' => 'collectivite-groupe-1',
            ],
            [
                'name' => 'collectivite-groupe-2',
                'status' => '1',
                'reference' => 'collectivite-groupe-2',
            ],
            [
                'name' => 'collectivite-groupe-3',
                'status' => '0',
                'reference' => 'collectivite-groupe-3',
            ],
        ];

        foreach ($authorityGroups as $authorityGroup) {
            $authorityGroupEntity = new AuthorityGroups();
            $authorityGroupEntity->setName($authorityGroup['name']);
            $authorityGroupEntity->setStatus($authorityGroup['status']);
            $this->addReference($authorityGroup['reference'], $authorityGroupEntity);
            $manager->persist($authorityGroupEntity);
        }

        $manager->flush();
    }
}
