<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\LegacyObjectsManager;

/**
 *
 */
class AdminAuthorityEditHandlerTest extends S2lowIntegrationTest
{
    /**
     * @throws \Exception
     */
    public function testEditAuthority(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();

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

        static::assertEquals('le nom', $authority->get('name'));
        static::assertEquals('111', $authority->get('siren'));
        static::assertEquals(1, $authority->get('authority_group_id'));
        static::assertEquals('', $authority->get('agreement'));
        static::assertEquals('test@test.ts', $authority->get('email'));
        static::assertEquals('test@test.ts', $authority->get('default_broadcast_email'));
        static::assertEquals('test@test.ts', $authority->get('broadcast_email'));
        static::assertEquals(1, $authority->get('status'));
        static::assertEquals(1, $authority->get('authority_type_id'));
        static::assertEquals('te', $authority->get('address'));
        //WTF ??! Il y a des espaces en plus
        static::assertEquals('29620               ', $authority->get('postal_code'));
        static::assertEquals('SAN FRANCISCO', $authority->get('city'));
        static::assertEquals('001', $authority->get('department'));
        static::assertEquals('1', $authority->get('district'));
        static::assertEquals('0000000000', $authority->get('telephone'));
        static::assertEquals('0000000000', $authority->get('fax'));
        static::assertEquals('test', $authority->get('helios_ftp_dest'));
        static::assertEquals('fds@fds.r', $authority->get('email_mail_securise'));
        static::assertEquals('fds', $authority->get('descr_mail_securise'));
    }

    /**
     * @throws \Exception
     */
    public function testCreateAuthority(): void
    {
        $certificatePem = $this->pemCertificateFactory->getFromString(
            file_get_contents(__DIR__ . '/../test/api/Eric_Pommateau_RGS_2_etoiles.pem')
        );

        $this->setUpUser($certificatePem->getContent(), $certificatePem->getHash());

        $client = $this->setUpClient(
            $certificatePem->getContent(),
            $certificatePem->getContentStrippedFromBegin()
        );                                                           // 2/ Le client ne modifie pas la variable _SERVER

        LegacyObjectsManager::setLegacyObjectInstancier();

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

        static::assertEquals('le nom', $authority->get('name'));
        static::assertEquals('111', $authority->get('siren'));
        static::assertEquals(1, $authority->get('authority_group_id'));
        static::assertEquals('', $authority->get('agreement'));
        static::assertEquals('test@test.ts', $authority->get('email'));
        static::assertEquals('test@test.ts', $authority->get('default_broadcast_email'));
        static::assertEquals('test@test.ts', $authority->get('broadcast_email'));
        static::assertEquals(1, $authority->get('status'));
        static::assertEquals(1, $authority->get('authority_type_id'));
        static::assertEquals('te', $authority->get('address'));
        static::assertEquals('29620               ', $authority->get('postal_code'));
        static::assertEquals('SAN FRANCISCO', $authority->get('city'));
        static::assertEquals('001', $authority->get('department'));
        static::assertEquals('1', $authority->get('district'));
        static::assertEquals('0000000000', $authority->get('telephone'));
        static::assertEquals('0000000000', $authority->get('fax'));
        static::assertEquals('test', $authority->get('helios_ftp_dest'));
        static::assertEquals('fds@fds.r', $authority->get('email_mail_securise'));
        static::assertEquals('fds', $authority->get('descr_mail_securise'));
    }
}
