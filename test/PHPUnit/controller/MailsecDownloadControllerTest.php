<?php

use S2low\Services\CloudFileStorage;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorable;
use S2lowLegacy\Class\mailsec\MailTransactionSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Controller\MailsecDownloadController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\UnrecoverableException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MailsecDownloadControllerTest extends S2lowTestCase
{
    use MailsecUtilitiesTestTrait;

    /**
     * @throws RedirectException
     * @throws UnrecoverableException
     * @throws Exception
     */
    public function testDonwload()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->addFile($mail_transaction_id);

        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        $mail_files_without_transac_dir = $tmpFolder->create();
        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        file_put_contents(
            $this->getArchivePath($mail_files_upload_root),
            "test"
        );

        $mailsecDownloadController = $this->getMailSecController($mail_files_upload_root . "/");
        $mailIncludedFilesCloudStorable = new MailIncludedFilesCloudStorable(
            self::getContainer()->get(MailTransactionSQL::class),
            $mail_files_upload_root,
            $mail_files_without_transac_dir
        );
        self::getContainer()->set(MailIncludedFilesCloudStorable::class, $mailIncludedFilesCloudStorable);

        self::getContainer()->get(Environnement::class)->get()->set('filename', 'mail.zip');
        self::getContainer()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

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
        $mail_files_without_transac_dir = $tmpFolder->create();

        $mailsecDownloadController = $this->getMailSecController($mail_files_upload_root . "/");
        $mailIncludedFilesCloudStorable = new MailIncludedFilesCloudStorable(
            self::getContainer()->get(MailTransactionSQL::class),
            $mail_files_upload_root,
            $mail_files_without_transac_dir
        );
        self::getContainer()->set(MailIncludedFilesCloudStorable::class, $mailIncludedFilesCloudStorable);

        self::getContainer()->get(Environnement::class)->get()->set('filename', 'foo.txt');
        self::getContainer()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

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
        $mail_files_without_transac_dir = $tmpFolder->create();

        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        copy(
            __DIR__ . "/fixtures/mailsec/mail.zip",
            $this->getArchivePath($mail_files_upload_root)
        );

        $mailsecDownloadController = $this->getMailSecController($mail_files_upload_root . "/");
        $mailIncludedFilesCloudStorable = new MailIncludedFilesCloudStorable(
            self::getContainer()->get(MailTransactionSQL::class),
            $mail_files_upload_root,
            $mail_files_without_transac_dir
        );
        self::getContainer()->set(MailIncludedFilesCloudStorable::class, $mailIncludedFilesCloudStorable);

        self::getContainer()->get(Environnement::class)->get()->set('filename', 'fooé.txt');
        self::getContainer()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

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
     * @throws Exception
     */
    public function testDownloadWithLatin1Filename()
    {
        $mail_transaction_id = $this->createMailTransaction();
        $this->addFile($mail_transaction_id, 'fooé.txt');

        $tmpFolder = new TmpFolder();
        $mail_files_upload_root = $tmpFolder->create();
        $mail_files_without_transac_dir = $tmpFolder->create();

        mkdir($mail_files_upload_root . "/" . $this->fn_download_payload);
        copy(
            __DIR__ . "/fixtures/mailsec/mail.zip",
            $this->getArchivePath($mail_files_upload_root)
        );

        $mailsecDownloadController = $this->getMailSecController($mail_files_upload_root . "/");
        $mailIncludedFilesCloudStorable = new MailIncludedFilesCloudStorable(
            self::getContainer()->get(MailTransactionSQL::class),
            $mail_files_upload_root,
            $mail_files_without_transac_dir
        );
        self::getContainer()->set(MailIncludedFilesCloudStorable::class, $mailIncludedFilesCloudStorable);

        // Simulate Latin-1 input
        $filenameLatin1 = mb_convert_encoding('fooé.txt', 'ISO-8859-1', 'UTF-8');
        self::getContainer()->get(Environnement::class)->get()->set('filename', $filenameLatin1);
        self::getContainer()->get(Environnement::class)->get()->set('root', $this->fn_download_payload);

        ob_start();
        try {
            $mailsecDownloadController->downloadAction();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString(
            "Ceci est un test avec un é",
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
        $mailsecDownloadController = self::getContainer()->get(MailsecDownloadController::class);

        $this->expectException(RedirectException::class);
        $mailsecDownloadController->downloadAction();
    }

    private function getMailSecController($prefix)
    {
        $localMailResolver = new LocalFileResolver(
            self::getContainer()->get(MailTransactionSQL::class),
            $prefix
        );

        $cloudStoreMailSec = new CloudFileStorage(
            self::getContainer()->get('app.clientCloudStorage.acte_enveloppe'),
            $localMailResolver,
            self::getContainer()->get(MailTransactionSQL::class),
            self::getContainer()->get(Symfony\Component\Filesystem\Filesystem::class),
        );

        return new MailsecDownloadController(
            self::getContainer()->get(ObjectInstancier::class),
            $localMailResolver,
            $cloudStoreMailSec,
            self::getContainer()->get(UserContext::class)
        );
    }
}
