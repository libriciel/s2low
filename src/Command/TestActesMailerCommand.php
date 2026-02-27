<?php

namespace S2low\Command;

use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestActesMailerCommand extends Command
{
    public function __construct(private readonly MailerInterface $mailer)
    {
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        $this
            ->setName('test:test-actes-mailer')
            ->setDescription(
                "Tests actes notification sending"
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws LogicException When this abstract method is not implemented
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     *
     * @see setCode()
     */
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        $email = (new Email())
            ->from(TDT_FROM_EMAIL)
            ->to("no-reply@libriciel.fr")
            ->subject("[TestActesMailerCommand] subject")
            ->text("[TestActesMailerCommand] body");

        $filePath = __DIR__ . "/../../tests/Services/fixtures/test_pdf.pdf";
        $email->attachFromPath($filePath, "attached_from_path");
        $email->attach(file_get_contents($filePath), "attached_from_content", "application/pdf");

        $this->mailer->send($email);
        return 0;
    }
}
