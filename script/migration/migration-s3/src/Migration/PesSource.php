<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Enum\Type;
use App\Repository\PesRepository;
use App\Service\TransactionImportFromS2low;
use Generator;

class PesSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly PesRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return Type::PES_ALLER->value;
    }

    public function getItems(int $lastProcessedId, ?string $minDate = null, ?string $maxDate = null): Generator
    {
        while (true) {
            $batch = $this->repository->getBatch($lastProcessedId, TransactionImportFromS2low::LIMIT, $minDate, $maxDate);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    oldKey: HELIOS_FILES_UPLOAD_ROOT . '/' . $item['sha1'],
                    newKey: $item['authority_id'] . '/pes_aller/' . $item['id'] . '/' . $item['sha1'],
                    type: $this->getIdentifier(),
                    date: $item['submission_date'],
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
