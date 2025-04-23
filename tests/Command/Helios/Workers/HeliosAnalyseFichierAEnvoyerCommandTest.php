<?php

namespace S2low\Tests\Command\Helios\Workers;

use org\bovigo\vfs\vfsStream;
use S2low\Enum\HeliosStatus;
use S2low\Tests\Services\WorkerCommandKernelTestCase;
use S2lowLegacy\Class\BeanstalkdWrapper;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAnalyseFichierAEnvoyerCommandTest extends WorkerCommandKernelTestCase
{
    private HeliosTransactionsSQL $heliosTransactionSQL;

    public function setUp(): void
    {
        self::bootKernel();
        $this->heliosTransactionSQL = static::getContainer()->get(HeliosTransactionsSQL::class);
    }

    protected function dataProvider(): array
    {
        return [
            [
                [
                    'user_id' => 1,
                    'authority_id' => 1,
                    'last_status_id' => HeliosStatus::POSTE->value,
                    'file_size' => '108',
                    'filename' => 'HELIOS_SIMU_ALR2_1739800313_1480737692.xml',
                    'siren' => '100000017',
                    'sha1' => '1d6043253d68c7d4236b660f312b4a38f7c7929a',
                    'submission_date' => date('Y-m-d H:i:s'),
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function tests($data): void
    {
        $heliosTransactionId = $this->heliosTransactionSQL->create(
            $data['filename'],
            $data['sha1'],
            $data['user_id'],
            $data['authority_id'],
            $data['file_size'],
            $data['siren'],
        );
        $this->heliosTransactionSQL->updateStatus($heliosTransactionId, $data['last_status_id'], 'Statut dans les tests.');

        $beanstalkWrapperMock = $this->createBeantstalkdMock('helios-analyse-fichier-a-envoyer', $heliosTransactionId);
        self::getContainer()->set(BeanstalkdWrapper::class, $beanstalkWrapperMock);

        vfsStream::setup('test/helios/');
        $vfsUrl = vfsStream::url('test/helios/' . $data['sha1']);

        $filePath = __DIR__ . '/../../../Fixtures/' . $data['filename'];
        copy($filePath, $vfsUrl);

        $this->commandExecute('worker:helios-analyse-fichier-a-envoyer', self::$kernel);

        $transactionUpdatedByCommand = $this->heliosTransactionSQL->getInfo($heliosTransactionId);

        self::assertEquals(HeliosTransactionsSQL::ATTENTE, $transactionUpdatedByCommand['last_status_id']);
    }
}
