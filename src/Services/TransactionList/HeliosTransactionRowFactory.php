<?php

declare(strict_types=1);

namespace S2low\Services\TransactionList;

use S2low\DTO\TransactionList\HeliosTransactionRow;
use S2low\DTO\TransactionList\StatusBadge;
use S2low\Enum\HeliosStatus;
use S2low\Helpers\RequeteHelper;

final class HeliosTransactionRowFactory
{
    private const string SHOW_PATH = '/modules/helios/helios_transac_show.php?id=';

    public function __construct(private readonly RequeteHelper $requeteHelper)
    {
    }

    /**
     * @param array<int, string> $statusLabels
     */
    public function fromListLine(array $line, array $statusLabels): HeliosTransactionRow
    {
        $id = (int) $line['id'];
        $statusId = (int) $line['last_status_id'];

        return new HeliosTransactionRow(
            id: $id,
            showUrl: $this->requeteHelper->getLink(self::SHOW_PATH . $id),
            filename: (string) ($line['filename'] ?? ''),
            nomFic: (string) ($line['xml_nomfic'] ?? ''),
            authorityName: (string) ($line['authority_name'] ?? ''),
            submittedAt: ListLineReader::submissionDate($line, $id),
            status: StatusBadge::fromHelios($statusId, $statusLabels[$statusId] ?? '', (string) ($line['message'] ?? '')),
            followerName: ListLineReader::fullName($line),
            isSelectable: HeliosStatus::tryFrom($statusId)?->allowsSaeArchiving() ?? false,
        );
    }
}
