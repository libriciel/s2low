<?php

namespace S2low\Infrastructure;

class AdminMailer
{
    public function __construct(
        private readonly string $email_admin_technique,
        private readonly string $tdt_from_email
    ) {
    }

    public function sendMailToAdmin($subject, $msg): void
    {
        if (TESTING_ENVIRONNEMENT) {
            return;
        }
        mail($this->email_admin_technique, $subject, $msg, "from: $this->tdt_from_email");
    }
}
