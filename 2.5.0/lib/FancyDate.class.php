<?php 

class FancyDate {
	
	
	public function getDateFrancais($date){
		if (! $date){
			return false;
		}
		return utf8_decode(strftime("%e %B %Y", strtotime($date)));
	}

	public function getDateHeureFrancais($date){
		if (! $date){
			return false;
		}
		return utf8_decode(strftime("%e %B %Y %H:%M:%S", strtotime($date)));
	}
	
}