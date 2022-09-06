<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;

class PastellPropertiesSQL extends SQL
{
    public function getPastellProperties($authority_id)
    {
        $sql = "SELECT * FROM authorities WHERE id=?";
        $authority_info  = $this->queryOne($sql, $authority_id);

        $pastellProperties = new PastellProperties();

        $pastellProperties->url = $authority_info['pastell_url'];
        $pastellProperties->login = $authority_info['pastell_login'];
        $pastellProperties->password = $authority_info['pastell_password'];
        $pastellProperties->id_e = $authority_info['pastell_id_e'];

        $actes_module_id = $this->getModuleId(ModuleSQL::ACTES_MODULE_NAME);
        $sql = "SELECT * FROM authority_pastell_config WHERE authority_id=? AND module_id=?";
        $actes_info = $this->queryOne($sql, $authority_id, $actes_module_id);
        if ($actes_info) {
            $pastellProperties->actes_flux_id = $actes_info['id_flux'];
            $pastellProperties->actes_action = $actes_info['action'];
            $pastellProperties->actes_send_auto = $actes_info['is_auto'];
            $pastellProperties->actes_destination = $actes_info['destination'];
            $pastellProperties->actes_transaction_id_min = $actes_info['transaction_id_min'];
            $pastellProperties->actes_transaction_id_max = $actes_info['transaction_id_max'];
        }

        $actes_module_id = $this->getModuleId(ModuleSQL::HELIOS_MODULE_NAME);
        $sql = "SELECT * FROM authority_pastell_config WHERE authority_id=? AND module_id=?";
        $helios_info = $this->queryOne($sql, $authority_id, $actes_module_id);
        if ($helios_info) {
            $pastellProperties->helios_flux_id = $helios_info['id_flux'];
            $pastellProperties->helios_action = $helios_info['action'];
            $pastellProperties->helios_send_auto = $helios_info['is_auto'];
            $pastellProperties->helios_destination = $helios_info['destination'];
            $pastellProperties->helios_transaction_id_min = $helios_info['transaction_id_min'];
            $pastellProperties->helios_transaction_id_max = $helios_info['transaction_id_max'];
        }


        return $pastellProperties;
    }

    public function editProperties($authority_id, PastellProperties $pastellProperties)
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

        $sql = "SELECT authority_pastell_config.id FROM authority_pastell_config " . " 
        JOIN modules ON authority_pastell_config.module_id = modules.id " .
            " WHERE authority_id=? AND modules.name=?";
        $authority_pastell_config_id = $this->queryOne($sql, $authority_id, ModuleSQL::ACTES_MODULE_NAME);

        $actes_module_id = $this->getModuleId(ModuleSQL::ACTES_MODULE_NAME);

        if ($authority_pastell_config_id) {
            $sql = "UPDATE authority_pastell_config SET " .
                " authority_id=?, module_id=?, id_flux=?,action=?,is_auto=?,destination=?,transaction_id_min=?,transaction_id_max=? " .
                " WHERE id=?";
            $this->queryOne(
                $sql,
                $authority_id,
                $actes_module_id,
                $pastellProperties->actes_flux_id,
                $pastellProperties->actes_action,
                $pastellProperties->actes_send_auto ? 't' : 'f',
                $pastellProperties->actes_destination,
                $pastellProperties->actes_transaction_id_min,
                $pastellProperties->actes_transaction_id_max,
                $authority_pastell_config_id
            );
        } else {
            $sql = "INSERT INTO authority_pastell_config(authority_id, module_id, id_flux, action, is_auto,destination,transaction_id_min,transaction_id_max) " .
                " VALUES (?,?,?,?,?,?,?,?)";
            $this->query(
                $sql,
                $authority_id,
                $actes_module_id,
                $pastellProperties->actes_flux_id,
                $pastellProperties->actes_action,
                $pastellProperties->actes_send_auto ? 't' : 'f',
                $pastellProperties->actes_destination,
                $pastellProperties->actes_transaction_id_min,
                $pastellProperties->actes_transaction_id_max
            );
        }



        $sql = "SELECT authority_pastell_config.id FROM authority_pastell_config " . " 
        JOIN modules ON authority_pastell_config.module_id = modules.id " .
            " WHERE authority_id=? AND modules.name=?";
        $authority_pastell_config_id = $this->queryOne($sql, $authority_id, ModuleSQL::HELIOS_MODULE_NAME);

        $helios_module_id = $this->getModuleId(ModuleSQL::HELIOS_MODULE_NAME);

        if ($authority_pastell_config_id) {
            $sql = "UPDATE authority_pastell_config SET " .
                " authority_id=?, module_id=?, id_flux=?,action=?,is_auto=?,destination=?,transaction_id_min=?,transaction_id_max=? " .
                " WHERE id=?";

            $this->queryOne(
                $sql,
                $authority_id,
                $helios_module_id,
                $pastellProperties->helios_flux_id,
                $pastellProperties->helios_action,
                $pastellProperties->helios_send_auto ? 't' : 'f',
                $pastellProperties->helios_destination,
                $pastellProperties->helios_transaction_id_min,
                $pastellProperties->helios_transaction_id_max,
                $authority_pastell_config_id
            );
        } else {
            $sql = "INSERT INTO authority_pastell_config(authority_id, module_id, id_flux, action, is_auto,destination,transaction_id_min,transaction_id_max) " .
                " VALUES (?,?,?,?,?,?,?,?)";
            $this->query(
                $sql,
                $authority_id,
                $helios_module_id,
                $pastellProperties->helios_flux_id,
                $pastellProperties->helios_action,
                $pastellProperties->helios_send_auto ? 't' : 'f',
                $pastellProperties->helios_destination,
                $pastellProperties->helios_transaction_id_min,
                $pastellProperties->helios_transaction_id_max
            );
        }
    }


    private function getModuleId($module_name)
    {
        return $this->queryOne(
            "SELECT id FROM modules WHERE name=?",
            $module_name
        );
    }
}
