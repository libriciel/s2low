<?php

namespace S2lowLegacy\Model;

use S2low\Model\AdministeredAuthorities;
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

        $sql = "SELECT $fields FROM authorities " .
        self::ADMINISTERING_GROUPS_JOIN .
        " LEFT JOIN authority_types ON authorities.authority_type_id = authority_types.id " ;
        if ($authority_group_id) {
            $condition = AdministeredAuthorities::conditionForGroup((int)$authority_group_id);
            $sql .= " WHERE $condition";
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
        $condition = AdministeredAuthorities::conditionForGroup($authorityGroupId);
        $sql = "SELECT authorities.id, authorities.name FROM authorities WHERE $condition ORDER BY authorities.name ASC";
        foreach ($this->query($sql) as $line) {
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

    public function getList($authority_group_id, $authority_type_id, $name, $siren, $siret, $offset, $limit)
    {
        $sql = "SELECT authorities.*, authority_types.description as type_name,
                    actes_groups.name as actes_group_name, helios_groups.name as helios_group_name
                    FROM authorities " .
                    self::ADMINISTERING_GROUPS_JOIN .
                    " LEFT JOIN authority_types ON authorities.authority_type_id = authority_types.id ";
        $data = array();
        if ($siret) {
            $sql .= " JOIN authority_siret ON authorities.id=authority_siret.authority_id";
        }
        $sql .= " WHERE 1=1 ";
        if ($siret) {
            $sql .= " AND authority_siret.siret LIKE ? ";
            $data[] = "%$siret%";
        }
        if ($authority_group_id) {
            $sql .= " AND " . AdministeredAuthorities::conditionForGroup((int)$authority_group_id);
        }
        if ($authority_type_id) {
            $sql .= " AND authority_type_id = ?";
            $data[] = $authority_type_id;
        }
        if ($name) {
            $sql .=  " AND authorities.name ILIKE ? ";
            $data[] = "%$name%";
        }
        if ($siren) {
            $sql .=  " AND siren LIKE ? ";
            $data[] = "%$siren%";
        }
        $offset = intval($offset);
        $limit = intval($limit);
        $sql .= " ORDER BY name LIMIT $limit OFFSET $offset";
        return $this->query($sql, $data);
    }

    public function getNb($authority_group_id, $authority_type_id, $name, $siren, $siret)
    {
        $sql = "SELECT count(*) FROM authorities ";
        $data = array();
        if ($siret) {
            $sql .= " JOIN authority_siret ON authorities.id=authority_siret.authority_id";
        }
        $sql .= " WHERE 1=1 ";
        if ($siret) {
            $sql .= " AND authority_siret.siret LIKE ? ";
            $data[] = "%$siret%";
        }
        if ($authority_group_id) {
            $sql .= " AND " . AdministeredAuthorities::conditionForGroup((int)$authority_group_id);
        }
        if ($authority_type_id) {
            $sql .= " AND authority_type_id = ?";
            $data[] = $authority_type_id;
        }
        if ($name) {
            $sql .= " AND name ILIKE ? ";
            $data[] = "%$name%";
        }
        if ($siren) {
            $sql .=  " AND siren LIKE ? ";
            $data[] = "%$siren%";
        }
        return $this->queryOne($sql, $data);
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

    public function create($name, $siren)
    {
        $sql = "INSERT INTO authorities (name,siren) VALUES (?,?) RETURNING id";
        return $this->queryOne($sql, $name, $siren);
    }
}
