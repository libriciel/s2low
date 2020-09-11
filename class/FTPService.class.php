<?php


class FTPService
{
    private const TIMEOUT = 1;
    private $host;
    private $port;
    private $login;
    private $password;
    /**
     * @var false|resource
     */
    private $ftp;
    private $currentDirectorySyntax;
    private $ftpServiceWrapper;
    private $modeDemo;
    private $isPassiveMode;

    public function __construct(
        FtpServiceWrapper $ftpServiceWrapper,
        $helios_ftp_server,
        $helios_ftp_port,
        $helios_ftp_login,
        $helios_ftp_password,
        $helios_sending_mode_demo,
        $helios_ftp_passive_mode,
        $helios_ftp_pst_mode
    )
    {
        $this->ftpServiceWrapper = $ftpServiceWrapper;
        $this->host = $helios_ftp_server;
        $this->port = $helios_ftp_port;
        $this->login = $helios_ftp_login;
        $this->password = $helios_ftp_password;
        $this->modeDemo = $helios_sending_mode_demo;
        $this->isPassiveMode = $helios_ftp_passive_mode;
        $this->isPstMode = $helios_ftp_pst_mode;


        //Attention, sur un serveur normal, c'est . par contre sur le site de la DGFip , c'est ./
        if ($this->modeDemo) {                     //TODO : vérifier que la config marche et est pertinente
            $this->currentDirectorySyntax = ".";
            $this->delete = true;
        } else {
            $this->currentDirectorySyntax = "./";
            $this->delete = false;
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function connect()
    {
        $mode = $this->isPassiveMode ? "Passif" : "Actif";
        $demo = $this->modeDemo ? "[MODE DEMO]":"";
        $protocol = $this->isPstMode ? "ftps" : "ftp";
        echo "Connection à $protocol://{$this->login}: {$this->password}@{$this->host }:{$this->port} (mode $mode) $demo\n";

        if($this->isPstMode){
            $this->ftp = $this->ftpServiceWrapper->sslConnect($this->host, $this->port, self::TIMEOUT);
        } else {
            $this->ftp = $this->ftpServiceWrapper->connect($this->host, $this->port, self::TIMEOUT);
        }


        if (!$this->ftp) {
            throw new Exception("Impossible de se connecter au serveur {$this->host}:{$this->port}");
        }
        echo "Connecté\n";
        if ($this->login) {
            $ftp_login = $this->ftpServiceWrapper->login($this->ftp, $this->login, $this->password);
            if (!$ftp_login) {
                throw new Exception("Impossible de se connecter avec le login {$this->login}");
            }
        }
        echo "Loggé\n";

        $this->setPassiveMode($this->isPassiveMode);
    }

    /**
     * @param string $remote_path
     * @return array|false
     * @throws Exception
     */
    public function getFileNames(string $remote_path)
    {
        if (!$this->ftpServiceWrapper->chdir($this->ftp, $remote_path)) {
            throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
        }

        $all_file = $this->ftpServiceWrapper->nlist($this->ftp, $this->currentDirectorySyntax);

        if ($all_file === false) {
            throw new Exception("Impossible de lister le contenu du répertoire distant $remote_path");
        }

        echo "Il y a " . count($all_file) . " fichiers en attente dans le repertoire distant $remote_path...\n";

        return $all_file;
    }

    /**
     * @param string $tmp_file
     * @param $file
     * @param $local_path
     * @return bool
     * @throws Exception
     */
    public function retrieveFile($file, $local_path): bool
    {
        $tmp_file = $this->createTmpFile($local_path);
        $ftp_get_result = $this->ftpServiceWrapper->get($this->ftp, $tmp_file, "$file", FTP_ASCII);

        if (!$ftp_get_result) {
            throw new Exception("Impossible de récupérer le fichier $file pour le mettre sur $tmp_file sur le FTP {$this->host}");
        }

        $rename_result = rename($tmp_file, "$local_path/$file");
        if (!$rename_result) {
            throw new Exception("Impossible de déplacer le fichier $tmp_file vers $local_path/$file");
        }

        if ($this->delete) {
            $this->ftpServiceWrapper->delete($this->ftp, $file);
        }
        return $ftp_get_result;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createTmpFile($localPath): string
    {
        $tmp_file = sys_get_temp_dir() . "/s2low_helios_ftp_retrieve_" . mt_rand(0, mt_getrandmax());

        if (disk_free_space($localPath) < 1000000 || disk_free_space(dirname($tmp_file)) < 1000000) {
            throw new Exception("Il ne reste pas assez d'espace sur le disque !");
        }
        return $tmp_file;
    }

    /**
     */
    public function disconnect(): void
    {
        $this->ftpServiceWrapper->close($this->ftp);
    }


    /**
     * Permet de switcher en mode passif
     * @param boolean $is_pasv
     */
    public function setPassiveMode($is_pasv){
        $this->ftpServiceWrapper->pasv ($this->ftp,$is_pasv);
    }

    public function sendRawCommand($command){
        $result = $this->ftpServiceWrapper->raw($this->ftp, $command);
        if ($this->modeDemo){
            return ;
        }
        if (!$result || ! preg_match("#^200#",$result[0])){
            $message =  "[FAILED] Send FTP raw command\n$command\n********** RESULT *******\n";
            $message .= implode("\n",$result)."\n";
            $message .=  "******** END RESULT ************\n";
            throw new Exception($message);
        }
    }

    public function sendOneFile($directory_destination,$file_path){
        $result = $this->ftpServiceWrapper->put($this->ftp,$directory_destination.basename($file_path),$file_path,FTP_BINARY);
        if (! $result){
            throw new Exception("Erreur lors de l'envoi du fichier ".basename($file_path) ." vers le serveur FTP");
        }
    }
}