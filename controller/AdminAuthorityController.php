<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\CSVOutput;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySQL;

class AdminAuthorityController extends Controller
{
    /**
     * @throws RedirectException
     */
    public function downloadConventionAction()
    {

        $authority_id = $this->getEnvironnement()->get()->get('authority_id');
        if (! $authority_id) {
            $this->redirectSSL();
        }

        $this->verifAdmin($authority_id);

        $actesConvention = $this->getObjectInstancier()->get(ActesConventions::class);

        $convention_filepath = $actesConvention->getConventionFilepath($authority_id);

        if (! $convention_filepath || ! file_exists($convention_filepath)) {
            $this->redirect(
                "/admin/authorities/admin_authority_edit.php?id=" . $authority_id,
                "Impossible de récupérer la convention"
            );
        }

        Helpers::sendFileToBrowser($convention_filepath, basename($convention_filepath));

        $this->controller_exit();
    }

    /**
     * @throws RedirectException
     */
    public function exportListAction()
    {
        $this->verifAdmin();

        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);
        if ($this->userContext->me->isSuper()) {
            $authority_group_id = 0;
        } elseif ($this->userContext->me->isGroupAdmin()) {
            $authority_group_id = $this->userContext->me->get('authority_group_id');
        } else {
            $this->redirect(
                "/admin/authorities/admin_authorities.php",
                "Vous devez être administrateur de groupe ou super admin"
            );
        }

        $colonne_name = $authoritySQL->getColonneNameForExport();

        $result[] = array_values($colonne_name);

        foreach ($authoritySQL->getAllForExport($authority_group_id) as $line) {
            $result[] = $line;
        }

        $csvOutput = $this->getObjectInstancier()->get(CSVOutput::class);
        $csvOutput->sendAttachment("collectivite.csv", $result);

        $this->controller_exit();
    }
}
