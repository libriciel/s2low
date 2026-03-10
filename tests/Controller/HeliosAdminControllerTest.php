<?php

declare(strict_types=1);

namespace S2low\Tests\Controller;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Controller\HeliosAdminController;
use S2low\Services\MailActesNotifications\MailerSymfony;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\Connexion;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\HeliosNamesGenerator;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAdminControllerTest extends TestCase
{
    private MockObject|HeliosTransactionsSQL $heliosTransactionsSQLMock;
    private HeliosAdminController $heliosAdminController;
    private MockObject|MailerSymfony $mailerSymfony;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->heliosTransactionsSQLMock = $this->getMockBuilder(HeliosTransactionsSQL::class)->disableOriginalConstructor()->getMock();

        $mailerSymfonyFactoryMock = $this->getMockBuilder(MailerSymfonyFactory::class)->disableOriginalConstructor()->getMock();
        $this->mailerSymfony = $this->getMockBuilder(MailerSymfony::class)->disableOriginalConstructor()->getMock();
        $mailerSymfonyFactoryMock->method('getInstance')->willReturn($this->mailerSymfony);

        $userContext = new UserContext(
            $this->getMockBuilder(Connexion::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(User::class)->disableOriginalConstructor()->getMock(),
            ['role' => 'SADM','email' => 'em@a.il'],
            [],
            []
        );

        $initialisationMock = $this->getMockBuilder(Initialisation::class)->disableOriginalConstructor()->getMock();

        $this->heliosAdminController = new HeliosAdminController(
            $this->heliosTransactionsSQLMock,
            $mailerSymfonyFactoryMock,
            'pAppli',
            $initialisationMock,
            new HeliosNamesGenerator(),
            $userContext
        );
    }

    /**
     * @throws Exception
     */
    public function testAucuneTransaction(): void
    {
        $this->heliosTransactionsSQLMock->method('getNonAcquitteWithPasstransStatus')
            ->willReturnOnConsecutiveCalls([], []);

        $this->mailerSymfony->expects(static::once())->method('sendMail')
            ->with('Aucune transaction n est reste en transmis');

        $this->heliosAdminController->transmisNonAcquitteParMail();
    }

    /**
     * @throws Exception
     */
    public function testTransactionGateway(): void
    {
        $this->heliosTransactionsSQLMock->method('getNonAcquitteWithPasstransStatus')
            ->willReturnOnConsecutiveCalls([
                [
                    'id' => 1,
                    'filename' => 'filename',
                    'xml_nomfic' => 'xml_nomfic',
                    'submission_date' => '01-01-2001',
                    'helios_ftp_dest' => 'helios_ftp_dest',
                    'xml_id_post' => 'xml_id_post',
                    'xml_cod_bud' => 'xml_cod_bud',
                    'xml_cod_col' => 'xml_cod_col',
                    'sha1' => 'sha1'
                ]
            ], []);

        $this->mailerSymfony->expects(static::once())->method('sendMail')
            ->with(
                '1 transactions sont restees a l\'etat transmis.',
                'Gateway 
xml_nomfic,01-01-2001,helios_ftp_dest,xml_id_post,xml_cod_bud,xml_cod_col
Passtrans 
'
            );
        $this->heliosAdminController->transmisNonAcquitteParMail();
    }

    /**
     * @throws Exception
     */
    public function testTransactionPasstrans(): void
    {
        $this->heliosTransactionsSQLMock->method('getNonAcquitteWithPasstransStatus')
            ->willReturnOnConsecutiveCalls([], [[
                'id' => 1,
                'filename' => 'filename',
                'xml_nomfic' => 'xml_nomfic',
                'submission_date' => '01-01-2001',
                'helios_ftp_dest' => 'helios_ftp_dest',
                'xml_id_post' => 'xml_id_post',
                'xml_cod_bud' => 'xml_cod_bud',
                'xml_cod_col' => 'xml_cod_col',
                'sha1' => 'sha1'
            ]]);

        $this->mailerSymfony->expects(static::once())->method('sendMail')
            ->with(
                '1 transactions sont restees a l\'etat transmis.',
                'Gateway 
Passtrans 
xml_nomfic,01-01-2001,helios_ftp_dest,xml_id_post,xml_cod_bud,xml_cod_col,sha1,helios_ftp_dest%%pAppli%%PES#xml_cod_col#xml_id_post#xml_cod_bud%%sha1
'
            );
        $this->heliosAdminController->transmisNonAcquitteParMail();
    }
}
