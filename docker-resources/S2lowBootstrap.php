<?php

namespace S2lowLegacy\Boot;

use S2lowLegacy\Class\User;
use S2lowLegacy\Lib\SQLQuery;

class S2lowBootstrap
{
    public function __construct(
        private readonly SQLQuery $sqlQuery,
    ) {
    }

    public function bootstrap()
    {
        $this->log("Initialisation de S2low");
        try {
            $this->sqlQuery->waitStarting(function ($m) {
                echo "$m\n";
            });
            $this->insertDemoS();
            $this->populateDatabase();
        } catch (\Exception $e) {
            $this->log("Erreur : " . $e->getMessage());
        }
    }

    private function insertDemos()
    {

        if ($this->sqlQuery->queryOne("SELECT * FROM users WHERE role='SADM'")) {
            $this->log("L'utilisateur admin existe déjà");
            return;
        }

        $authority_id = $this->sqlQuery->queryOne(
            "INSERT INTO authorities (id, status, name) VALUES(nextval('authorities_id_seq'), 1, 'Administrateurs') RETURNING id"
        );
        $this->log("Création de l'utilisateur admin [certificat DEMO-SUPER Adullact G3]");

        $him = new User();

        $him->set("name", "admin");
        $him->set("givenname", "admin");
        $him->set("email", "noreply@libriciel.net");
        $him->set("status", 1);
        $him->set("authority_id", $authority_id);
        $him->set("role", 'SADM');

        $him->set("certFilePath", __DIR__ . "/certificate/demosuper.pem");
        if (!$him->save()) {
            throw new \Exception("Erreur lors de l'enregistrement de l'utilisateur : " . $him->getErrorMsg());
        }

        $this->log("Utilisateur créé avec succès");
    }

    public function populateDatabase()
    {
        $data = file_get_contents(__DIR__ . "/database/database_populate.json");
        $all = json_decode($data, true);
        foreach ($all as $table => $table_definition) {
            foreach ($table_definition as $line) {
                $sql = "SELECT * " .
                    " FROM $table WHERE id=?";
                if ($this->sqlQuery->queryOne($sql, $line['id'])) {
                    continue;
                }
                $all_id = array();
                $all_value = array();
                $point = array();
                foreach ($line as $id => $value) {
                    $all_id[] = $id;
                    if ($value === '') {
                        $all_value[] = null;
                    } elseif ($value === "0") {
                        $all_value[] = 0;
                    } else {
                        $all_value[] = $value ?: '';
                    }
                    $point[] = "?";
                }
                $all_id = implode(",", $all_id);
                $point = implode(",", $point);
                $sql2 = "INSERT INTO $table ($all_id) VALUES ($point)";
                $this->log($sql2);
                $this->sqlQuery->query($sql2, $all_value);
            }
        }
    }

    private function log($message)
    {
        echo "[" . date("Y-m-d H:i:s") . "][S2LOW bootstrap] $message\n";
    }

    private function getHostname()
    {
        return parse_url(WEBSITE_SSL, PHP_URL_HOST);
    }

    private function getMailHostname()
    {
        return parse_url(WEBSITE_MAIL, PHP_URL_HOST);
    }
}
