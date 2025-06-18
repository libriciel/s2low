<?php

namespace S2lowLegacy\Controller;

use Exception;
use finfo;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\TGZExtractor;
use S2lowLegacy\Class\TmpFolder;

class ActesTransactionsValidateController extends Controller
{
    /**
     * @throws Exception
     */
    public function validateAction()
    {
        $this->verifSuperAdmin();
        $transaction_id = $this->getRecuperateurGet()->getInt('transaction_id');

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $envelope_id = $transaction_info['envelope_id'];

        $actesEnveloppeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $envelope_info = $actesEnveloppeSQL->getInfo($envelope_id);



        $actesRetriever = $this->getObjectInstancier()->get(ActesRetriever::class);
        $archive_path = $actesRetriever->getPath($envelope_info['file_path']);



        $archive = new \Libriciel\LibActes\ArchiveValidator(
            $this->getObjectInstancier()->get('actes_appli_trigramme'),
            $this->getObjectInstancier()->get('actes_appli_quadrigramme')
        );

        $error_xml = [];
        $archive_is_valide = false;
        try {
            $archive->validate($archive_path);
            $archive_is_valide = true;
            $validation_message = "L'archive est valide";
        } catch (\Libriciel\LibActes\Utils\XSDValidationException $e) {
            $error_xml = $e->getValidationErrors();
            $validation_message = "L'archive n'est pas valide : erreur lors de la validation XML";
        } catch (Exception $e) {
            $validation_message = "L'archive n'est pas valide : " . $e->getMessage();
        }


        $tmpDir = new TmpFolder();
        $tmp_dir = $tmpDir->create();

        $tgzExtractor = new TGZExtractor($tmp_dir);

        $tgzExtractor->extract($archive_path, '');

        $padesValid = $this->getObjectInstancier()->get(PadesValid::class);

        $pades_result = [];

        $finfo = new finfo();
        $pades_is_valide = true;
        foreach (scandir($tmp_dir) as $file) {
            $file_path = $tmp_dir . "/" . $file;
            if ($finfo->file($file_path, FILEINFO_MIME_TYPE) != 'application/pdf') {
                continue;
            }


            try {
                $pades_result[$file]['is_signed'] = $padesValid->validate($file_path);
                $pades_result[$file]['message'] = $pades_result[$file]['is_signed'] ? "Signature valide" : "Pas de signature";
                $pades_result[$file]['is_valid'] = true;
            } catch (Exception $e) {
                $pades_result[$file]['is_signed'] = true;
                $pades_result[$file]['is_valid'] = false;
                $pades_result[$file]['message'] = "Erreur lors de la validation PADES : " . $e->getMessage();
                $pades_is_valide = false;
            }
            $pades_result[$file]['last_result'] = $padesValid->getLastResult();
        }

        $this->pades_result = $pades_result;
        $this->pades_is_valide = $pades_is_valide;

        $this->error_xml = $error_xml;
        $this->validation_message = $validation_message;
        $this->archive_is_valide = $archive_is_valide;

        $this->envelope_filename = basename($archive_path);
        $this->title = "Validation d'une archive actes";
        $this->transaction_id = $transaction_id;
    }
}
