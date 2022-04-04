<?php

namespace S2low\Services;

use Symfony\Component\Process\Process;

abstract class CommandLauncher
{
   protected function launch( $commmand, $resultatAnalysisOptions=[]){
       $process = new Process($commmand);
       try{
           $process->run();
       } catch (\Exception $exception) {
           throw new \RecoverableException( get_class($this) ." : ".$exception->getMessage());
       }

       $commandOutput= $this->getCommandOutput(
           $process,
           $resultatAnalysisOptions
       );

       if($commandOutput->hasBlockingErrors()){
           throw new \Exception($commandOutput->getFirstBlockingErrorMessage());
       }

       if($commandOutput->hasNonBlockingErrors()){
           throw new \RecoverableException($commandOutput->getNonBlockingErrors());
       }
       return $commandOutput->getResult();
   }

    abstract public function getCommandOutput(Process $process, array $resultatAnalysisOptions): AnalysedOutput;
}