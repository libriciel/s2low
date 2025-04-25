<?php

namespace S2low\Command\Debug;

use ReflectionClass;
use S2lowLegacy\Class\actes\ActesEnvelopeStorage;
use S2lowLegacy\Class\actes\ActesNameArchive;
use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesPdfLegacy;
use S2lowLegacy\Class\actes\ClassificationString;
use S2lowLegacy\Class\actes\FilesNotFoundInCloudException;
use S2lowLegacy\Class\actes\TypeTransaction;
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
        SQL::class,
        ActesPdfLegacy::class,
        ActesPdf::class,
        ActesEnvelopeStorage::class,
        TypeTransaction::class,          //enum
        ClassificationString::class,     // le construct utilise transaction_info
        ActesNameArchive::class,          // pas utilisé comme service
        FilesNotFoundInCloudException::class
    ];

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
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
                                $e->getMessage(),
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
        $io->title('Classes dont l\'instanciation génère une exception');
        $io->table(
            ['nom','type d\'exception', 'message'],
            $classes_with_exception
        );
        $io->title('Classes instanciées différement');
        foreach ($classes_instanciated_but_different as $class) {
            $output->writeln($class[0]);
            $output->writeln($class[1]);
            $output->writeln($class[2]);
        }
                return 0;
    }
}
