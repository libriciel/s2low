<?php

/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 *
 * contact@alternancesoft.com
 *
  * Ce logiciel est un programme informatique servant à la
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
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement,
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité.
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
/**
 * \class mail_annuaire  mail_annuaire.class.php
 * \brief Cette classe permet de modeliser le tableau correspond de mail_annuraire
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * Cette classe fournit des méthodes de traiter le tableau mail_annuaire
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

require_once(SITEROOT . "/class/DataObject.class.php");
class mail_annuaire extends DataObject
{
    protected $objectName = "mail_annuaire";
    protected $id;
    protected $user_id;
    protected $mail_address;
    protected $description;

    protected $dbFields =  array(
    "authority_id"          => array( "descr" => "Identifiant mail", "type" => "isInt", "mandatory" => true),
    "mail_address"  => array("descr" => "---", "type" => "isString", "mandatory" => true),
    "description"   => array("descr" => "---", "type" => "isString", "mandatory" => true),
    );

    function __construct($id = false)
    {
        parent::__construct($id);
    }

    public function newSave($address, $authority_id, $description)
    {
        $this->mail_address = $address;
        $this->authority_id = $authority_id;
        $this->description = $description;
        parent::save(false);
        return true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthority_id()
    {
        return $this->authority_id;
    }

    public function getMailAddress()
    {
        return $this->mail_address;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getMail()
    {
        return $this->mail_address . "-" . $this->description;
    }
}
