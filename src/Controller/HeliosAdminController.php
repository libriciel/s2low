<?php

namespace S2low\Controller;

use Exception;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HeliosAdminController extends AbstractController
{
    /**
     * @var \S2lowLegacy\Model\HeliosTransactionsSQL
     */
    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function __construct(
        HeliosTransactionsSQL $heliosTransactionsSQL,
        MailerSymfonyFactory $mailerSymfonyFactory
    ) {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
        $this->mailerSymfonyFactory = $mailerSymfonyFactory;
    }

    /**
     * @Route("/modules/helios/admin/transmis-non-acquitte-by-mail.php",name="app_modules_helios_admin_transmis_non_acquitte_by_mail")
     * @throws Exception
     */
    public function transmisNonAcquitteParMail(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        /** @var Initialisation $init */        //TODO : déplacer vers les services

        $init = LegacyObjectsManager::getLegacyObjectInstancier()
                ->get(Initialisation::class);

        $init->initHelios();

        if (!$init->userIsSuperAdmin()) {
            $_SESSION["error"] = "Super admin only !";
            return parent::redirect(WEBSITE);
        }

        $transactions_list = $this->heliosTransactionsSQL->getNonAcquitte();

        ob_start();
        if (! $transactions_list) {
            $subject =  "Aucune transaction n est reste en transmis";
        } else {
            $subject = count($transactions_list) . " transactions sont reste a l'etat transmis.";
        }

        $output = fopen("php://output", "w");

        foreach ($transactions_list as $line) {
            unset($line['id']);
            unset($line['filename']);
            fputcsv($output, $line);
        }
        fclose($output);

        $content = ob_get_contents();
        ob_end_clean();

        $mail = $this->mailerSymfonyFactory->getInstance();
        $mail->addRecipient($init->getUserInfo()['email']);
        $mail->sendMail($subject, $content);

        $_SESSION['error'] = "Mail envoye a {$init->getUserInfo()['email']}";
        return parent::redirect("transmis-non-acquitte.php");
    }
}
