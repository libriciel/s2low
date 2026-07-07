<?php

namespace S2lowLegacy\Mail;

use S2low\Services\MailActesNotifications\MailerSymfony;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\FileUploader;

/**
 * Modifications :
 * Auteur   Date       Commentaire
 * PEV      10/08/2010 des problemes d'encodage sur les dates. Ligne 136, utf8_encode la date.
 */


class Annuaire
{
    private array $tabError;
    private array $tabOK;
    private array $tabAlreadyExist;

    private $authority_id;

    public function __construct(
        private readonly Database $bd,
        $authority_id,
    ) {
        $this->tabError = [];
        $this->tabOK = [];
        $this->tabAlreadyExist = [];
        $this->authority_id = $authority_id;
    }

    public function mailExists($email)
    {
        $sql = "SELECT * FROM mail_annuaire WHERE mail_address='$email' AND authority_id=" . $this->authority_id;
        $result = $this->bd->select($sql);
        return $result->num_row();
    }

    public function import(FileUploader $uploader)
    {
        while ($res = $uploader->getLigne()) {
            $this->traiteLigne($res);
        }
    }

    private function traiteLigne($ligne)
    {
        $ligne_array = explode(',', $ligne);
        if (count($ligne_array) < 2) {
            $this->tabError[] = $ligne;
            return false;
        }
        $description = trim($ligne_array[0]);
        $email = trim($ligne_array[1]);

        $groupe_name = "";
        if (isset($ligne_array[2])) {
            $groupe_name = trim($ligne_array[2]);
        }
        if (! MailerSymfony::isValidMail($email)) {
            $this->tabError[] = $ligne;
            return false;
        }
        $chaine = "$description &lt;$email&gt";

        if ($this->mailExists($email)) {
            $this->tabAlreadyExist[] = $chaine;
            return false;
        }

        $id_user = $this->saveAnnuaire($email, $description);


        $this->tabOK[] = $chaine;
    }

    private function saveAnnuaire($email, $description)
    {
        $annuaire = new MailAnnuaire();
        $annuaire->set("mail_address", $email);
        $annuaire->set("description", $description);
        $annuaire->set("authority_id", $this->authority_id);
        $annuaire->save(false);
        return $annuaire->getId();
    }

    public function getNbContact()
    {
        $sql = "SELECT count(*) as nb FROM mail_annuaire WHERE authority_id=" . $this->authority_id;
        $result = $this->bd->select($sql);
        $ligne = $result->get_next_row();
        return $ligne['nb'];
    }

    public function getListeMailAndGroupe($begin)
    {
        $tabMail = array();

        $begin = $this->bd->getConnection()->quote("$begin%");

        $sql = "SELECT name FROM mail_groupe WHERE name ILIKE $begin AND authority_id=" . $this->authority_id . " ORDER BY name";
        $result = $this->bd->select($sql);
        while ($row = $result->get_next_row()) {
            $tabMail[] = $row['name'] . " (groupe)";
        }

        $sql = "SELECT description,mail_address FROM mail_annuaire " .
                " WHERE (mail_address ILIKE $begin OR description ILIKE $begin) AND authority_id =" . $this->authority_id .
                " ORDER by description,mail_address";
        $result = $this->bd->select($sql);
        while ($row = $result->get_next_row()) {
            if ($row['description']) {
                $row['description'] = strtr($row['description'], ",", ";");

                $tabMail[] = '"' . get_hecho($row['description'], ENT_QUOTES)  . '" [' . $row['mail_address'] . ']';
            } else {
                $tabMail[] = $row['mail_address'];
            }
        }
        return $tabMail;
    }

    public function getAllMail()
    {
        $result = array();
        $sql =  "SELECT mail_annuaire.*, mail_groupe.name as groupe_name FROM mail_annuaire " .
                " LEFT JOIN mail_user_groupe ON mail_annuaire.id=mail_user_groupe.id_user " .
                " LEFT JOIN mail_groupe ON mail_user_groupe.id_groupe = mail_groupe.id" .
                " WHERE mail_annuaire.authority_id=" . $this->authority_id;
        foreach ($this->bd->fetchAll($sql) as $info) {
            if (empty($result[$info['id']])) {
                $result[$info['id']] = $info;
                $result[$info['id']]['groupe'] = array();
                if ($info['groupe_name']) {
                    $result[$info['id']]['groupe'][] = $info['groupe_name'];
                }
            } else {
                $result[$info['id']]['groupe'][] = $info['groupe_name'];
            }
        }
        return $result;
    }
}
