<?php

class ActesRetriever {

    private $actes_files_upload_root;
    private $openStackSwiftWrapper;

    public function __construct(
    	$actes_files_upload_root,
		OpenStackSwiftWrapper $openStackSwiftWrapper) {
        $this->actes_files_upload_root = $actes_files_upload_root;
        $this->openStackSwiftWrapper = $openStackSwiftWrapper;
    }

    public function getPath($acte_path){
		try {
			$result = $this->openStackSwiftWrapper->retrieveFile(
				ActesEnvelopeStorage::CONTAINER_NAME,
				$this->actes_files_upload_root . "/" . $acte_path,
				$acte_path
			);
		} catch (Exception $e){
			return false;
		}
        return $result;
    }

}