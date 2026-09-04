<?php

declare(strict_types=1);

namespace S2low\Exceptions;

use Exception;

/**
 * La désignation des groupes administrateurs demandée par le formulaire est refusée : groupe
 * manquant, groupe désactivé, ou collectivité créée sans aucun groupe administrateur.
 */
class GroupDesignationRefusedException extends Exception
{
}
