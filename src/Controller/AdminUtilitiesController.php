<?php

namespace S2low\Controller;

use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\UserToEmailAdressConverter;
use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Model\ModuleSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminUtilitiesController extends AbstractController
{
    private Controller $legacyController;
    /**
     * @var \S2low\Services\MailActesNotifications\MailerSymfonyFactory
     */
    private MailerSymfonyFactory $mailerFactory;
    /**
     * @var \S2lowLegacy\Model\ModuleSQL
     */
    private ModuleSQL $moduleSQL;
    /**
     * @var \S2lowLegacy\Class\UserToEmailAdressConverter
     */
    private UserToEmailAdressConverter $userToEmailAdressConverter;

    public function __construct(
        Controller $legacyController,
        ModuleSQL $moduleSQL,
        MailerSymfonyFactory $mailerFactory,
        UserToEmailAdressConverter $userToEmailAdressConverter
    ) {
        $this->legacyController = $legacyController;
        $this->moduleSQL = $moduleSQL;
        $this->mailerFactory = $mailerFactory;
        $this->userToEmailAdressConverter = $userToEmailAdressConverter;
    }

    protected function redirectLegacy(string $url, string $message): RedirectResponse
    {
        $this->legacyController->setErrorMessage($message);
        return parent::redirect($url);
    }

    /**
     * @Route("/admin/utilities/admin_send_global_message.php",name="app_admin_utilities_admin_send_global_message")
     * @throws \Exception
     */
    public function doSendAction(Request $request): RedirectResponse
    {

        $this->legacyController->verifSuperAdmin();

        $module_id = intval($request->request->get('module'));
        $authority_group_id = intval($request->request->get('authority_group_id'));
        $subject = $request->request->get('subject');
        $body = $request->request->get('body');

        if (empty($subject) || empty($body)) {
            return $this->redirectLegacy('/admin/utilities/index.php', "Données manquantes pour l'envoi du message.");
        }

        if (empty($module_id)) {
            return $this->redirectLegacy('/admin/utilities/index.php', 'Pas de module spécifié.');
        }

        $module_info = $this->moduleSQL->getInfo($module_id);
        if (!$module_info) {
            return $this->redirectLegacy('/admin/utilities/index.php', 'Module incorrect spécifié.');
        }

        if (!$recipientUsers = $this->moduleSQL->getUsers($module_id, $authority_group_id)) {
            return $this->redirectLegacy('/admin/utilities/index.php', 'Récupération destinataire impossible.');
        }

        $result = ['recipient_ok' => [], 'recipient_ko' => []];

        foreach ($recipientUsers as $recipientUser) {
            $mailer = $this->mailerFactory->getInstance();
            $mailer->addRecipient(
                $this->userToEmailAdressConverter->getNormalizedEmailAdresse($recipientUser)
            );

            if ($mailer->sendMail($subject, $body)) {
                $result['recipient_ok'][] = $recipientUser['email'];
            } else {
                $result['recipient_ko'][] = $recipientUser['email'];
            }
        }

        $msg = 'Envoi de message aux ' . count($recipientUsers) . " utilisateurs du module {$module_info['name']}.\n Résultat :\n";
        $msg .= 'Envoi OK : ' . implode(', ', $result['recipient_ok']) . "\n";
        $msg .= 'Envoi KO : ' . implode(', ', $result['recipient_ko']) . "\n";

        $status = 1;

        if (!Log::newEntry(LOG_ISSUER_NAME, $msg, $status, false, 'SADM', $module_info['name'], $this->legacyController->getUser())) {
            $msg .= "\nErreur de journalisation.";
        }

        return $this->redirectLegacy('/admin/utilities/index.php', nl2br($msg));
    }
}
