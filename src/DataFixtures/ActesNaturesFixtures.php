<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ActesNaturesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $actesNatures = [
            [
                'short_descr' => '',
                'descr' => ''
            ],
        ];

        $manager->flush();
    }
}
