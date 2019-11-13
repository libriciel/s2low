<?php

class MailsecDownloadController extends Controller {

	/**
	 * @return bool
	 * @throws RedirectException
	 */
	public function downloadAction(){
		$filename = $this->getRecuperateurGet()->get('filename');
		$fn_download = $this->getRecuperateurGet()->get('root');

		if (! $this->fileExists($fn_download,$filename)){
			$this->redirectToErrorPage();
		}

		$filepath = MAIL_FILES_UPLOAD_ROOT."/".$fn_download.'/'.$filename;

		$finfo = finfo_open(FILEINFO_MIME_TYPE|FILEINFO_MIME_ENCODING);
		$mime_type = finfo_file($finfo, $filepath);
		finfo_close($finfo);

		header_wrapper("Content-Type: $mime_type");
		header_wrapper("Pragma: public");
		header_wrapper("Content-Length: ".filesize($filepath));
		header_wrapper('Content-Disposition: attachment; filename="'.$filename.'"');
		header_wrapper("Content-Description: File Transfert");

		readfile($filepath);
		return true;
	}

	/**
	 * @throws RedirectException
	 */
	private function redirectToErrorPage(){
		$this->redirect(WEBSITE."/modules/mail/?command=show&mail_emis_id=");
	}

	private function fileExists($fn_download, $filename){
		$mailTransactionSQL = $this->getObjectInstancier()->get(MailTransactionSQL::class);

		if ($filename == 'mail.zip' ){
			return $mailTransactionSQL->fnDownloadExists($fn_download);
		}
		return $mailTransactionSQL->fileExists($fn_download,$filename);
	}

}