<?php
class MockController extends Controller {
	
	public function mockAction(){
		$this->template_milieu = __DIR__."/MockMockTemplate.php";
	}
	
	public function _actionAfter(){
		$this->render($this->template_milieu);
	}
	
}