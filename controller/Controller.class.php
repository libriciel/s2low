<?php
class Controller {
	
	protected $me;
	private $viewParameter;
	
	private $objectInstancier;
	
	public function __construct(ObjectInstancier $objectInstancier){
		$this->objectInstancier = $objectInstancier;
		$this->viewParameter = array();
	}
	
	public function __get($key){
		if (isset($this->viewParameter[$key])){
			return $this->viewParameter[$key];
		}
		throw new Exception("parameter $key not found");
	}
	
	public function __set($key,$value){
		$this->setViewParameter($key, $value);
	}
	
	public function isViewParameter($key){
		return isset($this->viewParameter[$key]);
	}
	
	public function setViewParameter($key,$value){
		$this->viewParameter[$key] = $value;
	}
	
	public function getViewParameter(){
		return $this->viewParameter;
	}
	
	public function setErrorMessage($error_message){
		$_SESSION["error"] = $error_message ;
	}
	
	public function setMessage($message){
		//En attendant mieux...
		$_SESSION["error"] = $message ;
	}
	
	
	public function redirectSSL($url_path = "",$url_arg = ""){
		$url = trim(WEBSITE_SSL,"/") ."/". trim($url_path,"/");
		if ($url_arg){
			$url .= "?$url_arg";
		}
		header("Location: $url");
		throw new RedirectException("Redirect to $url");
	}
	
	public function redirect($url,$error_message = ""){
		if ($error_message){
			$this->setErrorMessage($error_message);
		}
		header("Location: $url");
		throw new RedirectException("Redirect to $url with message : $error_message");
	}
	
	public function verifAdmin($authority_id = false){
		$this->me = new User();
		$this->me->authenticate();
		
		if (! $this->me->isAdmin()) {
			$this->redirect(WEBSITE_SSL,"Accès refusé");
		} // @codeCoverageIgnore
		if ($this->me->isSuper()){
			return;
		}
		if ($authority_id) {
			$authoritySQL = new AuthoritySQL($this->getSQLQuery());
			$info = $authoritySQL->getInfo($authority_id);
				
			if ($this->me->isGroupAdmin()){
				if ($info['authority_group_id'] == $this->me->get("authority_group_id")){
					return;
				}
				$this->redirect(WEBSITE_SSL,"Accès refusé");
			} // @codeCoverageIgnore
			
			if ($info['id'] == $this->me->get('authority_id')){
				return ;
			}
			$this->redirect(WEBSITE_SSL,"Accès refusé");
		} // @codeCoverageIgnore
	}
	
	public function verifGroupAdmin($authority_id){
		$this->verifAdmin();
		if ($this->me->isSuper()){
			return;
		}
		
		if ($this->me->isGroupAdmin()){
			$authoritySQL = new AuthoritySQL($this->getSQLQuery());
			$info = $authoritySQL->getInfo($authority_id);
			if ($info['authority_group_id'] == $this->me->get("authority_group_id")){
				return;
			}
		} 
		
		$this->redirect(WEBSITE_SSL,"Accès refusé");
	} // @codeCoverageIgnore
	
	
	public function verifSuperAdmin(){
		$this->verifAdmin();
		if (! $this->me->isSuper()){
			$this->redirect(WEBSITE_SSL,"Accès refusé");
		} // @codeCoverageIgnore
	}
	
	
	public function renderDefault(){
		$doc = new HTMLLayout();
		$doc->setTitle($this->title);
		
		$doc->openContainer();
		$doc->openSideBar();
		$doc->buildMenu($this->me);

		$doc->addBody($this->side_bar);

		$doc->closeSideBar();
		$doc->openContent();

		ob_start();
		$this->render($this->template_milieu);
		$html = ob_get_contents();
		ob_end_clean();
				
		$doc->addBody($html);
		$doc->closeContent();
		$doc->closeContainer();
		
		$doc->buildFooter();
		
		$doc->display();
	}
	
	public function render($template){
		foreach($this->viewParameter as $key => $value){
			$$key = $value;
		}
		include($template);
	}
	
	public function _actionBefore($controller,$action){
		$this->title = "S2low";
		$this->template_milieu = __DIR__."/../template/".ucfirst($controller).ucfirst($action).".php";
		$this->side_bar = false;
	}
	
	public function _actionAfter(){
		$this->renderDefault();
	}
	
	public function getRecuperateurGet(){
		return new Recuperateur($_GET);
	}
	
	public function getRecuperateurPost(){
		return new Recuperateur($_POST);
	}
	
	/**
	 * @return SQLQuery
	 */
	public function getSQLQuery(){
		return $this->objectInstancier->SQLQuery;
	}
	
	public function getObjectInstancier(){
		return $this->objectInstancier;
	}
	
}