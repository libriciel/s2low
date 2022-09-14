<?php

namespace S2low\Controller;

use Exception;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HeliosAdminController extends AbstractController
{
    /**
     * @var \S2lowLegacy\Model\HeliosTransactionsSQL
     */
    private HeliosTransactionsSQL $heliosTransactionsSQL;

    public function __construct(HeliosTransactionsSQL $heliosTransactionsSQL)
    {
        $this->heliosTransactionsSQL = $heliosTransactionsSQL;
    }

    /**
     * @Route("/modules/helios/admin/transmis-non-acquitte-by-mail.php",name="app_modules_helios_admin_transmis_non_acquitte_by_mail")
     * @throws Exception
     */
    public function transmisNonAcquitteParMail()
    {
        require_once(__DIR__ . '/../../init/init-www-helios.php');

        if ($userInfo['role'] != 'SADM') {
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

        mail($userInfo['email'], $subject, $content);

        $_SESSION['error'] = "Mail envoye a {$userInfo['email']}";
        return parent::redirect("transmis-non-acquitte.php");
    }
}
