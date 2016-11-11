<?php

class AdminGroupController extends Controller {

	public function doEditAction(){
		$id = $this->getRecuperateurPost()->getInt('id');
		$this->verifSuperAdmin();

		$name = $this->getRecuperateurPost()->get("name");
		$status = $this->getRecuperateurPost()->getInt("status",0);

		/** @var GroupSQL $groupeSQL */
		$groupSQL = $this->getObjectInstancier()->get('GroupSQL');
		$id = $groupSQL->edit($id,$name,$status);

		/** @var AuthorityGroupSirenSQL $authorityGroupSirenSQL */
		$authorityGroupSirenSQL = $this->getObjectInstancier()->get('AuthorityGroupSirenSQL');

		/** @var Siren $theSiren */
		$theSiren  = $this->getObjectInstancier()->get('Siren');

		/** @var FileUploaderNG $fileUploaderNG */
		$fileUploaderNG = $this->getObjectInstancier()->get('FileUploaderNG');

		$file_content = $fileUploaderNG->getFileContent('siren_file');

		foreach (explode("\n",$file_content) as $siren) {
			if ($theSiren->isValid($siren)) {
				$authorityGroupSirenSQL->add($id, $siren);
			}
		}

		$message = "Le groupe « ".get_hecho($name)." » (id=$id) a été éditée";
		$this->setMessage($message);
		$this->log($message);

		$this->redirect("/admin/groups/admin_group_edit.php?id=$id");
	}


}