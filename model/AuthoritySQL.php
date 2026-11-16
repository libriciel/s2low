<?php

namespace S2lowLegacy\Model;

use S2low\DTO\AuthoritySearch;
use S2low\Enum\AdministeredModule;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\UnrecoverableException;

class AuthoritySQL extends SQL
{
    /**
     * Les deux groupes administrateurs se nomment séparément : un seul champ ne peut plus les dire.
     */
    private const ADMINISTERING_GROUPS_JOIN =
        " LEFT JOIN authority_groups actes_groups ON actes_groups.id=authorities.actes_group_id" .
        " LEFT JOIN authority_groups helios_groups ON helios_groups.id=authorities.helios_group_id";


    public static function getSAEProperties()
    {
        return  array(
            'pastell_url' => "URL Pastell",
            'pastell_login' => "Login",
            'pastell_password' => "Mot de passe",
            'pastell_id_e' => "Identifiant de l'entité (id_e)"
        );
    }

    public static function getSAEPropertiesType($properties)
    {
        return ($properties == 'pastell_password') ? "password" : "text";
    }

    public function getIdBySIREN($siren)
    {
        $sql = "SELECT id FROM authorities where siren=?";
        return $this->queryOne($sql, $siren);
    }

    public function getAll()
    {
        $result = array();
        $sql = "SELECT authorities.id, authorities.name FROM authorities ORDER BY authorities.name ASC";
        foreach ($this->query($sql) as $line) {
            $result[$line['id']] = $line['name'];
        }
        return $result;
    }

    public function getColonneNameForExport()
    {
        return [
            'authorities.name' => 'Nom',
            'email' => 'Adresse email',
            'siren' => 'Numéro SIREN',
            'address' => 'Adresse',
            'postal_code' => "Code Postal",
            'city' => 'Ville',
            'telephone' => 'Numéro de téléphone',
            'fax' => 'Numéro de fax',
            'department' => 'Département',
            'district' => 'Arrondissement',
            'authorities.status' => 'Status',
            'actes_groups.name as actes_group_name' => 'Groupe administrateur Actes',
            'helios_groups.name as helios_group_name' => 'Groupe administrateur Helios',
            'authority_types.description' => 'Type'
        ];
    }

    public function getAllForExport($authority_group_id = 0)
    {
        $fields = implode(',', array_keys($this->getColonneNameForExport()));

        $sql = "SELECT $fields, authorities.actes_group_id, authorities.helios_group_id FROM authorities " .
        self::ADMINISTERING_GROUPS_JOIN .
        " LEFT JOIN authority_types ON authorities.authority_type_id = authority_types.id " ;
        if ($authority_group_id) {
            $sql .= " WHERE authorities.actes_group_id = ? OR authorities.helios_group_id = ? ORDER BY name";
            return $this->query($sql, $authority_group_id, $authority_group_id);
        }
        $sql .= " ORDER BY name";

        return $this->query($sql);
    }

