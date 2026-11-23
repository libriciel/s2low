<?php

declare(strict_types=1);

namespace S2low\Services\TransactionList;

use S2low\DTO\TransactionList\ActesFollowUp;
use S2low\DTO\TransactionList\ActesTransactionRow;
use S2low\DTO\TransactionList\StatusBadge;
use S2low\Enum\ActesStatus;
use S2low\Helpers\RequeteHelper;
use S2lowLegacy\Class\actes\TypeTransaction;

final class ActesTransactionRowFactory
{
    private const string SHOW_PATH = '/modules/actes/actes_transac_show.php?id=';
    private const string DEFAULT_FOLLOW_UP_DIRECTION = 'envoyé';

    public function __construct(private readonly RequeteHelper $requeteHelper)
    {
    }

    public function fromListLine(array $line): ActesTransactionRow
    {
        $id = (int) $line['transaction_id'];
        $type = TypeTransaction::from((int) $line['type']);
        $statusId = (int) $line['last_status_id'];

        return new ActesTransactionRow(
            id: $id,
            showUrl: $this->showUrl($id),
            internalNumber: (string) ($line['number'] ?? ''),
            type: $type,
            typeLabel: (string) ($line['type_str'] ?? ''),
            subject: (string) ($line['subject'] ?? ''),
            nature: (string) ($line['nature_descr'] ?? ''),
            authorityName: (string) ($line['authority_name'] ?? ''),
            submittedAt: ListLineReader::submissionDate($line, $id),
            status: StatusBadge::fromActes($statusId, (string) ($line['current_status_name'] ?? '')),
            followerName: ListLineReader::fullName($line),
            isSelectable: $type === TypeTransaction::TransmissionActe
                && (ActesStatus::tryFrom($statusId)?->allowsBulkClosing() ?? false),
            archiveUrl: $this->httpArchiveUrl($line['archive_url'] ?? null),
            followUps: $this->followUps($line['courrier_info'] ?? []),
        );
    }

    private function showUrl(int $transactionId): string
    {
        return $this->requeteHelper->getLink(self::SHOW_PATH . $transactionId);
    }

    private function httpArchiveUrl(?string $archiveUrl): ?string
    {
        if ($archiveUrl === null || preg_match('#^https?://#i', $archiveUrl) !== 1) {
            return null;
        }

        return $archiveUrl;
    }

    private function followUps(array $courrierInfo): array
    {
        $followUps = [];
        foreach ($courrierInfo as $transactionId => $info) {
            $direction = $info['sens'] ?? self::DEFAULT_FOLLOW_UP_DIRECTION;
            $followUps[] = new ActesFollowUp($info['type_str'] . " ($direction)", $this->showUrl((int) $transactionId));
        }

        return $followUps;
    }
}
