<?php

namespace S2low\Command\Debug;

use ReflectionClass;
use S2lowLegacy\Class\LegacyObjectsManager;
use Exception;
use S2lowLegacy\Lib\SQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class CompareInstanciations extends Command
{
    private const NOT_INSTANCIABLE_CLASSES = [
        SQL::class
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('debug:compare-instanciations')
            ->setDescription(
                "Compares instanciations between service container and object instancier"
            )
            ->addArgument(
                'namespace',
                InputArgument::OPTIONAL,
                'Tested namespace',
                'S2lowLegacy'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        global $kernel;
        $classes_ok = [];
        $classes_instanciated_but_different = [];
        $classes_with_exception = [];
        foreach (get_declared_classes() as $class) {
            try {
                if (
                    str_starts_with((new ReflectionClass($class))->getNamespaceName(), $input->getArgument('namespace'))
                    &&
                    !in_array($class, self::NOT_INSTANCIABLE_CLASSES)
                ) {
                    $object_instanciated_by_container = $kernel->getContainer()->get($class);
                    $object_instanciated_by_object_instancier = LegacyObjectsManager::getLegacyObjectInstancier()->get($class);
                    if ($object_instanciated_by_container == $object_instanciated_by_object_instancier) {
                        $classes_ok[] = $class;
                    } else {
                        try {
                            $classes_instanciated_but_different[] = [
                                $class,
                                var_export($object_instanciated_by_object_instancier, true),
                                var_export($object_instanciated_by_container, true),
                            ];
                        } catch (Exception $e) {
                            $classes_instanciated_but_different[] = [
                                $class,
                                'var_export non défini',
                                'var_export non défini',
                            ];
                        }
                    }
                }
            } catch (Throwable $e) {
                $classes_with_exception[] = [$class, (new ReflectionClass($e))->getShortName(), $e->getMessage()];
            }
        }
            $io->title('Classes correctement instanciées');
        foreach ($classes_ok as $class) {
            $output->writeln($class);
        }
            $io->title('Classes instanciées différement');
                $io->table(
                    ['nom','objet instancié par object instancier', 'objet instancié par container'],
                    $classes_instanciated_but_different
                );
        $io->title('Classes dont l\'instanciation génère une exception');
        $io->table(
            ['nom','type d\'exception', 'message'],
            $classes_with_exception
        );
                return 0;
    }
}
