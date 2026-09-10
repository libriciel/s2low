<?php

declare(strict_types=1);

namespace S2low\Model;

use S2low\Enum\AdministeredModule;

/**
 * La condition SQL qui retient les collectivités qu'un groupe administre.
 *
 * Elle vaut pour toute requête portant sur la table `authorities` : un groupe les administre dès
 * qu'elles le désignent pour un seul de leurs modules. Le groupe est interpolé après conversion en
 * entier, les appelants construisant leur requête par concaténation.
 */
final class AdministeredAuthorities
{
    public static function conditionForGroup(int $groupId): string
    {
        $conditions = array_map(
            static fn(AdministeredModule $module): string => "authorities.{$module->groupColumn()} = $groupId",
            AdministeredModule::cases()
        );

        return '(' . implode(' OR ', $conditions) . ')';
    }
}
