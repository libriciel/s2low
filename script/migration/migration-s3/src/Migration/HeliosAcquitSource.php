<?php

namespace App\Migration;

use App\DTO\MigrationItem;
use App\Repository\HeliosRepository;
use Generator;

class HeliosAcquitSource implements MigrationSourceInterface
{
    public function __construct(
        private readonly HeliosRepository $repository
    ) {
    }

    public function getIdentifier(): string
    {
        return 'helios_acquit';
    }

    public function getItems(int $lastProcessedId): Generator
    {
        while (true) {
            $batch = $this->repository->getAcquitBatch($lastProcessedId, 100);
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $item) {
                yield new MigrationItem(
                    id: $item['id'],
                    key: $item['siren'] . '/' . $item['acquit_filename'],
                    type: 'PES_ACQUIT',
                    siren: $item['siren']
                );
                $lastProcessedId = $item['id'];
            }
        }
    }
}
