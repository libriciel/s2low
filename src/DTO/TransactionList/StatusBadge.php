<?php

declare(strict_types=1);

namespace S2low\DTO\TransactionList;

use InvalidArgumentException;
use S2low\Enum\ActesStatus;
use S2low\Enum\HeliosStatus;
use S2low\Enum\StatusTone;

final readonly class StatusBadge
{
    private const string TITLE_SEPARATOR = ' — ';

    private function __construct(
        public string $text,
        public string $title,
        public ?StatusTone $tone,
    ) {
        if ($text === '') {
            throw new InvalidArgumentException("Le texte d'un état ne peut pas être vide");
        }
    }

    public static function fromActes(int $statusId, string $statusLabel): self
    {
        $status = ActesStatus::tryFrom($statusId);
        $label = $statusLabel !== '' ? $statusLabel : self::fallbackLabel($statusId);

        if ($status === null) {
            return new self($label, $label, null);
        }

        return new self($status->badgeLabel(), $label, $status->tone());
    }

    public static function fromHelios(int $statusId, string $statusLabel, string $lastMessage): self
    {
        $status = HeliosStatus::tryFrom($statusId);
        $label = $statusLabel !== '' ? $statusLabel : ($lastMessage !== '' ? $lastMessage : self::fallbackLabel($statusId));
        $title = $lastMessage !== '' && $lastMessage !== $label ? $label . self::TITLE_SEPARATOR . $lastMessage : $label;

        if ($status === null) {
            return new self($label, $title, null);
        }

        return new self($status->badgeLabel(), $title, $status->tone());
    }

    public function isRecognized(): bool
    {
        return $this->tone !== null;
    }

    private static function fallbackLabel(int $statusId): string
    {
        return 'État ' . $statusId;
    }
}
