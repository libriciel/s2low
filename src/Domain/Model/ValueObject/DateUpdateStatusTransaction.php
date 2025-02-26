<?php

namespace S2low\Domain\Model\ValueObject;

class DateUpdateStatusTransaction
{
    private \DateTimeImmutable $date;

    public function __construct(string $date = new \DateTimeImmutable())
    {
        $this->date = $date;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}