    /**
     * @return array<int, string>
     */
    public function getAllAdministeredBy(int $authorityGroupId): array
    {
        $result = array();
        $sql = "SELECT authorities.id, authorities.name FROM authorities " .
            " WHERE authorities.actes_group_id = ? OR authorities.helios_group_id = ? ORDER BY authorities.name ASC";
        foreach ($this->query($sql, $authorityGroupId, $authorityGroupId) as $line) {
            $result[$line['id']] = $line['name'];
        }
        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getAllAdministeredForModuleBy(AdministeredModule $module, int $authorityGroupId): array
    {
        $result = [];
        $sql = "SELECT id, name FROM authorities WHERE {$module->groupColumn()} = ? ORDER BY name ASC";
        foreach ($this->query($sql, $authorityGroupId) as $line) {
            $result[$line['id']] = $line['name'];
        }
        return $result;
    }

    public function updateSAE($authority_id, PastellProperties $pastellProperties)
    {
        $sql = "UPDATE authorities SET pastell_url=?,pastell_login=?,pastell_id_e=? " .
            " WHERE id = ?";
        $this->query(
            $sql,
            $pastellProperties->url,
            $pastellProperties->login,
            $pastellProperties->id_e,
            $authority_id
        );

        if ($pastellProperties->password) {
            $sql = "UPDATE authorities SET pastell_password=? WHERE id=?";
            $this->query($sql, $pastellProperties->password, $authority_id);
        }
    }

    public function verifDepartmentAndDistrict($department_code, $district_code)
    {
        $sql = "SELECT * FROM authority_departments " .
                " JOIN authority_districts ON authority_department_id=authority_departments.id " .
                " WHERE authority_departments.code=?  AND authority_districts.code=?";
        $result = $this->query($sql, $department_code, $district_code);
        return count($result);
    }

    public function search(AuthoritySearch $search, int $offset, int $limit): array
    {
        $administeringGroupsJoin = self::ADMINISTERING_GROUPS_JOIN;
        $sql = <<<SQL
            SELECT authorities.*, authority_types.description AS type_name,
                actes_groups.name AS actes_group_name, helios_groups.name AS helios_group_name
            FROM authorities
            $administeringGroupsJoin
            LEFT JOIN authority_types ON authority_types.id = authorities.authority_type_id
            WHERE {$this->searchConditions()}
            ORDER BY authorities.name
            LIMIT :limit OFFSET :offset
            SQL;

        return $this->query($sql, [...$this->searchParameters($search), 'limit' => $limit, 'offset' => $offset]);
    }

    public function count(AuthoritySearch $search): int
    {
        $sql = <<<SQL
            SELECT count(*)
            FROM authorities
            WHERE {$this->searchConditions()}
            SQL;

        return (int)$this->queryOne($sql, $this->searchParameters($search));
    }

    private function searchConditions(): string
    {
        return <<<SQL
            (:authorityTypeId = 0 OR authorities.authority_type_id = :authorityTypeId)
            AND (:name = '' OR authorities.name ILIKE :namePattern)
            AND (:siren = '' OR authorities.siren LIKE :sirenPattern)
            AND (:siret = '' OR EXISTS (
                SELECT 1 FROM authority_siret
                WHERE authority_siret.authority_id = authorities.id AND authority_siret.siret LIKE :siretPattern
            ))
            AND (:administeringGroupId = 0
                OR authorities.actes_group_id = :administeringGroupId
                OR authorities.helios_group_id = :administeringGroupId)
            AND (:actesGroupId = 0 OR authorities.actes_group_id = :actesGroupId)
            AND (:heliosGroupId = 0 OR authorities.helios_group_id = :heliosGroupId)
            SQL;
    }

    private function searchParameters(AuthoritySearch $search): array
    {
        return [
            'authorityTypeId' => $search->authorityTypeId,
            'name' => $search->name,
            'namePattern' => "%$search->name%",
            'siren' => $search->siren,
            'sirenPattern' => "%$search->siren%",
            'siret' => $search->siret,
            'siretPattern' => "%$search->siret%",
            'administeringGroupId' => $search->administeringGroupId,
            'actesGroupId' => $search->actesGroupId,
            'heliosGroupId' => $search->heliosGroupId,
        ];
    }

    /**
     * @param $authority_id
     * @throws UnrecoverableException
     */
    public function verifHasPastell($authority_id)
    {
        $authorityInfo = $this->getInfo($authority_id);
        if (! $authorityInfo['pastell_url']) {
            throw new UnrecoverableException("La collectivité n'a pas de Pastell configuré");
        }
    }

    public function getInfo($id)
    {
        $sql = "SELECT * FROM authorities WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function updateDoNotVerifyNomFicUnicity($authority_id, $helios_do_not_verify_nom_fic_unicity)
    {
        $sql = "UPDATE authorities SET helios_do_not_verify_nom_fic_unicity=? WHERE id=?";
        $this->query($sql, $helios_do_not_verify_nom_fic_unicity ? 1 : 0, $authority_id);
    }

    public function clearAdministeringGroup(int $authorityId, AdministeredModule $module): void
    {
        $sql = "UPDATE authorities SET {$module->groupColumn()} = NULL WHERE id = ?";
        $this->query($sql, $authorityId);
    }

    public function create($name, $siren)
    {
        $sql = "INSERT INTO authorities (name,siren) VALUES (?,?) RETURNING id";
        return $this->queryOne($sql, $name, $siren);
    }
}
