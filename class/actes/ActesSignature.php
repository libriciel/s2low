<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;
use Exception;
use Symfony\Component\Process\Process;

class ActesSignature
{
    public function __construct(
        private readonly ActesIncludedFileSQL $actesIncludedFileSQL,
        private readonly ActesTransactionsSQL $actesTransactionSQL,
        private readonly ActesEnvelopeSQL $actesEnveloppeSQL,
        private readonly ActesRetriever $actesRetriever,
    ) {
    }

    /**
     * @param $actes_included_file_id
     * @param $signature
     * @return array|bool|mixed
     * @throws Exception
     */
    public function setSignature($actes_included_file_id, $signature)
    {

        $tmpFolder = new TmpFolder();
        $tmp_dir = $tmpFolder->create();
        try {
            $result = $this->setSignatureThrowException($actes_included_file_id, $signature, $tmp_dir);
        } catch (Exception $e) {
            $tmpFolder->delete($tmp_dir);
            throw $e;
        }
        $tmpFolder->delete($tmp_dir);
        return $result;
    }

    /**
     * @param $actes_included_file_id
     * @param $signature
     * @param $tmp_dir
     * @return array|bool|mixed
     * @throws Exception
     */
    public function setSignatureThrowException($actes_included_file_id, $signature, $tmp_dir)
    {
        $transaction_id = $this->actesIncludedFileSQL->getTransactionId($actes_included_file_id);
        if (! $transaction_id) {
            throw new Exception("Impossible de trouver une transaction ratachée au fichier à signé");
        }

        $transactionInfo = $this->actesTransactionSQL->getInfo($transaction_id);
        if (! $transactionInfo) {
            throw new Exception("Impossible de trouver la transaction $transaction_id");
        }
        if ($transactionInfo['last_status_id'] != 18) {
            throw new Exception("La transaction $transaction_id n'est pas dans l'état « En attente d'être signée. »");
        }


        $envelope_id = $transactionInfo['envelope_id'];


        $actes_envelope_info = $this->actesEnveloppeSQL->getInfo($envelope_id);
        $archivePath = $this->actesRetriever->getPath($actes_envelope_info['file_path']);

        //$archivePath = $this->actes_files_upload_root.'/'.$actes_envelope_info['file_path'];

        $tgzExtractor = new TGZExtractor($tmp_dir);
        $tgzExtractor->extract($archivePath, false);

        $xml_file = $this->actesIncludedFileSQL->getXMLFilename($transaction_id);

        $xml = simplexml_load_file($tmp_dir . "/" . $xml_file);
        $namespaces = $xml->getDocNamespaces();

        $children = $xml->children($namespaces['actes']);
        $children->{'Document'}->addChild("Signature", $signature, $namespaces['actes']);

        $xml->asXML($tmp_dir . "/" . $xml_file);

        // The file names come from the depositor's archive, so they must never reach a
        // shell: a file named "--checkpoint-action=exec=..." would be read by tar as an
        // option and run a command. We list the files in PHP and run tar directly, with
        // "--" so each name stays a file name.
        $old_cvd = getcwd();
        chdir($tmp_dir);
        $files = glob('*') ?: [];
        $process = new Process(['tar', 'czf', $archivePath, '--', ...$files]);
        $process->run();
        chdir($old_cvd);
        if (! $process->isSuccessful()) {
            throw new Exception("Erreur lors de la création de l'archive signée : " . $process->getErrorOutput());
        }
        $this->actesIncludedFileSQL->setSignature($transaction_id, $actes_included_file_id, $signature);
        $this->actesTransactionSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_POSTE,
            "L'acte a été signé électroniquement"
        );
        return $transaction_id;
    }
}
