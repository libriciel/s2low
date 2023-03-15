<?php

namespace S2lowLegacy\Class;

class PdfStampMessages
{
    private string $messageEnvoi;
    private string $messageReception;
    private string $messagePublication;

    public function __construct($use_prod_notifications)
    {
        $this->messageEnvoi = "Envoi simulé le";
        $this->messageReception = "Reception simulée le";
        $this->messagePublication = "Publication simulée le";

        if ($use_prod_notifications) {
            $this->messageEnvoi = "Envoyé en préfecture le";
            $this->messageReception = "Reçu en préfecture le";
            $this->messagePublication = "Publié le";
        }
    }

    /**
     * @return string
     */
    public function getMessageEnvoi(): string
    {
        return $this->messageEnvoi;
    }

    /**
     * @return string
     */
    public function getMessageReception(): string
    {
        return $this->messageReception;
    }

    /**
     * @return string
     */
    public function getMessagePublication(): string
    {
        return $this->messagePublication;
    }
}
