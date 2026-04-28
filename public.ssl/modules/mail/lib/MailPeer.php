<?php

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Log;

/**
 * \class MailPeer  MailPeer.class.php
 * \brief Cette classe permet de traiter entre les tableaux
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter les requete de base de donner et les objets de tableaux.
 * Modifications :
 * Auteur   Date       Commentaire
 * PEV      15/08/2010  Avec Postgres 8.4 les requêtes impliquant la table mail_error posait des problèmes. Les requêtes avaient pour condiction un char = un int.
 */
class MailPeer
{
    public static function mailList(&$MailTransaction, $userId)
    {
        $Field = "id,objet, status,date_envoi ";
        $from = "mail_transaction";
        $cond = "where user_id=" . $userId;
        if ($MailTransaction->pagerInit($Field, $from, $cond)) {
            return $MailTransaction->data;
        } else {
            return false;
        }
    }

    public static function mailSearch(&$MailTransaction, $cond)
    {
        $Field = "DISTINCT id,objet,status,date_envoi ";
        $from = "mail_transaction ";
        $cond = "where " . $cond;
        if ($MailTransaction->pagerInit($Field, $from, $cond)) {
            return $MailTransaction->data;
        } else {
            return false;
        }
    }


    //FIXME fonction catastrophique...
    public static function GetMailEmis($trans_id)
    {
        assert(!!$trans_id);

        $db = DatabasePool::getInstance();

        $MailEmisArray = array();

        $sql = "SELECT id FROM mail_message_emis " .
            " WHERE mail_transaction_id = ?" .
            " ORDER BY email";

        $result = $db->select($sql, [$trans_id]);
        if (!$result->isError()) {
            while ($row = $result->get_next_row()) {
                //FIXME : ICI : on fait une requete par mail
                $obj = new MailMessageEmis($row["id"]);
                if ($obj->init()) {
                    $MailEmisArray[] = $obj;
                }
            }
        }
        return $MailEmisArray;
    }

    public static function GetIncludeFiles($trans_id)
    {
        $MailIncludeFileArray = array();
        if (!empty($trans_id)) {
            $sql = "SELECT id FROM mail_included_file where";
            $sql .= " mail_transaction_id = ?";
            $db = DatabasePool::getInstance();
            $result = $db->select($sql, [$trans_id]);
            if (!$result->isError()) {
                while ($row = $result->get_next_row()) {
                    $obj = new MailIncludedFile($row["id"]);
                    if ($obj->init()) {
                        $MailIncludeFileArray[] = $obj;
                    }
                }
            }
            return $MailIncludeFileArray;
        }
        return false;
    }


    public static function GetAnnuaire($authority_id, $groupe_id = null)
    {
        assert(!!$authority_id);
        $sql = "SELECT * FROM mail_annuaire ";
        $params = [];
        if ($groupe_id) {
            $sql .= " JOIN mail_user_groupe ON mail_annuaire.id = mail_user_groupe.id_user ";
        }
        $sql .= " WHERE mail_annuaire.authority_id=?";
        $params[] = $authority_id;
        if ($groupe_id) {
            $sql .= " AND mail_user_groupe.id_groupe = ?";
            $params[] = $groupe_id;
        }

        $sql .= " ORDER BY COALESCE(description,mail_address) ;";

        $db = DatabasePool::getInstance();
        $result = $db->select($sql, $params);
        return $result->get_all_rows();
    }


    /**
     * \bref:recuperer les email adress et message retour par 2 tableau:
     * MailErrors, MailMessageEmis
     * pour un email spécifier.
     *
     * @param $trans_id =>mail_trainsaction id:
     * @return array|bool un tableau: 2 colone: mail adress et message retour
     */
    public static function GetMailErrors($trans_id)
    {
        if (!empty($trans_id)) {
            $sql = "SELECT mail_message_emis.email, mail_errors.message_retour FROM mail_errors, mail_message_emis where";
            $sql .= " mail_errors.id = mail_message_emis.mail_transaction_id and mail_message_emis.mail_transaction_id= ?";
            $db = DatabasePool::getInstance();
            $result = $db->select($sql, [$trans_id]);
            return $result->get_all_rows();
        }
        return false;
    }

    /**
     * \bref: vérifier une email adress est déjas dans la tableau de annuaire
     *
     * @param string $mail :adress qu'on va vérifier
     * @param integer $userId : pour quelle user
     * @return bool if exist, return true, si non, return false.
     */
    public static function VerifierMailAnnuaire($mail, $authority_id)
    {
        if (!empty($mail)) {
            $db = DatabasePool::getInstance();

            $sql = "SELECT id FROM mail_annuaire WHERE ";
            $sql .= " mail_address=" . $db->getPdo()->quote($mail) . " and authority_id=" . $authority_id;
            $result = $db->select($sql);
            $idArray = $result->get_all_rows();
            return count($idArray) > 0;
        }

        return false;
    }

    /**
     * \bref supprimer l'enregistment(n-uplet) correspond de trans mail id
     * \ aussi les relation sur les autre tableau ;
     * \ : MailMessageEmis
     * \ : MailIncludedFile
     * \ : MailErrors
     * @param integer $transId
     */
    public static function DeleteMailTransation($transId)
    {
        //Note EP : bon, j'ai fais avec les moyens du bord en sachant qu'on ne devrait plus utilisé ce module...
        global $me;


        $mailTransaction = new MailTransaction($transId); //NE FONCTIONNE PLUS (JLG)
        $mailTransaction->init();
        $objet = $mailTransaction->getObjet();

        $message = array();
        $db = DatabasePool::getInstance();

        $sql = "DELETE FROM mail_errors WHERE id IN ";
        $sql .= "(SELECT mail_message_emis.mail_transaction_id FROM mail_message_emis WHERE mail_transaction_id=?)";
        if (!$db->exec($sql, [$transId])) {
            $message[] = "Erreur lors de la suppression de mail_errors ";
        }

        $sql = "delete FROM mail_message_emis WHERE mail_transaction_id=?";

        if (!$db->exec($sql, [$transId])) {
            $message[] = "Erreur lors de la suppression de mail_transaction_id ";
        }

        $sql = "delete FROM mail_included_file WHERE mail_transaction_id=?";
        if (!$db->exec($sql, [$transId])) {
            $message[] = "Erreur lors de la suppression de mail_included_file ";
        }

        $sql = "delete FROM mail_transaction WHERE id=?";
        if (!$db->exec($sql, [$transId])) {
            $message[] = "Erreur lors de la suppression de mail_transaction";
        }
        Log:: newEntry(
            LOG_ISSUER_NAME,
            "Suppression du mail {$objet} (id=$transId)",
            1,
            false,
            'USER',
            'mails',
            $me
        );

        return $message;
    }
}
