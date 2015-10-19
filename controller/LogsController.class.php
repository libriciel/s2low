<?php

class LogsController extends Controller {

	public function viewAction(){
		$this->verifUser();
		$this->title = "Tedetis : Journal d'évènements";

		$h1_title = "Journal d'évènements";
		if ($this->me->isAuthorityAdmin()) {
			$authoritySQL = new AuthoritySQL($this->getSQLQuery());
			$authority_info = $authoritySQL->getInfo($this->me->get('authority_id'));
			$h1_title .= " de la collectivité «&nbsp;{$authority_info['name']}&nbsp;»";
		} elseif ($this->me->isGroupAdmin()) {
			$groupSQL = new GroupSQL($this->getSQLQuery());
			$groupe_info = $groupSQL->getInfo($this->me->get("authority_group_id"));
			$h1_title .= " du groupe «&nbsp;{$groupe_info['name']}&nbsp;»";
		}
		$this->h1_title = $h1_title;



	}


}