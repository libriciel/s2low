<?php

namespace S2low\Tests;

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Mail\MailPeer;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Mail\MailTransaction;

class MailPeerTest extends \S2lowTestCase
{
    private MailTransaction $mailTransaction;
    public function setUp(): void
    {
        parent::setUp();
        $this->mailTransaction = self::getContainer()->get(MailTransaction::class);
    }

    public function testDeleteMailTransaction()
    {
        $idCreatedMail = $this->createDumbMail();

        MailPeer::DeleteMailTransation($idCreatedMail);

        self::assertTrue($this->mailIsDeleted($idCreatedMail));
    }

    private function createDumbMail(): int
    {
        $creatorUserId = 5;

        $_POST['objet'] = 'objet';
        $_POST['psw1'] = 'psw1';
        $_POST['message'] = 'unique Message 12385616';

        $this->mailTransaction->newSave($creatorUserId);
        return $this->getMailIdFromUniqueMessage($_POST['message']);
    }


    private function mailIsDeleted($idCreatedMail): bool
    {
        $this->mailTransaction->setId($idCreatedMail);
        return $this->mailTransaction->init() === false;
    }

    private function getMailIdFromUniqueMessage($uniqueMessage)
    {
        return self::getContainer()->get(SQLQuery::class)->query("SELECT id from mail_transaction WHERE message = '${uniqueMessage}'")[0]['id'];
    }
}
