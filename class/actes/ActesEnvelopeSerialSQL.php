<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\Database;

class ActesEnvelopeSerialSQL
{
    private $db;
    private $errorMsg;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getLastError()
    {
        return $this->errorMsg;
    }

    public function getNext($authority_id)
    {
        if (! $this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        // On vérifie si le numéro doit être remis à zéro ou pas
        $sql = "SELECT reset_date, serial " .
                " FROM actes_envelope_serials " .
                " WHERE authority_id=$authority_id FOR UPDATE";
        $result = $this->db->select($sql);

        if ($result->isError()) {
            $this->errorMsg = "Erreur d'accès base de données.";
            return false;
        }

        if ($result->num_row() <= 0) {
          // Pas encore d'entrée pour cette collectivité
            $date = date('Y-m-d');
            $sql = "INSERT INTO actes_envelope_serials (authority_id, reset_date, serial) VALUES($authority_id, '$date', 2)";

            if (! $this->db->exec($sql)) {
                $this->errorMsg = "Erreur d'accès base de données.";
                $this->db->rollback();
                return false;
            } else {
                $this->db->commit();
                return 1;
            }
        } else {
            $row = $result->get_next_row();
            $date = mb_substr($row["reset_date"], 0, 10);
            $today = date('Y-m-d');

            if (strcmp($today, $date) != 0) {
              // La dernière remise à zéro n'est pas d'aujourd'hui => on remet à zéro le compteur
                $sql = "UPDATE actes_envelope_serials SET reset_date=?, serial=2 WHERE authority_id=?" ;

                if (! $this->db->exec($sql, [$today,$authority_id])) {
                    $this->errorMsg = "Erreur d'accès base de données.";
                    $this->db->rollback();
                    return false;
                } else {
                    $this->db->commit();
                    return 1;
                }
            } else {
              // Mise à jour aujourd'hui => on incrémente le numéro de série

            // pour le bug 240, modifié par HTan, 15-12-2008
                if ($row['serial'] >= 10000 || $row['serial'] <= 0) {
                    $this->errorMsg = "Serial number: déborder 10000. Vérifier serial est modifié par quelqu'un ou on a commité plus que 9999 tranactions par jour.";
                    return false;
                }
              //-----

                $sql = "UPDATE actes_envelope_serials SET serial=serial+1 WHERE authority_id=$authority_id";

                if (! $this->db->exec($sql)) {
                    $this->errorMsg = "Erreur d'accès base de données.";
                    $this->db->rollback();
                    return false;
                } else {
                    $this->db->commit();
                    return $row["serial"];
                }
            }
        }
    }
}
