<?php

/*
 * Tedetis - Copyright 2006 Alternance-Soft
 * Contributeur : C. Pop, Mars 2007
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant � la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  � l'utilisation,  � la modification et/ou au
 * développement et � la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe � 
 * manipuler et qui le réserve donc � des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités � charger  et  tester  l'adéquation  du
 * logiciel � leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * �l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder � cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/

?>
<?php

/**
 * \class HeliosTransactionWorkflow HeliosTransactionWorkflow.class.php
 * \brief Cette classe permet de g�rer le workflow des transactions Helios
 * \author Cristina Pop <cpop@alternancesoft.com> et J�r�me Schell <j.schell@alternancesoft.com>
 * \date 20.02.2007
 * 
 *
 * Cette classe fournit des m�thodes de gestion des transactions Helios.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once (SITEROOT . "/class/DataObject.class.php");
require_once (SITEROOT . "/class/Parapheur.class.php");

class HeliosTransactionWorkflow extends DataObject {
  protected $objectName = "helios_transactions_workflow";

  //des variables qui corresp. aux champs de la BD "helios_transactions_workflow"
  protected $id; // id de workflow
  protected $transaction_id; //id de la transactions
  protected $status_id; //status de la transaction
  protected $date; //le timestamp de la transaction
  protected $message; //message concernant la transaction

  //d'ici variables apparement non-utilis�es ....................................
  public $files = array ();
  private $fileNameSerial;

  protected $related_transaction;

  protected $last_classification_date;

  protected $xmlFileName;
  protected $xmlFilesize;
  protected $xmlObj;

  private $workflow = array ();

  protected $rootDir;
  protected $destDir;

  //..............................................................................

  protected $dbFields = array (
      // "id" => array( "descr" => "Identifiant dans le table de workflow", "type" => "isInt", "mandatory" => true),
  "transaction_id" => array (
      "descr" => "Identifiant de la transaction",
      "type" => "isInt",
      "mandatory" => true
    ),
    "status_id" => array (
      "descr" => "status du fichier",
      "type" => "isInt",
      "mandatory" => true
    ),
    "date" => array (
      "descr" => "le timestamp de la transaction ",
      "type" => "isDate",
      "mandatory" => true
    ),
    "message" => array (
      "descr" => "un message concernant la transaction",
      "type" => "isString",
      "mansatory" => true
    )
  );

  /**
   * \brief Constructeur d'une transaction
   * \param id integer Num?o d'identifiant d'une transaction existante avec laquelle initialiser l'objet
   */
  public function __construct($id = false) {
    parent :: __construct($id);

    $this->fileNameSerial = 1;
  }

  /**
   * \brief M?hode initialisant l'entit?avec l'identifiant courant
   * \return true si succ?, false sinon
  */
  public function init() {
    if (!parent :: init()) {
      return false;
    }

    return true;
  }

  /**
   * \brief M�hode permettant de fixer la valeur d'un attribut
   * \param $name  : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */

  public function set($name, $val) {
    switch ($name) {
      case "transaction_id" :
      case "status_id" :
      case "destDir" : // A VOIR 
        parent :: set("rootDir", HELIOS_FILES_UPLOAD_ROOT);
        break;
    }
    parent :: set($name, $val);
  }

  /*
   * Methodes statiques
   * 
   */

  /**
    * \brief M�hode permettant de recuperer la date � la quelle le fichier a ete post�   
    * 
    * \param $transaction_id 
    * \return la date
   */
  public static function getDatePoste($transaction_id) {
    $sql = "SELECT date from helios_transactions_workflow where status_id=1 AND transaction_id=" . $transaction_id;
    //SELECT id FROM actes_transactions WHERE unique_id='" . addslashes($unique_id) . "' AND type=1";

    $db = & DatabasePool :: getInstance();
    $result = $db->select($sql);

    if (!$result->isError() && $result->num_row() == 1) {
      $row = $result->get_next_row();
      return $row["date"];
    }

    return false;
  }

  public static function getCurrentStatus($transaction_id) {
    $db = & DatabasePool :: getInstance();

    $sql = "SELECT message FROM helios_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM helios_transactions_workflow atw2 WHERE atw2.transaction_id = atw.transaction_id) AND transaction_id = " . $transaction_id . " ORDER BY atw.id DESC LIMIT 1";

    $result = $db->select($sql);

    if (!$result->isError() && $result->num_row() == 1) {
      $row = $result->get_next_row();
      return $row["message"];
    }

    return false;
  }
  public static function getCurrentDate($transaction_id)
  {
  	$db = & DatabasePool :: getInstance();
    $sql = "SELECT submission_date FROM helios_transactions WHERE id=". $transaction_id;

    $result = $db->select($sql);

    if (!$result->isError() && $result->num_row() == 1) {
      $row = $result->get_next_row();
      return $row["submission_date"];
    }

    return false;
  }
}
?>
