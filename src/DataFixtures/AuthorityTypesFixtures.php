<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use S2low\Infrastructure\Persistence\Entity\AuthorityTypes;

class AuthorityTypesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $authorityTypes = [
            [
                'parent_type_reference' => null,
                'description' => 'cecie',
                'reference' => 'collectivite-type-1',
            ],
            [
                'parent_type_reference' => 'authority-type-1',
                'description' => 'cecie',
                'reference' => 'collectivite-type-2',
            ],
            [
                'parent_type_reference' => null,
                'description' => 'cecie',
                'reference' => 'collectivite-type-3',
            ],
            [
                'parent_type_reference' => 'authority-type-2',
                'description' => 'cecie',
                'reference' => 'collectivite-type-4',

            ],
        ];

        foreach ($authorityTypes as $authorityType) {
            $authorityTypeEntity = new AuthorityTypes();
            $authorityTypeEntity->setDescription($authorityType['description']);

            $this->addReference($authorityType['reference'], $authorityTypeEntity);
            $manager->persist($authorityTypeEntity);
        }

        $manager->flush();
    }
}
