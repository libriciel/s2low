<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use S2lowLegacy\Class\Authority;
use S2lowTestCase;

/**
 *
 */
class AuthorityTest extends S2lowTestCase
{
    /**
     * Test simple d'init utilisant les données en BDD de S2lowTestCase
     * @return void
     */
    public function testSimpleInitAndGet()
    {
        $authority = new Authority(1);
        $authority->init();
        static::assertEquals(
            'Bourg-en-Bresse',
            $authority->get('name')
        );
        static::assertEquals(
            'helios_ftp_dest',
            $authority->get('helios_ftp_dest')
        );
    }

    public function mails(): array
    {
        return[
            [ 'email','email',''],
            [ 'broadcast_email','broadcast_email',''],
            ['default_broadcast_email','email',''],
        ];
    }

    /**
     * Test simple d'init utilisant les données en BDD de S2lowTestCase
     * @return void
     * @throws Exception
     * @dataProvider mails
     */
    public function testSimpleMail(string $mailField, string $value1, string $value2)
    {
        $authority = new Authority(1);
        $authority->init();
        $authority->set($mailField, $value1);
        $authority->save(false, false);

        $authority1 = new Authority(2);
        $authority1->init();
        static::assertEquals(
            $value1,
            $authority1->get($mailField)
        );
        $authority1->set($mailField, $value2);
        $authority1->save(false, false);

        $authority2 = new Authority(3);
        $authority2->init();
        static::assertEquals(
            $value2,
            $authority2->get($mailField)
        );
    }

    /**
     * @return void
     * @throws Exception
     * @dataProvider ftpDests
     */
    public function testSaveFtpDest(string $originFtpDest, string $modifiedFtpDest)
    {
        $authority = new Authority(1);
        $authority->init();

        static::assertEquals(
            $originFtpDest,
            $authority->get('helios_ftp_dest')
        );
        $authority->set('helios_ftp_dest', $modifiedFtpDest);
        $authority->save(false, false);

        $authority1 = new Authority(1);
        $authority1->init();

        static::assertEquals(
            $modifiedFtpDest,
            $authority1->get('helios_ftp_dest')
        );
        $authority1->set('helios_ftp_dest', $originFtpDest);
        $authority1->save(false, false);

        $authority2 = new Authority(1);
        $authority2->init();
        static::assertEquals(
            $originFtpDest,
            $authority2->get('helios_ftp_dest')
        );
    }

    /**
     * @return array[]
     */
    public function ftpDests(): array
    {
        return [
            ['helios_ftp_dest','helios_ftp_dest_modif'],
            ['helios_ftp_dest','']
        ];
    }
}
