<?php

class AdminUtilitiesController extends Controller {

	public function indexAction(){
		$this->verifSuperAdmin();
		$this->{'module_list'} = Module::getActiveModulesIdName();
		$this->{'group_list'} = $this->getObjectInstancier()->get(GroupSQL::class)->getAll();
	}

	/**
	 * @throws RedirectException
	 */
	public function doSendAction(){
		$this->verifSuperAdmin();

		$module_id = $this->getEnvironnement()->post()->getInt("module");
		$authority_group_id = $this->getEnvironnement()->post()->getInt("authority_group_id");
		$subject = $this->getEnvironnement()->post()->get("subject");
		$body = $this->getEnvironnement()->post()->get("body");


		if (empty($subject) || empty($body)) {
			$this->redirect("/admin/utilities/","Données manquantes pour l'envoi du message.");
		}

		if (empty($module_id)) {
			$this->redirect("/admin/utilities/","Pas de module spécifié.");
		}

		$moduleSQL = $this->getObjectInstancier()->get(ModuleSQL::class);
		$module_info =$moduleSQL->getInfo($module_id);
		if (! $module_info){
			$this->redirect("/admin/utilities/","Module incorrect spécifié.");
		}

		if (! $recipients = $moduleSQL->getUsers($module_id,$authority_group_id)) {
			$this->redirect("/admin/utilities/", "Récupération destinataire impossible.");
		}

		$result = ['recipient_ok'=>[],'recipient_ko'=>[]];

		foreach($recipients as $recipient){
			$mailer = $this->getObjectInstancier()->get(MailerFactory::class)->getInstance();
			$mailer->addComplexRecipient($recipient);

			if ($mailer->sendMail($subject, $body)) {
				$result['recipient_ok'][] = $recipient['email'];
			} else {
				$result['recipient_ko'][] = $recipient['email'];
			}
		}

		$msg = "Envoi de message aux " . count($recipients) . " utilisateurs du module {$module_info['name']}.\n Résultat :\n" ;
		$msg .= "Envoi OK : ".implode(", ",$result['recipient_ok'])."\n";
		$msg .= "Envoi KO : ".implode(", ",$result['recipient_ko'])."\n";

		$status = 1;

		if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $status, false, 'SADM', $module_info['name'], $this->me)) {
			$msg .= "\nErreur de journalisation.";
		}

		$this->redirect("/admin/utilities/", nl2br($msg));
	}

}