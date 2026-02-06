<?php

namespace S2low\Command;

use S2lowLegacy\Class\Database;
use Symfony\Component\Console\Command\Command;

#As
class TestCommand extends Command
{
    public function __construct(
        private readonly Database $db,
    ) {
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('test:cmd');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
//        $data = mb_convert_encoding('ceci esét @`312`21535718@!$#!#&(un test', 'ISO-8859-1', 'UTF-8');
//        $this->connection->exec("INSERT INTO actes_status (id, name)VALUES (26, $data);");
//
//        $res = $this->db->quote('ceci esét @`312\'\'`2\'1535718@!$#!#&(un test');
        var_dump($res);
        $output->writeln('Test command');
        return self::SUCCESS;
    }
}
