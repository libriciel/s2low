<?php

namespace S2low\Controller;

use Exception;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Lib\HeliosNamesGenerator;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class HeliosAdminController extends AbstractController
{
    public function __construct(
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL,
        private readonly MailerSymfonyFactory $mailerSymfonyFactory,
        private readonly string $helios_ftp_p_appli,
        private readonly Initialisation $initialisation,
        private readonly HeliosNamesGenerator $namesGenerator,
        private readonly UserContext $userContext
    ) {
    }

    /**
     * @throws Exception
     */
    #[Route(
        path: '/modules/helios/admin/transmis-non-acquitte-by-mail.php',
        name: 'app_modules_helios_admin_transmis_non_acquitte_by_mail',
    )]
    public function transmisNonAcquitteParMail(): RedirectResponse
    {
        $this->initialisation->initModule($this->userContext, Initialisation::MODULENAMEHELIOS);

        if ($this->userContext->userInfo['role'] != 'SADM') {
            $_SESSION['error'] = 'Super admin only !';
            return parent::redirect(WEBSITE);
        }

        $transactions_list_Gateway = $this->heliosTransactionsSQL->getNonAcquitteWithPasstransStatus(
            false,
            date('Y-m-d')
        );
        $transactions_list_Passtrans = $this->heliosTransactionsSQL->getNonAcquitteWithPasstransStatus(
            true,
            date('Y-m-d')
        );


        if ((count($transactions_list_Gateway) + count($transactions_list_Passtrans)) === 0) {
            $subject = 'Aucune transaction n est reste en transmis';
        } else {
            $transactionsTransmises = count($transactions_list_Gateway) + count($transactions_list_Passtrans);
            $subject = $transactionsTransmises . " transactions sont restees a l'etat transmis.";
        }

        ob_start();
        $output = fopen('php://output', 'w');

        echo "Gateway \n";
        foreach ($transactions_list_Gateway as $line) {
            unset($line['id']);
            unset($line['filename']);
            unset($line['sha1']);
            fputcsv($output, $line);
        }

        echo "Passtrans \n";
        foreach ($transactions_list_Passtrans as $line) {
            unset($line['id']);
            unset($line['filename']);
            $p_dest = $line['helios_ftp_dest'];
            $pAppli = $this->helios_ftp_p_appli;

            $p_msg = $this->namesGenerator->getP_MSGFromParameters(
                $line['xml_cod_col'],
                $line['xml_id_post'],
                $line['xml_cod_bud']
            );

            $hash = $line['sha1'];

            $line['passtransFileName'] = "$p_dest%%$pAppli%%$p_msg%%$hash";
            fputcsv($output, $line);
        }

        fclose($output);

        $content = ob_get_contents();
        ob_end_clean();

        $mail = $this->mailerSymfonyFactory->getInstance();
        $mail->addRecipient($this->userContext->userInfo['email']);
        $mail->sendMail($subject, $content);

        $_SESSION['error'] = "Mail envoye a {$this->userContext->userInfo['email']}";
        return parent::redirect('transmis-non-acquitte.php');
    }
}
