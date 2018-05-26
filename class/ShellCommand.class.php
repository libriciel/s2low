<?php

class ShellCommand {

	private $s2lowLogger;

	private $last_command;
	private $last_output;

	public function __construct(S2lowLogger $s2lowLogger) {
		$this->s2lowLogger = $s2lowLogger;
	}

	public function exec($command){
		$this->s2lowLogger->debug("Execution de la commande : $command");
		exec($command, $output, $ret);
		$this->last_command = $command;
		$this->last_output = $output;
		$this->s2lowLogger->debug(
			"Résultat de l'éxecution de la commande : $command",['ret'=>$ret,'outpuy'=>$output]
		);
		return $ret;
	}

	public function getLastOutputAsString(){
		return implode("\n",$this->last_output);
	}

	public function getLastOutput(){
		return $this->last_output;
	}

	public function getLastCommand(){
		return $this->last_command;
	}

}