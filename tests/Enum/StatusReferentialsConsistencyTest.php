<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use RuntimeException;
use S2low\Enum\ActesStatus;
use S2low\Enum\HeliosStatus;
use S2lowLegacy\Class\actes\TransactionSQL;
use S2lowTestCase;

class StatusReferentialsConsistencyTest extends S2lowTestCase
{
    private const string REFERENCE_DATA_FILE = __DIR__ . '/../../db/init_data.sql';

    public function testActesStatusMatchesTheReferenceData(): void
    {
        static::assertSame($this->idsSeededInto('actes_status'), $this->sortedValues(ActesStatus::cases()));
    }

    public function testActesStatusMatchesTheLabelsDisplayedByTheTransactionList(): void
    {
        $labelledIds = array_keys($this->getObjectInstancier()->get(TransactionSQL::class)->getStatus());
        sort($labelledIds);

        static::assertSame($labelledIds, $this->sortedValues(ActesStatus::cases()));
    }

    public function testHeliosStatusMatchesTheReferenceData(): void
    {
        static::assertSame($this->idsSeededInto('helios_status'), $this->sortedValues(HeliosStatus::cases()));
    }

    private function idsSeededInto(string $table): array
    {
        $sql = file_get_contents(self::REFERENCE_DATA_FILE);
        if ($sql === false) {
            throw new RuntimeException('Impossible de lire ' . self::REFERENCE_DATA_FILE);
        }
        if (preg_match('/INSERT INTO ' . $table . '\b[^;]*VALUES(.*?);/s', $sql, $insert) !== 1) {
            throw new RuntimeException("Aucune insertion dans $table trouvée dans " . self::REFERENCE_DATA_FILE);
        }
        preg_match_all('/\(\s*(-?\d+)\s*,/', $insert[1], $ids);

        $values = array_map('intval', $ids[1]);
        sort($values);

        return $values;
    }

    private function sortedValues(array $cases): array
    {
        $values = array_map(static fn (ActesStatus|HeliosStatus $case): int => $case->value, $cases);
        sort($values);

        return $values;
    }
}
