<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\PesAcquitRepository;
use App\Repository\TransactionSaver;
use Generator;

class PesAcquitSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly PesAcquitRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::PES_ACQUIT->value;
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
                    key: $item['siren'] . '/' . $item['acquit_filename'],
                    type: $this->getIdentifier(),
                    date: $item['submission_date'],
                    siren: $item['siren']
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
