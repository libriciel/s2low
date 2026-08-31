<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Lib\SQLQuery;

/**
 *
 */
class AdminAuthorityEditHandlerTest extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testEditAuthority(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_POST = [
            'id' => '1',
            'name' => 'le nom',
            'siren' => '111',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'helios_ftp_dest' => 'test',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'api' => 1
        ];
        $crawler = $client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );

        $authority = new Authority(1);
        $authority->init();

        static::assertSame('le nom', $authority->get('name'));
        static::assertSame('111', $authority->get('siren'));
        static::assertSame(1, $authority->get('authority_group_id'));
        static::assertSame(null, $authority->get('agreement'));
        static::assertSame('test@test.ts', $authority->get('email'));
        static::assertSame('test@test.ts', $authority->get('default_broadcast_email'));
        static::assertSame('test@test.ts', $authority->get('broadcast_email'));
        static::assertSame(1, $authority->get('status'));
        static::assertSame(1, $authority->get('authority_type_id'));
        static::assertSame('te', $authority->get('address'));
        //WTF ??! Il y a des espaces en plus
        static::assertSame('29620               ', $authority->get('postal_code'));
        static::assertSame('SAN FRANCISCO', $authority->get('city'));
        static::assertSame('001', $authority->get('department'));
        static::assertSame('1', $authority->get('district'));
        static::assertSame('0000000000', $authority->get('telephone'));
        static::assertSame('0000000000', $authority->get('fax'));
        static::assertSame('test', $authority->get('helios_ftp_dest'));
        static::assertSame('fds@fds.r', $authority->get('email_mail_securise'));
        static::assertSame('fds', $authority->get('descr_mail_securise'));
    }

    /**
     * @throws \Exception
     */
    public function testCreateAuthority(): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_POST = [
            'name' => 'le nom',
            'siren' => '111',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'helios_ftp_dest' => 'test',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'api' => 1
        ];
        $crawler = $client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );

        $matches = [];
        preg_match('#{.*}#', $crawler->html(), $matches);
        $id = json_decode($matches[0])->id;

        $authority = new Authority($id);
        $authority->init();

        static::assertSame('le nom', $authority->get('name'));
        static::assertSame('111', $authority->get('siren'));
        static::assertSame(1, $authority->get('authority_group_id'));
        static::assertSame(null, $authority->get('agreement'));
        static::assertSame('test@test.ts', $authority->get('email'));
        static::assertSame('test@test.ts', $authority->get('default_broadcast_email'));
        static::assertSame('test@test.ts', $authority->get('broadcast_email'));
        static::assertSame(1, $authority->get('status'));
        static::assertSame(1, $authority->get('authority_type_id'));
        static::assertSame('te', $authority->get('address'));
        static::assertSame('29620               ', $authority->get('postal_code'));
        static::assertSame('SAN FRANCISCO', $authority->get('city'));
        static::assertSame('001', $authority->get('department'));
        static::assertSame('1', $authority->get('district'));
        static::assertSame('0000000000', $authority->get('telephone'));
        static::assertSame('0000000000', $authority->get('fax'));
        static::assertSame('test', $authority->get('helios_ftp_dest'));
        static::assertSame('fds@fds.r', $authority->get('email_mail_securise'));
        static::assertSame('fds', $authority->get('descr_mail_securise'));
    }

    /**
     * @dataProvider heliosPasstrans
     * @throws \Exception
     */
    public function testCreateAuthorityWithPasstrans(?int $id, bool $usePasstrans, bool $expected): void
    {
        $client = $this->client;
        $this->setUserWithRole(UserRole::SuperAdministrateur);                                                       // 2/ Le client ne modifie pas la variable _SERVER

        $_POST = [
            'id' => $id,
            'name' => 'le nom',
            'siren' => '111',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'helios_ftp_dest' => 'test',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'api' => 1
        ];
        $crawler = $client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );

        $matches = [];
        preg_match('#{.*}#', $crawler->html(), $matches);
        $id = json_decode($matches[0])->id;

        $authority = new Authority($id);
        $authority->init();

        static::assertSame($expected, $authority->get('helios_use_passtrans'));
    }

    public function heliosPasstrans(): iterable
    {
        // On créé la collectivité : helios_use_passtrans doit correspondre à la valeur
        // de la variable d'environnement
        yield [null,false,false];
        // La collectivité est éditée : la valeur de helios_use_passtrans doit rester la même quelle que soit la
        // variable d'environnement
        yield [1,true,false];
        yield [1,false,false];
    }

    /**
     * @throws \Exception
     */
    public function testAnAdminWhoDoesNotAdministerHeliosDoesNotEraseItsSettings(): void
    {
        $this->givenTheGroupAdminOfGroup1();
        $this->givenAuthority1AdministeredForHeliosByGroup(2);

        $this->whenTheFormIsPosted($this->authorityPostWithoutTheHeliosFields());

        $authority = new Authority(1);
        $authority->init();

        static::assertSame('helios_ftp_dest', $authority->get('helios_ftp_dest'));
        static::assertTrue($authority->getModulePerm(Module::HELIOS));
    }

    /**
     * @throws \Exception
     */
    public function testTheHeliosGroupAdminStillChangesHeliosSettings(): void
    {
        $this->givenTheGroupAdminOfGroup1();
        $this->givenAuthority1AdministeredForHeliosByGroup(1);

        $this->whenTheFormIsPosted($this->authorityPostWithoutTheHeliosFields());

        $authority = new Authority(1);
        $authority->init();

        static::assertSame('', $authority->get('helios_ftp_dest'));
        static::assertFalse($authority->getModulePerm(Module::HELIOS));
    }

    /**
     * L'utilisateur 13 est administrateur du groupe 1, celui de la collectivité 1.
     */
    private function givenTheGroupAdminOfGroup1(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenSirenAuthorizedForGroup1('123456789');
    }

    private function givenSirenAuthorizedForGroup1(string $siren): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'INSERT INTO authority_group_siren (authority_group_id, siren) VALUES (1, ?)',
            [$siren]
        );
    }

    private function givenAuthority1AdministeredForHeliosByGroup(int $heliosGroupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = 1, helios_group_id = ? WHERE id = 1',
            [$heliosGroupId]
        );
    }

    private function authorityPostWithoutTheHeliosFields(): array
    {
        return [
            'id' => '1',
            'name' => 'le nom',
            'siren' => '123456789',
            'authority_group_id' => 1,
            'agreement' => '',
            'email' => 'test@test.ts',
            'default_broadcast_email' => 'test@test.ts',
            'broadcast_email' => 'test@test.ts',
            'status' => 1,
            'authority_type_id' => 1,
            'address' => 'te',
            'postal_code' => '29620',
            'city' => 'SAN FRANCISCO',
            'department' => '001',
            'district' => '1',
            'telephone' => '0000000000',
            'fax' => '0000000000',
            'email_mail_securise' => 'fds@fds.r',
            'descr_mail_securise' => 'fds',
            'perm_' . Module::ACTES => 'on',
            'api' => 1
        ];
    }

    private function whenTheFormIsPosted(array $post): void
    {
        $_POST = $post;

        $crawler = $this->client->request(
            'POST',
            '/admin/authorities/admin_authority_edit_handler.php'
        );

        static::assertMatchesRegularExpression(
            '#"status":"ok"#',
            $crawler->html()
        );
    }
}
