<?php

class HeliosSignatureTechnique {

	private $heliosTransactionSQL;
	private $xadesSignature;
	private $enableSignatureTechnique;
	private $pesAllerRetriever;
    private $helios_files_upload_root;

	public function __construct(
	    HeliosTransactionsSQL $heliosTransactionsSQL,
        $helios_files_upload_root,
        XadesSignature $xadesSignature,
        $enableSignatureTechnique = true,
        PesAllerRetriever $pesAllerRetriever
    ){
		$this->heliosTransactionSQL = $heliosTransactionsSQL;
		$this->xadesSignature = $xadesSignature;
		$this->enableSignatureTechnique = $enableSignatureTechnique;
		$this->pesAllerRetriever = $pesAllerRetriever;
		$this->helios_files_upload_root = $helios_files_upload_root;
	}

	public function sign($transaction_id, $p12_certificate_path,$p12_password,XadesSignatureProperties $xadesSignatureProperties){
		$info = $this->heliosTransactionSQL->getInfo($transaction_id);
		if ($info['signature_technique']){
			return true;
		}
		$orig_pes_aller_path = $this->pesAllerRetriever->getPath($info['sha1']);

		if (sha1_file($orig_pes_aller_path) != $info['sha1']){
			throw new UnrecoverableHeliosSignatureTechniqueException("Le fichier a été modifé depuis son postage sur la plateforme");
		}
		$file_signed = sys_get_temp_dir()."/".uniqid("pes_aller_signed");

		if ($this->xadesSignature->isSigned($orig_pes_aller_path)){
		    try{
                $this->xadesSignature->verify($orig_pes_aller_path);
            } catch (Exception $exception){
                throw new UnrecoverableHeliosSignatureTechniqueException(
                    "La signature du fichier est invalide : ".$exception->getMessage());
            }
		}

		if (! $this->enableSignatureTechnique){
			return true;
		}

		try {
			$this->xadesSignature->sign($orig_pes_aller_path, $p12_certificate_path, $p12_password, $file_signed, $xadesSignatureProperties);
		} catch (XadesSignatureHasSignatureException $exception) {
			$this->heliosTransactionSQL->setSignatureTechnique($transaction_id, $info['sha1'], $info['file_size']);
			return true;
		} catch (XadesSignatureNoIDException $e){
			return false;
		} catch (Exception $e) {
			throw new UnrecoverableHeliosSignatureTechniqueException($e->getMessage());
		}

		$new_sha1 = sha1_file($file_signed);
		$new_file = filesize($file_signed);


		if (! copy($file_signed,$this->helios_files_upload_root."/".$new_sha1)){
			throw new RecoverableHeliosSignatureTechniqueException("Impossible de copier avec signature technique sur {$this->helios_files_upload_root}");
		}

		$this->heliosTransactionSQL->setSignatureTechnique($transaction_id,$new_sha1,$new_file);

		unlink($orig_pes_aller_path);
		unlink($file_signed);
		return true;
	}

}

class UnrecoverableHeliosSignatureTechniqueException extends Exception{}

class RecoverableHeliosSignatureTechniqueException extends Exception {}