<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use Exception;
use PHPUnit\MailsecUtilitiesTestTrait;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Controller\MailsecDownloadController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\UnrecoverableException;

class MailsecDownloadControllerTest extends S2lowTestCase
{
    use MailsecUtilitiesTestTrait;

    /**
     * @throws RedirectException
     * @throws Exception
     */
    public function testDonwload()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->addFile($mail_transaction_id);

        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            "test"
        );
        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);


        $mailsecDownloadController = $this->getObjectInstancier()->get(MailsecDownloadController::class);

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('filename', 'mail.zip');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

        ob_start();
        try {
            $mailsecDownloadController->downloadAction();
        } catch (Exception $e) {
/* Nothing to do */
        }
        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString("test", $contents);
    }

    /**
     * @throws RedirectException
     * @throws UnrecoverableException
     * @throws Exception
     */
    public function testDonwloadWithFilename()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->addFile($mail_transaction_id, 'foo.txt');

        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        copy(
            __DIR__ . "/fixtures/mailsec/mail.zip",
            $this->getArchivePath($mail_files_upload_root)
        );

        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);

        $mailsecDownloadController = $this->getObjectInstancier()->get(MailsecDownloadController::class);

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('filename', 'foo.txt');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

        ob_start();
        try {
            $mailsecDownloadController->downloadAction();
        } catch (Exception $e) {
/* Nothing to do */
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Ceci est un test", $contents);
    }

    /**
     * @throws RedirectException
     * @throws UnrecoverableException
     * @throws Exception
     */
    public function testDonwloadWithFilenameAndAccents()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->addFile($mail_transaction_id, 'fooé.txt');

        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        copy(
            __DIR__ . "/fixtures/mailsec/mail.zip",
            $this->getArchivePath($mail_files_upload_root)
        );

        $this->getObjectInstancier()->set('mail_files_upload_root', $mail_files_upload_root);

        $mailsecDownloadController = $this->getObjectInstancier()->get(MailsecDownloadController::class);

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('filename', 'fooé.txt');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

        ob_start();
        try {
            $mailsecDownloadController->downloadAction();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString(
            "Ceci est un test avec un é", //Quickfix passage utf-8
            $contents
        );
    }

    /**
     * @throws RedirectException
     * @throws UnrecoverableException
     */
    public function testDownloadWhenFileDoesNotExist()
    {
        $this->createMailTransaction();
        $mailsecDownloadController = $this->getObjectInstancier()->get(MailsecDownloadController::class);

        $this->expectException(RedirectException::class);
        $mailsecDownloadController->downloadAction();
    }
}
