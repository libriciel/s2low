<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\SQLQuery;
use Libriciel\LibActes\ActesXSD;
use S2lowLegacy\Model\AuthoritySQL;
use SimpleXMLElement;

class ActesUpdateClassificationSQL extends SQL
{
    private $authoritySQL;

    public function __construct(SQLQuery $sqlQuery, AuthoritySQL $authoritySQL)
    {
        parent::__construct($sqlQuery);
        $this->authoritySQL = $authoritySQL;
    }

    public function updateClassification($siren, $xml_content)
    {
        try {
            $this->getConnection()->beginTransaction();
            $this->updateClassificationThrow($siren, $xml_content);
            $this->getConnection()->commit();
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        }
    }

    private function updateClassificationThrow($siren, $fichier_xml)
    {
        $authority_id = $this->authoritySQL->getIdBySIREN($siren);
        if (! $authority_id) {
            throw new Exception("Aucune collectivité ne correspond au SIREN $siren");
        }
        $actesXSD = new \Libriciel\LibActes\ActesXSD();

        $xml = $actesXSD->getSimpleXMLForActesContent($fichier_xml);
        if ($xml->getName() != 'RetourClassification') {
            throw new Exception("Le message n'est pas un retour de classification: {$xml->getName()} trouvé.");
        };

        $date_classification =  strval($xml->xpath("/actes:RetourClassification/actes:DateClassification")[0]);

        $this->updateClassificationRequest($siren, $date_classification, $fichier_xml);

        $this->deleteActesTypePJ();
        $this->deleteActeNature();

        $actes_nature_list = $xml->xpath("/actes:RetourClassification/actes:NaturesActes/actes:NatureActe");

        foreach ($actes_nature_list as $actes_nature) {
            $codeNatureActe =  strval($actes_nature->xpath('@actes:CodeNatureActe')[0]);
            $typeAbrege = strval($actes_nature->xpath('@actes:TypeAbrege')[0]);
            $libelle = strval($actes_nature->xpath('@actes:Libelle')[0]);
            $this->insertActeNature($codeNatureActe, $typeAbrege, $libelle);
        }
        $this->updateCodePJ($xml);


        $this->deleteClassification($authority_id);
        $matiere = $xml->xpath("/actes:RetourClassification/actes:Matieres")[0];
        ;
        $this->ajoutMatieres($matiere, null, 1, $authority_id);
    }

    private function updateCodePJ(SimpleXMLElement $xml)
    {
        foreach ($xml->xpath("//actes:TypePJNatureActe") as $type_pj) {
            $code = strval($type_pj->xpath("@actes:CodeTypePJ")[0]);
            $libelle = strval($type_pj->xpath("@actes:Libelle")[0]);
            $nature_id = strval($type_pj->xpath("parent::actes:NatureActe/@actes:CodeNatureActe")[0]);
            $this->insertActeTypePJ($nature_id, $code, $libelle);
        }
    }

    private function ajoutMatieres(SimpleXMLElement $matiere, $parent_id, $level, $authority_id)
    {

        /** @var SimpleXMLElement $matiere_children */
        foreach ($matiere->children(ActesXSD::ACTES_NAMESPACE) as $matiere_children) {
            $code = intval($matiere_children->attributes(ActesXSD::ACTES_NAMESPACE)->{'CodeMatiere'});
            $libelle = strval($matiere_children->attributes(ActesXSD::ACTES_NAMESPACE)->{'Libelle'});

            $new_parent_id = $this->insertClassification($level, $code, $parent_id, $libelle, $authority_id);
            $this->ajoutMatieres($matiere_children, $new_parent_id, $level + 1, $authority_id);
        }
    }

    private function insertClassification($level, $code, $parent_id, $description, $authority_id)
    {
        $sql = "INSERT INTO actes_classification_codes (level, code, parent_id, description, authority_id)" .
                " VALUES (?,?,?,?,?) RETURNING ID";

        return $this->queryOne($sql, $level, $code, $parent_id, $description, $authority_id);
    }

    public function getClassificationCode($authority_id)
    {
        $sql = "SELECT * FROM actes_classification_codes WHERE authority_id=? ORDER BY id";
        return $this->query($sql, $authority_id);
    }

    private function updateClassificationRequest($siren, $date_classification, $xml_data)
    {
        $sql = "UPDATE actes_classification_requests SET version_date = ?, xml_data = ? " .
                " WHERE version_date IS NULL AND requested_by IN ( SELECT users.id FROM  users, authorities" .
                " WHERE users.authority_id = authorities.id AND authorities.siren = ? )";

        $this->getConnection()->executeStatement(
            $sql,
            [$date_classification, $xml_data, $siren],
            [\Doctrine\DBAL\ParameterType::STRING, \Doctrine\DBAL\ParameterType::LARGE_OBJECT, \Doctrine\DBAL\ParameterType::STRING]
        );
    }

    /**
     * @deprecated 5.0.42, dead code, only used in test
     */
    public function getClassification($siren)
    {
        $sql = "SELECT xml_data FROM actes_classification_requests WHERE requested_by IN ( SELECT users.id FROM  users, authorities" .
            " WHERE users.authority_id = authorities.id AND authorities.siren = ? ) ORDER BY version_date LIMIT 1";

        $xml_data = $this->getConnection()->fetchOne($sql, [$siren]);

        if (is_resource($xml_data)) {
            $contents = stream_get_contents($xml_data);
            fclose($xml_data);
            return $contents;
        }
        return $xml_data;
    }

    public function deleteActeNature()
    {
        $sql = "DELETE FROM actes_natures";
        $this->query($sql);
    }

    public function deleteActesTypePJ()
    {
        $sql = "DELETE FROM actes_type_pj";
        $this->query($sql);
    }

    public function insertActeNature($codeNatureActe, $typeAbrege, $libelle)
    {
        $sql = "INSERT into actes_natures (id, short_descr, descr) VALUES (?,?,?)";
        $this->query($sql, $codeNatureActe, $typeAbrege, $libelle);
    }

    public function insertActeTypePJ($codeNatureActe, $code, $libelle)
    {
        $sql = "INSERT into actes_type_pj (nature_id, code, libelle) VALUES (?,?,?)";
        $this->query($sql, $codeNatureActe, $code, $libelle);
    }

    public function getActeNature()
    {
        $sql = "SELECT * FROM actes_natures ORDER BY id";
        return $this->query($sql);
    }

    public function deleteClassification($authority_id)
    {
        $sql = "DELETE FROM actes_classification_codes WHERE authority_id  = ?";
        $this->query($sql, $authority_id);
    }
}
