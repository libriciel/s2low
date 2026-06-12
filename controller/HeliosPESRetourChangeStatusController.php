<?php

namespace S2lowLegacy\Controller;

use DOMDocument;
use Exception;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\ModuleSQL;
use S2lowLegacy\Lib\ObjectInstancier;

class HeliosPESRetourChangeStatusController extends Controller
{
    public function __construct(
        private readonly ModuleSQL $moduleSQL,
        ObjectInstancier $objectInstancier
    ) {
        parent::__construct($objectInstancier);
    }
    public function changeStatusAction()
    {
        $retour_id = $this->getEnvironnement()->get()->get('id');

        try {
            $this->modifStatus($retour_id);
            $msg = "Le status a été modifié";
            $result = "OK";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $result = "KO";
        }

        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $root = $doc->createElement("transaction");
        $doc->appendChild($root);
        $idElement = $doc->createElement("idRetour", $retour_id);
        $resultatElement = $doc->createElement("resultat");
        $messageElement = $doc->createElement("message");

        $root->appendChild($idElement);
        $root->appendChild($resultatElement);
        $root->appendChild($messageElement);

        $resultatElement->appendChild($doc->createTextNode($result));
        $messageElement->appendChild($doc->createTextNode($msg));

        header_wrapper("Content-type: text/xml");
        echo $doc->saveXML();

        $this->controller_exit();
    }

    /**
     * @param $transaction_id
     * @throws Exception
     */
    private function modifStatus($transaction_id)
    {
        $module = $this->moduleSQL->initByName("helios");
        if (!$module) {
            throw new Exception("Erreur d'initialisation du module");
        }
        $me = new User();
        if (! $me->authenticate()) {
            throw new Exception("Échec de l'authentification");
        }

        if (!$module->isActive() || !$me->canAccess($module->get("name")) || $me->isGroupAdminOrSuper()) {
            throw new Exception("Accès refusé");
        }
        $heliosRetourSQL = $this->getObjectInstancier()->get(HeliosRetourSQL::class);
        $info = $heliosRetourSQL->getInfo($transaction_id);

        if (empty($info['authority_id']) || $info['authority_id'] != $me->get('authority_id')) {
            throw new Exception("Accès refusé");
        }

        $heliosRetourSQL->changeStatus(
            $transaction_id,
            HeliosRetourSQL::STATUS_LU
        );
    }
}
