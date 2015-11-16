<?php

class HeliosSignatureTechnique {

	private $heliosTransactionSQL;
	private $helios_files_upload_root;
	private $xadesSignature;

	public function __construct(HeliosTransactionsSQL $heliosTransactionsSQL, $helios_files_upload_root, XadesSignature $xadesSignature){
		$this->heliosTransactionSQL = $heliosTransactionsSQL;
		$this->helios_files_upload_root = $helios_files_upload_root;
		$this->xadesSignature = $xadesSignature;
	}

	public function sign($transaction_id, $p12_certificate_path,$p12_password,XadesSignatureProperties $xadesSignatureProperties){
		$info = $this->heliosTransactionSQL->getInfo($transaction_id);
		if ($info['signature_technique']){
			return ;
		}
		$orig_pes_aller_path = $this->helios_files_upload_root."/".$info['sha1'];
		if (sha1_file($orig_pes_aller_path) != $info['sha1']){
			throw new UnrecoverableHeliosSignatureTechniqueException("Le fichier a été modifé depuis son postage sur la plateforme");
		}
		$file_signed = sys_get_temp_dir()."/".uniqid("pes_aller_signed");
		try {
			$this->xadesSignature->sign($orig_pes_aller_path, $p12_certificate_path, $p12_password, $file_signed, $xadesSignatureProperties);
		} catch (XadesSignatureHasSignatureException $exception){
			if (!$this->xadesSignature->verify($orig_pes_aller_path)) {
				throw new UnrecoverableHeliosSignatureTechniqueException("Le fichier est déjà signé, mais la signature est invalide");
			}
			$this->heliosTransactionSQL->setSignatureTechnique($transaction_id,$info['sha1'],$info['file_size']);
			return;
		}

		$new_sha1 = sha1_file($file_signed);
		$new_file = filesize($file_signed);

		if (! copy($file_signed,$this->helios_files_upload_root."/".$new_sha1)){
			throw new RecoverableHeliosSignatureTechniqueException("Impossible de copier avec signature technique sur {$this->helios_files_upload_root}");
		}

		$this->heliosTransactionSQL->setSignatureTechnique($transaction_id,$new_sha1,$new_file);

		unlink($orig_pes_aller_path);
		unlink($file_signed);
	}

}

class UnrecoverableHeliosSignatureTechniqueException extends Exception{}

class RecoverableHeliosSignatureTechniqueException extends Exception {}