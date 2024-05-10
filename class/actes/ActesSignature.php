<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;
use Exception;
use S2lowLegacy\Lib\SQLQuery;

class ActesSignature
{
    private $actesIncludedFileSQL;
    private $actesTransactionSQL;
    private $actesEnveloppeSQL;

    public function __construct(SQLQuery $sqlQuery, ActesRetriever $actesRetriever)
    {
        $this->actesIncludedFileSQL = new ActesIncludedFileSQL($sqlQuery);
        $this->actesTransactionSQL = new ActesTransactionsSQL($sqlQuery);
        $this->actesEnveloppeSQL = new ActesEnvelopeSQL($sqlQuery);
        $this->actesRetriever = $actesRetriever;
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

        $old_cvd = getcwd();
        chdir($tmp_dir);
        $cmd = "tar cf - * | gzip -9 > $archivePath ";

        system($cmd, $ret);
        chdir($old_cvd);
        $this->actesIncludedFileSQL->setSignature($transaction_id, $actes_included_file_id, $signature);
        $this->actesTransactionSQL->updateStatus(
            $transaction_id,
            ActesStatusSQL::STATUS_POSTE,
            "L'acte a été signé électroniquement"
        );
        return $transaction_id;
    }
}
