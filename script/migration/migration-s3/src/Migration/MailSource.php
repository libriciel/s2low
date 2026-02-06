<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\MailSecRepository;
use App\Service\TransactionSaver;
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

    public function getItems(int $lastProcessedId): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionSaver::LIMIT);
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
}
