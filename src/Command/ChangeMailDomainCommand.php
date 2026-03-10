<?php

namespace S2low\Command;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Model\AuthoritySQL;
use Exception;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use S2lowLegacy\Class\User;

class ChangeMailDomainCommand extends Command
{
    private $s2lowLogger;
    /**
     * @var User
     */
    private $user;
    /**
     * @var UserSQL
     */
    private $userSQL;
    /**
     * @var AuthoritySQL
     */
    private $authoritySQL;


    public function __construct(
        LoggerInterface $s2lowLogger,
        User $user,
        UserSQL $userSQL,
        AuthoritySQL $authoritySQL
    ) {
        $this->s2lowLogger = $s2lowLogger;
        $this->user = $user;
        $this->userSQL = $userSQL;
        $this->authoritySQL = $authoritySQL;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('admin:change-mail-domain-name-for-a-group')
            ->setDescription(
                "Changes the mail adress domain name for a given group"
            )
            ->addOption(
                'dry-run',
                '',
                InputOption::VALUE_NONE,
                "do not process"
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                "process without questions"
            )
            ->addArgument(
                'group-id',
                InputArgument::REQUIRED,
                "The group_id which mail adresses will be modified"
            )
            ->addArgument(
                'domain-name-to-replace',
                InputArgument::REQUIRED,
                "the domain name that will be replaced"
            )
            ->addArgument(
                'target-domain-name',
                InputArgument::REQUIRED,
                "the domain name that will replace domain-name-to-replace"
            );
    }

    private function askIfNeeded(InputInterface $input, SymfonyStyle $io): bool
    {
        if ($input->getOption('dry-run')) {
            $io->writeln("Dry run mode : halt");
            $io->success('Pass');
            return false;
        }
        if (!$input->getOption('force')) {
            $question = new ConfirmationQuestion("Are you sure ?", false);
            $response = $io->askQuestion($question);
            if (!$response) {
                $this->s2lowLogger->notice("Operation canceled");
                $io->success('Cancel');
                return false;
            }
        }
        return true;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        try {
            $authorities = $this->getAuthoritiesTomodify(
                (int)$input->getArgument('group-id'),
                $input->getArgument('domain-name-to-replace')
            );
            $usersToModify = $this->getUsersToModify(
                (int)$input->getArgument('group-id'),
                $input->getArgument('domain-name-to-replace')
            );
        } catch (Exception $exception) {
            $io->error($exception->getMessage());
            return 0;
        }

        $usersAndMails = $this->generateArrayWithNewMails(
            $usersToModify,
            $input->getArgument('domain-name-to-replace'),
            $input->getArgument('target-domain-name')
        );
        $this->displayChangesBeforeValidation($io, $authorities, $usersAndMails, $usersToModify);

        if (!$this->askIfNeeded($input, $io)) {
            return 0;
        }

        $this->updateMails($usersAndMails);
        $io->success('Done');
        return 0;
    }

    /**
     * @param $domaineOrigine
     * @param $domaineCible
     * @param $email
     * @return array|string|string[]|null
     */
    protected function getModifiedMail($domaineOrigine, $domaineCible, $email)
    {
        return preg_replace("#@$domaineOrigine#", "@$domaineCible", $email);
    }

    /**
     * @param int $group_id
     * @param $existingMailDomain
     * @return array
     * @throws \Exception
     */
    protected function getAuthoritiesTomodify(int $group_id): array
    {
        $authorities = $this->authoritySQL->getAllGroup($group_id);

        if (count($authorities) === 0) {
            throw new Exception("Aucune autorité liée au groupe $group_id, ou le groupe n'existe pas");
        }

        return $authorities;
    }

    /**
     * @param int $group_id
     * @param $existingMailDomain
     * @return array
     * @throws \Exception
     */
    protected function getUsersTomodify(int $group_id, $existingMailDomain): array
    {
        $usersToModify = $this->user->getUsersList(
            "WHERE authorities.authority_group_id= $group_id AND users.email LIKE '%@$existingMailDomain'"
        );

        if ($usersToModify === false) {
            return [];
        }
        return $usersToModify;
    }

    /**
     * @param $usersToModify
     * @param $existingMailDomain
     * @param $targetMailDomain
     * @return array
     */
    protected function generateArrayWithNewMails($usersToModify, $existingMailDomain, $targetMailDomain): array
    {
        $tableToDisplay = [];
        foreach ($usersToModify as $user) {
            $tableToDisplay[] = [
                $user["id"],
                $user["email"],
                $this->getModifiedMail($existingMailDomain, $targetMailDomain, $user["email"])
            ];
        }
        return $tableToDisplay;
    }

    /**
     * @param \Symfony\Component\Console\Style\SymfonyStyle $io
     * @param $authorities
     * @param array $usersAndMails
     * @param $usersToModify
     */
    protected function displayChangesBeforeValidation(SymfonyStyle $io, $authorities, array $usersAndMails, $usersToModify): void
    {
        $io->note("Modified Authorities : " . implode(" ; ", $authorities));
        $io->table(
            ["user.id", "user.email (original)", "user.email (target)"],
            $usersAndMails
        );
        $io->writeln(count($usersToModify) . " users to modify");
    }

    /**
     * @param array $usersAndMails
     */
    protected function updateMails(array $usersAndMails): void
    {
        foreach ($usersAndMails as $user) {
            $this->userSQL->updateMail(
                $user[0],
                $user[2]
            );
        }
    }
}
