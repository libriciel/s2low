<?php

class AdminUserController extends Controller {

	public function doEditAction(){
		return include(__DIR__."/../public.ssl/admin/users/admin_user_edit_handler.php");
	}


}