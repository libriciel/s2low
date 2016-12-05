<?php

class LibersignController extends Controller {


	public function testAction(){
		$this->verifSuperAdmin();
		$this->libersignController = $this;
	}

	public function displayLibersignJS(){
		$libersign_applet_url  = LIBERSIGN_URL;
		$libersign_help_url  = LIBERSIGN_HELP_URL;
		$libersign_extension_update_url  = LIBERSIGN_EXTENSION_UPDATE_URL;
		include(__DIR__."/../template/LibersignJS.php");

	}




}