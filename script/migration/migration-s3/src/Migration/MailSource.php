<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Repository\MailSecRepository;
use Generator;

class MailSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly MailSecRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return 'mail';
    }

    public function getItems(int $lastProcessedId): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, 100);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['siren'] . '/' . $item['fn_download'] . '/mail.zip',
                    type: 'MAIL',
                    siren: $item['siren']
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
