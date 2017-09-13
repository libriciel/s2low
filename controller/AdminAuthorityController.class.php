<?php

class AdminAuthorityController extends Controller {

    public function downloadConventionAction(){

        $authority_id = $this->getEnvironnement()->get()->get('authority_id');
        if (! $authority_id){
            $this->redirectSSL();
        }

        $this->verifAdmin($authority_id);

        $actesConvention = $this->getObjectInstancier()->get("ActesConventions");

        $convention_filepath = $actesConvention->getConventionFilepath($authority_id);

        if ( ! $convention_filepath || ! file_exists($convention_filepath)){
            $this->redirect(
                "/admin/authorities/admin_authority_edit.php?id=" . $authority_id,
                "Impossible de récupérer la convention"
            );
        }

        Helpers::sendFileToBrowser($convention_filepath,basename($convention_filepath));

        $this->controller_exit();
    }

}