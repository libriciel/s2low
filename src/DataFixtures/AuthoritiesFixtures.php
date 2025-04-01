<?php

namespace S2low\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use S2low\Infrastructure\Persistence\Entity\Authorities;
use S2low\Infrastructure\Persistence\Entity\AuthorityGroups;
use S2low\Infrastructure\Persistence\Entity\AuthorityTypes;

class AuthoritiesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $authoritiesData = [
            [
                'status' => 1,
                'name' => 'Ville de Montpellier',
                'email' => 'contact@montpellier.fr',
                'extSiret' => '12345',
                'siren' => '123456789',
                'agreement' => 'AGR-2023-001',
                'address' => '1 place Georges Frêche',
                'postalCode' => '34000',
                'city' => 'Montpellier',
                'telephone' => '0467000001',
                'fax' => '0467000002',
                'department' => '34',
                'district' => 'A',
                'authorityGroupId' => 2,
                'broadcastEmail' => 'broadcast@montpellier.fr',
                'emailMailSecurise' => 'securise@montpellier.fr',
                'defaultBroadcastEmail' => 'default@montpellier.fr',
                'heliosFtpPassword' => 'ftpPass123',
                'heliosFtpLogin' => 'ftpLogin',
                'heliosFtpDest' => 'dest1',
                'saeWSDL' => 'http://sae/wsdl',
                'saeLogin' => 'sae_user',
                'saePassword' => 'sae_pass',
                'saeIdVersant' => 'IDV-001',
                'saeIdArchive' => 'ARCH-001',
                'saeNumeroAggrement' => 'SAE-AGR-2023',
                'saeOriginatingAgency' => 'AGENCY-01',
                'newNotification' => true,
                'pastellUrl' => 'https://pastell.montpellier.fr',
                'pastellLogin' => 'pastell_admin',
                'pastellPassword' => 'pastell_pass',
                'pastellId' => 99,
                'heliosDoNotVerifyNomFicUnicity' => false,
                'descrMailSecurise' => 'Canal sécurisé recommandé',
                'heliosUsePasstrans' => true,
            ]
        ];

        $authoritiesList = [];
        $id = 0;
        foreach ($authoritiesData as $authorityData) {
            $authority = (new Authorities())
                ->setStatus($authorityData['status'])
                ->setName($authorityData['name'])
                ->setEmail($authorityData['email'])
                ->setExtSiret($authorityData['extSiret'])
                ->setSiren($authorityData['siren'])
                ->setAgreement($authorityData['agreement'])
                ->setAddress($authorityData['address'])
                ->setPostalCode($authorityData['postalCode'])
                ->setCity($authorityData['city'])
                ->setTelephone($authorityData['telephone'])
                ->setFax($authorityData['fax'])
                ->setDepartment($authorityData['department'])
                ->setDistrict($authorityData['district'])
                ->setBroadcastEmail($authorityData['broadcastEmail'])
                ->setEmailMailSecurise($authorityData['emailMailSecurise'])
                ->setDefaultBroadcastEmail($authorityData['defaultBroadcastEmail'])
                ->setHeliosFtpPassword($authorityData['heliosFtpPassword'])
                ->setHeliosFtpLogin($authorityData['heliosFtpLogin'])
                ->setHeliosFtpDest($authorityData['heliosFtpDest'])
                ->setSaeWsdl($authorityData['saeWSDL'])
                ->setSaeLogin($authorityData['saeLogin'])
                ->setSaePassword($authorityData['saePassword'])
                ->setSaeIdVersant($authorityData['saeIdVersant'])
                ->setSaeIdArchive($authorityData['saeIdArchive'])
                ->setSaeNumeroAggrement($authorityData['saeNumeroAggrement'])
                ->setSaeOriginatingAgency($authorityData['saeOriginatingAgency'])
                ->setNewNotification($authorityData['newNotification'])
                ->setPastellUrl($authorityData['pastellUrl'])
                ->setPastellLogin($authorityData['pastellLogin'])
                ->setPastellPassword($authorityData['pastellPassword'])
                ->setPastellIdE($authorityData['pastellId'])
                ->setHeliosDoNotVerifyNomFicUnicity($authorityData['heliosDoNotVerifyNomFicUnicity'])
                ->setDescrMailSecurise($authorityData['descrMailSecurise'])
                ->setHeliosUsePasstrans($authorityData['heliosUsePasstrans'])
                ->setAuthorityType($this->getReference('collectivite-type-1', AuthorityTypes::class))
                ->setAuthorityGroup($this->getReference('collectivite-groupe-1', AuthorityGroups::class));

            $this->addReference('collectivite', $authority);
            $manager->persist($authority);
        }

        $manager->flush();
    }


    public function getDependencies(): array
    {
        return [
            AuthorityTypesFixtures::class,
            AuthorityGroupsFixtures::class,
        ];
    }
}
