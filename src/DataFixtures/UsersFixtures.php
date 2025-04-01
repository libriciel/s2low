<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;
use S2low\Infrastructure\Persistence\Entity\Users;

class UsersFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $usersData = [
            [
                'role' => 'SADM',
                'reference' => 'user-sadm',
                'email' => 'jean.dupont@montpellier.fr',
                'subjectDn' => 'CN=Jean Dupont, OU=MTP, O=Montpellier',
                'issuerDn' => 'CN=CertMontpellier, O=Ville',
                'name' => 'Dupont',
                'givenname' => 'Jean',
                'telephone' => '0601020304',
                'status' => 1,
                'certificate' => '---CERTIFICATE---',
                'certNotBefore' => '2023-01-01T00:00:00+00:00',
                'certNotAfter' => '2025-01-01T00:00:00+00:00',
                'certSerial' => 'ABC123456789',
                'login' => 'jdupont',
                'password' => 'hashed_password',
                'certificateRgs2Etoiles' => '---CERTIFICATE RGS 2E---',
                'certificateHash' => 'a1b2c3d4e5f6g7h8i9j0',
            ],
            [
                'role' => 'USER',
                'reference' => 'user-user',
                'email' => 'michel.duran@montpellier.fr',
                'subjectDn' => 'CN=Michel Duran, OU=MTP, O=Montpellier',
                'issuerDn' => 'CN=CertMontpellier, O=Ville',
                'name' => 'Duran',
                'givenname' => 'Michel',
                'telephone' => '0601020304',
                'status' => 1,
                'certificate' => '---CERTIFICATE---',
                'certNotBefore' => '2023-01-01T00:00:00+00:00',
                'certNotAfter' => '2025-01-01T00:00:00+00:00',
                'certSerial' => 'ABC123456789',
                'login' => 'mduran',
                'password' => 'hashed_password',
                'certificateRgs2Etoiles' => '---CERTIFICATE RGS 2E---',
                'certificateHash' => 'a1b2c3d4e5f6g7h8i9j0',
            ],
        ];

        foreach ($usersData as $userData) {
            $user = (new Users())
                ->setEmail($userData['email'])
                ->setSubjectDn($userData['subjectDn'])
                ->setIssuerDn($userData['issuerDn'])
                ->setName($userData['name'])
                ->setGivenname($userData['givenname'])
                ->setTelephone($userData['telephone'])
                ->setRole($userData['role'])
                ->setStatus($userData['status'])
                ->setCertificate($userData['certificate'])
                ->setCertNotBefore(new \DateTime($userData['certNotBefore']))
                ->setCertNotAfter(new \DateTime($userData['certNotAfter']))
                ->setCertSerial($userData['certSerial'])
                ->setLogin($userData['login'])
                ->setPassword($userData['password'])
                ->setCertificateRgs2Etoiles($userData['certificateRgs2Etoiles'])
                ->setCertificateHash($userData['certificateHash'])
                ->setAuthority($this->getReference('collectivite', Authorities::class))
                ->setAuthorityGroup($this->getReference('collectivite-groupe-1', AuthorityGroups::class));

            $this->addReference($userData['reference'], $user);
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AuthorityGroupsFixtures::class,
            AuthoritiesFixtures::class,
        ];
    }
}
