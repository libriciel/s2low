<?php

namespace S2lowLegacy\Boot;

use S2lowLegacy\Class\User;
use S2lowLegacy\Controller\PostgreSQLController;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class S2lowBootstrap
{
    public function __construct(
        private readonly SQLQuery $sqlQuery,
        private readonly PostgreSQLController $postgreSQLController,
        #[Autowire(service: 'console.messenger.application')]
        private readonly Application $app
    ) {
    }

    public function bootstrap()
    {
        $this->log("Initialisation de S2low");
        try {
            // Ajout du certificat domaine s2low
            $this->installSelfSignedCertificateIfNoneExists(
                $this->getHostname(),
                "privkey.pem",
                "fullchain.pem",
                apacheSSLPath: '/etc/apache2/ssl/app',
            );
            // Ajout du certificat domaine mailsec
            $this->installSelfSignedCertificateIfNoneExists(
                $this->getMailHostname(),
                "privkey.pem",
                "fullchain.pem",
                apacheSSLPath: '/etc/apache2/ssl/mailsec',
            );
            $this->sqlQuery->waitStarting(function ($m) {
                echo "$m\n";
            });
            $this->dbUpdate();
            $this->insertDemoS();
            $this->populateDatabase();
        } catch (\Exception $e) {
            $this->log("Erreur : " . $e->getMessage());
        }
    }

    /**
     * @param string $hostname
     * @param string $privKeyFilename
     * @param string $fullchainFilename
     * @return void
     * @throws \Exception
     */
    public function installSelfSignedCertificateIfNoneExists(
        string $hostname,
        string $privKeyFilename,
        string $fullchainFilename,
        string $letsencryptPath = "/etc/letsencrypt/live",
        string $apacheSSLPath = "/etc/apache2/ssl"
    ): void {
        #TODO : pouvoir utiliser cette fonction pour le certificat du domaine s2low ET du domaine mail sec

        if (file_exists("$apacheSSLPath/$privKeyFilename")) {
            $this->log("Le certificat du site $hostname est déjà présent.");
            return;
        }

        $letsencrypt_cert_path = "$letsencryptPath/$hostname";
        $letsencryptPrivKeyFullPath = "$letsencrypt_cert_path/$privKeyFilename";
        $letsencryptFullchainFullPath = "$letsencrypt_cert_path/{$fullchainFilename}";
        $apachePrivKeyFullPath = "$apacheSSLPath/$privKeyFilename";
        $apacheFullChainFullPath = "$apacheSSLPath/$fullchainFilename";

        if (file_exists($letsencryptPrivKeyFullPath)) {
            $this->log("Certificat letsencrypt trouvé !");
            symlink($letsencryptPrivKeyFullPath, $apachePrivKeyFullPath);
            symlink($letsencryptFullchainFullPath, $apacheFullChainFullPath);
            return;
        }

        $script = __DIR__ . "/../docker-resources/certificate/generate-key-pair.sh";

        exec("$script $hostname $apachePrivKeyFullPath $apacheFullChainFullPath", $output, $return_var);
        $this->log(implode("\n", $output));
        if ($return_var != 0) {
            throw new \Exception("Impossible de générer ou de trouver le certificat du site $hostname !");
        }
    }

    private function dbUpdate()
    {
        $command = $this->app->find('doctrine:migrations:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--no-interaction' => true]);
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
