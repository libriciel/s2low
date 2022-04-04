<?php

namespace S2low\Services;

use Symfony\Component\Process\Process;

class OpensslVerifyCommand extends CommandLauncher
{
    const VALIDE = "VALIDE";
    /**
     * @var string
     */
    private $authorized_ca_path;

    public function __construct(string $authorized_ca_path)
    {
        $this->authorized_ca_path = $authorized_ca_path;
    }

    /**
     * @throws \RecoverableException
     */
    public function verify(string $certificate_path, array $nonBlockingErrors, string $timestamp =null) : void
    {
        $verifyCmd = ["openssl","verify","-CApath", $this->authorized_ca_path, $certificate_path];

        if($timestamp){
            $verifyCmd = ["openssl","verify","-CApath",$this->authorized_ca_path,"-attime",$timestamp, $certificate_path];
        }

        $this->launch($verifyCmd,$nonBlockingErrors);
    }

    public function getCommandOutput(Process $process, array $resultatAnalysisOptions): AnalysedOutput
    {
        $erreurs = [];
        foreach (explode(PHP_EOL,$process->getErrorOutput()) as $line) {
            if (preg_match("/error ([0123456789]+) at ([0123456789])+ depth lookup:(.*)/", $line, $matches)) {
                $erreurs[]=[
                    "errorCode"=>$matches[1],
                    "depth"=>$matches[2],
                    "message"=>$matches[3]
                ];
            }
        }
        foreach ($erreurs as $erreur) {
            if (!in_array($erreur["errorCode"], $resultatAnalysisOptions)) {
                return new AnalysedOutput("",[$erreur["message"]]);
            }
        }
        return new AnalysedOutput($this::VALIDE);
    }
}