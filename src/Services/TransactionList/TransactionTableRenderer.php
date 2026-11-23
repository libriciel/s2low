<?php

declare(strict_types=1);

namespace S2low\Services\TransactionList;

use S2low\DTO\TransactionList\ActesTransactionTable;
use Twig\Environment;

final class TransactionTableRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function renderActes(ActesTransactionTable $table): string
    {
        return $this->twig->render('transaction_list/actes.html.twig', ['table' => $table]);
    }
}
