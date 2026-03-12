<?php

namespace S2lowLegacy\Controller;

use Exception;
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\mailsec\MailIncludedFilesCloudStorage;
use S2lowLegacy\Class\mailsec\MailTransactionSQL;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\UnrecoverableException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ZipArchive;

class MailsecDownloadController extends Controller
{
    public const DEFAULT_ARCHIVE_NAME = 'mail.zip';

    public function __construct(
        protected readonly ObjectInstancier $objectInstancier,
        #[Autowire(service: 'app.localFileResolver.mailsec')]
        private readonly LocalFileResolver $localMailSecResolver,
        #[Autowire(service: 'app.store.file.mailsec')]
        private readonly CloudFileStorageInterface $cloudStoreMailSec,
        UserContext $userContext
    ) {
        parent::__construct($objectInstancier, $userContext);
    }


    /**
     * @return bool
     * @throws RedirectException
     * @throws UnrecoverableException
     * @throws Exception
     */
    public function downloadAction()
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = false;

        $filename = $this->getRecuperateurGet()->get('filename');
        $fn_download = $this->getRecuperateurGet()->get('root');

        if (!mb_check_encoding($filename, 'UTF-8')) {
            $filename = mb_convert_encoding($filename, 'UTF-8', 'ISO-8859-1');
        }
        if (!mb_check_encoding($fn_download, 'UTF-8')) {
            $fn_download = mb_convert_encoding($fn_download, 'UTF-8', 'ISO-8859-1');
        }

        $mailTransactionSQL = $this->getObjectInstancier()->get(MailTransactionSQL::class);
        $mail_id = $mailTransactionSQL->getIdFromFnDownload($fn_download);

        try {
            $filepath = $this->localMailSecResolver->getFullPath($mail_id);
            $this->cloudStoreMailSec->downloadFileFromCloud($mail_id);
        } catch (Exception $exception) {
            $this->redirectToErrorPage();
        }

        if ($filename != self::DEFAULT_ARCHIVE_NAME) {
            $tmp_folder = $tmpFolder->create();
            $zipArchive = new ZipArchive();
            $zipArchive->open($filepath);
            $filenameInZip = iconv(
                'IBM437',
                'UTF-8',
                mb_convert_encoding($filename, "ISO-8859-9", "UTF-8")
            );     // HACK Fix passage en utf-8!!

            $filepath = $tmp_folder . "/" . $filename;
            $stream = $zipArchive->getStream($filenameInZip);   // Certains fichiers ont été compressés avant
                                                                // le passage en UTF-8.
            if (!$stream) {                                       // D'autres non.
                $stream = $zipArchive->getStream($filename);
            }

            if (!$stream) {
                throw new Exception("Impossible de télécharger ce fichier. Essayez de télécharger l'archive.");
            }
            $contents = stream_get_contents($stream);
            file_put_contents(
                $filepath,
                $contents
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        header_wrapper("Content-Type: $mime_type");
        header_wrapper("Pragma: public");
        header_wrapper("Content-Length: " . filesize($filepath));
        header_wrapper("Content-Disposition: attachment; filename=\"$filename\"");
        header_wrapper("Content-Description: File Transfert");
        header_wrapper("X-Robots-Tag: noindex");

        readfile($filepath);

        if ($filename != self::DEFAULT_ARCHIVE_NAME && $tmp_folder) {
            $tmpFolder->delete($tmp_folder);
        }
        exit_wrapper();
        return true;
    }

    /**
     * @throws RedirectException
     */
    private function redirectToErrorPage()
    {
        $this->redirect(WEBSITE . "/modules/mail/?command=show&mail_emis_id=");
    }

    private function fileExists($fn_download, $filename)
    {
        $mailTransactionSQL = $this->getObjectInstancier()->get(MailTransactionSQL::class);

        if ($filename == self::DEFAULT_ARCHIVE_NAME) {
            return $mailTransactionSQL->fnDownloadExists($fn_download);
        }
        return $mailTransactionSQL->fileExists($fn_download, $filename);
    }
}
