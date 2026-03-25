<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\MailSecRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class MailSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly MailSecRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::MAIL->value;
    }

    public function getItems(int $lastProcessedId, ?string $minDate = null): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionImportFromS2low::LIMIT, $minDate);
            if (empty($batch)) {
                break;
            }
            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['siren'] . '/' . $item['fn_download'] . '/mail.zip',
                    type: $this->getIdentifier(),
                    date: $item['date_envoi'],
                    siren: $item['siren']
                );
                $lastProcessedId = $item['id'];
            }
        }
    }

    public function getHandledTransactions(): Generator
    {
        // TODO: Implement getHandledTransactions() method.
    }
}
