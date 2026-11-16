<?php

namespace S2lowLegacy\Model;

use Doctrine\DBAL\ArrayParameterType;
use S2low\Enum\AdministeredModule;
use S2low\DTO\GroupAuthorityCount;
use S2lowLegacy\Lib\SQL;

class GroupSQL extends SQL
{
    public function getInfo($id)
    {
        $sql = "SELECT * FROM authority_groups WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM authority_groups ORDER BY authority_groups.name ASC";
        return $this->query($sql);
    }

    public function edit($id, $name, $status)
    {
        if ($id) {
            $sql = "UPDATE authority_groups SET name=?, status=? WHERE id=?";
            $this->query($sql, $name, $status, $id);
        } else {
            $sql = "INSERT INTO authority_groups(id,name,status) VALUES (nextval('authority_groups_id_seq'),?,?) RETURNING id";
            $id = $this->queryOne($sql, $name, $status);
        }
        return $id;
    }

    public function groupNameAlreadyExists($id, $name)
    {
        $sql = "SELECT id FROM authority_groups WHERE name= ? ";
        $id_from_database = $this->queryOne($sql, $name);

        if (! $id_from_database) {
            return false;
        }

        if ($id) {
            return ($id != $id_from_database);
        }

        return true;
    }

    public function getGroupsIdName()
    {
        $result = [];
        $sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";
        foreach ($this->query($sql) as $line) {
            $result[$line['id']] = $line['name'];
        }
        return $result;
    }

    /**
     * @param int[] $currentAdministeringGroupIds
     * @return array<int, string>
     */
    public function getGroupsEligibleToAdminister(string $siren = '', array $currentAdministeringGroupIds = []): array
    {
        $sql = <<<SQL
SELECT id, name
FROM authority_groups
WHERE (
        status = 1
        AND (
            :siren = ''
            OR EXISTS (
                SELECT 1
                FROM authority_group_siren ags
                WHERE ags.authority_group_id = authority_groups.id
                  AND ags.siren = :siren
            )
        )
    )
   OR id IN (:currentAdministeringGroupIds)
ORDER BY name ASC
SQL;

        return $this->getConnection()->fetchAllKeyValue(
            $sql,
            ['siren' => $siren, 'currentAdministeringGroupIds' => $currentAdministeringGroupIds],
            ['currentAdministeringGroupIds' => ArrayParameterType::INTEGER]
        );
    }

    public function isActive(int $groupId): bool
    {
        return (int)$this->queryOne("SELECT status FROM authority_groups WHERE id = ?", [$groupId]) === 1;
    }

    /**
     * @return list<AdministeredModule>
     */
    public function getAdministeredModules(int $groupId): array
    {
        $sql = <<<SQL
SELECT
    bool_or(actes_group_id = :groupId) AS administers_actes,
    bool_or(helios_group_id = :groupId) AS administers_helios
FROM authorities
SQL;
        $designations = $this->getConnection()->fetchAssociative($sql, ['groupId' => $groupId]);

        $administeredModules = [];
        if ($designations['administers_actes'] === true) {
            $administeredModules[] = AdministeredModule::ACTES;
        }
        if ($designations['administers_helios'] === true) {
            $administeredModules[] = AdministeredModule::HELIOS;
        }
        return $administeredModules;
    }

    public function administers(int $groupId, AdministeredModule $module): bool
    {
        return in_array($module, $this->getAdministeredModules($groupId), true);
    }

    /**
     * @return list<GroupAuthorityCount>
     */
    public function countAuthoritiesPerGroup(): array
    {
        $sql = <<<SQL
SELECT authority_groups.id, authority_groups.name, COUNT(authorities.id) AS authority_count
FROM authority_groups
LEFT JOIN authorities
    ON authority_groups.id IN (authorities.actes_group_id, authorities.helios_group_id)
GROUP BY authority_groups.id, authority_groups.name
ORDER BY authority_groups.name ASC
SQL;

        return array_map(
            static fn(array $line): GroupAuthorityCount => new GroupAuthorityCount(
                (int)$line['id'],
                $line['name'],
                (int)$line['authority_count']
            ),
            $this->getConnection()->fetchAllAssociative($sql)
        );
    }

    public function countAuthoritiesWithoutGroup(): int
    {
        $sql = <<<SQL
SELECT COUNT(*)
FROM authorities
WHERE actes_group_id IS NULL
  AND helios_group_id IS NULL
SQL;

        return (int)$this->getConnection()->fetchOne($sql);
    }
}
