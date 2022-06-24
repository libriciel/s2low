<?php

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

        $actesConvention = $this->getObjectInstancier()->get("ActesConventions");

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
        if ($this->me->isSuper()) {
            $authority_group_id = 0;
        } elseif ($this->me->isGroupAdmin()) {
            $authority_group_id = $this->me->get('authority_group_id');
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

        $csvOutput = $this->getObjectInstancier()->get("CSVOutput");
        $csvOutput->sendAttachment("collectivite.csv", $result);

        $this->controller_exit();
    }
}
