<?php

namespace S2low\DTO;

use S2low\Enum\ActesStatus;
use Symfony\Component\Validator\Constraints as Assert;

class ActesSearchCriteria
{
    // Pagination
    public int $page = 1;
    public int $count = 10;

    // Tri
    #[Assert\Choice(choices: ['asc', 'desc'])]
    public string $sortway = 'desc';
    public string $order = 'id';

    // Filtres simples
    public ?string $objet = null;
    public ?ActesStatus $status = ActesStatus::EN_COURS;
    public ?string $authority = null;

    // Filtres avancés
    public ?string $nature = null;
    public ?string $type = null;
    public ?string $num = null;

    public ?string $min_submission_date = null;
    public ?string $max_submission_date = null;
    public ?string $min_ack_date = null;
    public ?string $max_ack_date = null;
    public ?string $datepicker_min_submission_date = null;
    public ?string $datepicker_max_submission_date = null;
    public ?string $datepicker_min_ack_date = null;
    public ?string $datepicker_max_ack_date = null;
}
