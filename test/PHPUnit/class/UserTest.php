<?php

use S2lowLegacy\Class\User;

class UserTest extends S2lowTestCase
{
    public function testGetIdFromCertData()
    {
        $user = new User();
        $ids = $user->getIdFromCertData('Q1pUbEb5DK53BkYf0arDl/3zl5U=');
        $this->assertEquals(1, $ids[0]);
    }

    public function testgetNbUserWithMyCertificate()
    {
        $user = new User();
        $user->setId(2);
        $user->init();
        $this->assertEquals(1, $user->getNbUserWithMyCertificate());
    }

    public function testGetCertificateInfo()
    {
        $user = new User();
        $user->setId(1);
        $user->init();
        $info = $user->getCertificateInfo();
        $this->assertEquals('Q1pUbEb5DK53BkYf0arDl/3zl5U=', $info['certificate_hash']);
    }

    /**
     * @dataProvider userEditions
     */
    public function testCanEditUser(int $editorId, int $editedUserId, bool $canEdit): void
    {
        $editor = new User($editorId);
        $editor->init();

        self::assertSame($canEdit, $editor->canEditUser($editedUserId));
    }

    public function userEditions(): iterable
    {
        yield 'admin de collectivité, utilisateur de sa collectivité' => [2, 5, true];
        yield 'admin de collectivité, super admin de sa collectivité' => [2, 1, false];
        yield 'admin de collectivité, admin de groupe de sa collectivité' => [6, 7, false];
        yield 'admin de groupe, admin de collectivité de son groupe' => [7, 2, true];
        yield 'admin de groupe, super admin de son groupe' => [7, 1, false];
        yield 'super admin, admin de groupe' => [1, 7, true];
    }

    public function testGetDn()
    {
        $user = new User();
        $certificate = [
            'C' => "FR",
            'O' => "CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE",
            'OU' => [
                "CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE",
                "0002 28440002500011",
                "Systèmes d'information",
            ],
            'organizationIdentifier' => "NTRFR-28440002500011",
            'L' => "NANTES CEDEX 2",
            'CN' => "iparapheur.cdg44.fr",
            'serialNumber' => "0001"
            ];

        $this->assertEquals(
            "/C=FR/O=CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE/OU=CENTRE DE GESTION DE LA FONCTION PUBLIQUE DE LOIRE ATLANTIQUE/OU=0002 28440002500011/OU=Systèmes d'information/organizationIdentifier=NTRFR-28440002500011/L=NANTES CEDEX 2/CN=iparapheur.cdg44.fr/serialNumber=0001",
            $user->getDn($certificate)
        );
    }

    public function testSaveUser(): void
    {
        $user = new User();
        $user->set('email', 'em@i.l');
        $user->set('certFilePath', __DIR__ . '/fixtures/certificats/dateOk/fullchain.pem');
        $user->set('name', 'name');
        $user->set('givenname', 'givenName');
        $user->set('role', 'USER');
        $user->set('telephone', '0000000000');
        $user->set('authority_id', 1);
        $user->set('status', 1);
        static::assertTrue($user->save());

        $userId = $user->getId();

        $retrievedUser = new User($userId);
        $retrievedUser->init();

        static::assertSame($retrievedUser->get('email'), 'em@i.l');
        static::assertSame($retrievedUser->get('name'), 'name');
        static::assertSame($retrievedUser->get('givenname'), 'givenName');
        static::assertSame($retrievedUser->get('role'), 'USER');
        static::assertSame($retrievedUser->get('telephone'), '0000000000');
        static::assertSame($retrievedUser->get('authority_id'), 1);
        static::assertSame($retrievedUser->get('status'), 1);
    }

    /**
     * @dataProvider roles
     * @param string $role
     * @param array $availableRoles
     * @return void
     */
    public function testGetAvailableRolesForUserCreation(string $role, array $availableRoles): void
    {
        $user = new User();
        $user->set('role', $role);
        self::assertSame(
            $availableRoles,
            $user->getAvailableRolesForUserCreation()
        );
    }

    public function roles(): iterable
    {
        $restrictedRoles = [
            User::ADM => 'Administrateur collectivité',
            User::USER => 'Utilisateur'
        ];

        $allRoles = [
            User::SADM => 'Super administrateur',
            User::GADM => 'Administrateur de groupe',
            User::ADM => 'Administrateur collectivité',
            User::USER => 'Utilisateur',
            User::ARCH => 'Archiviste'
        ];
        return [
            [User::SADM, $allRoles ],
            [User::GADM, $restrictedRoles],
            [User::ADM, $restrictedRoles],
            [User::USER,$restrictedRoles],
            [User::ARCH,$restrictedRoles],
        ];
    }

    /**
     * @dataProvider roleIsArchivist
     */
    public function testIsArchivist(string $role, bool $isArchivist): void
    {
        $user = new User();
        $user->set('role', $role);
        self::assertSame($isArchivist, $user->isArchivist());
    }

    public function roleIsArchivist(): array
    {
        return
            [   [User::SADM, false ],
                [User::GADM, false ],
                [User::ADM, false ],
                [User::USER, false ],
                [User::ARCH, true ]
                ]
            ;
    }
}
