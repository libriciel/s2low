<?php

namespace S2lowLegacy\Controller;

use S2lowLegacy\Lib\FileUploaderNG;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\Siren;
use S2lowLegacy\Model\AuthorityGroupSirenSQL;
use S2lowLegacy\Model\GroupSQL;

class AdminGroupController extends Controller
{
    /**
     * @throws RedirectException
     */
    public function doEditAction()
    {
        $id = $this->getRecuperateurPost()->getInt('id');
        $this->verifSuperAdmin();

        $name = $this->getRecuperateurPost()->get("name");
        $status = $this->getRecuperateurPost()->getInt("status", 0);

        $name = str_replace('\'', '_', $name);

        $groupSQL = $this->getObjectInstancier()->get(GroupSQL::class);

        if ($groupSQL->groupNameAlreadyExists($id, $name)) {
            $this->setMessage("Le nom de ce groupe est déjà utilisé");
            $this->redirect("/admin/groups/admin_group_edit.php?id=$id");
        }

        $id = $groupSQL->edit($id, $name, $status);

        $authorityGroupSirenSQL = $this->getObjectInstancier()->get(AuthorityGroupSirenSQL::class);

        $theSiren  = $this->getObjectInstancier()->get(Siren::class);

        $fileUploaderNG = $this->getObjectInstancier()->get(FileUploaderNG::class);


        $file_content = $fileUploaderNG->getFileContent('siren_file');
        if ($fileUploaderNG->getFileType('siren_file') != 'text/plain') {
            $this->setMessage("Le fichier SIREN n'a pas été analysé car il n'est pas au bon format");
            $this->redirect("/admin/groups/admin_group_edit.php?id=$id");
        }
        foreach (preg_split('/\n|\r\n?/', $file_content) as $siren) {
            $siren = preg_replace('/\s+/', '', $siren);
            if ($theSiren->isValid($siren)) {
                $authorityGroupSirenSQL->add($id, $siren);
            }
        }

        $message = "Le groupe « " . get_hecho($name) . " » (id=$id) a été édité";
        $this->setMessage($message);
        $this->log($message);

        $this->redirect("/admin/groups/admin_group_edit.php?id=$id");
    }
}
