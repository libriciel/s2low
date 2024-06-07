<?php

namespace S2low\Services\Helios;

use RuntimeException;

class Statuses
{
    public function __construct(private array $statuses)
    {
    }

    public function getNameById(int $id): string
    {
        $filteredArray = array_filter($this->statuses, function ($status) use ($id) {
            return $status['id'] === $id;
        });
        if (count($filteredArray) === 0) {
            throw new RuntimeException("Status $id not found");
        }

        if (count($filteredArray) > 1) {
            throw new RuntimeException('Statuts non cohérents');
        }

        return array_values($filteredArray)[0]['name'];
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }
}
