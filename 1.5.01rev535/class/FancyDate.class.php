<?php 

class FancyDate {
	
	
	public function getDateFrancais($date){
		return strftime("%e %B %Y", strtotime($date));
	}
	
	
}