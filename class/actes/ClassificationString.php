<?php

namespace S2lowLegacy\Class\actes;

class ClassificationString
{
    private string $string = '';

    public function __construct(?array $transactionInfo)
    {
        if (isset($transactionInfo['classification'])) {
            $this->string = $transactionInfo['classification'];
        }

        if (isset($transactionInfo['classification_string']) && !empty($transactionInfo['classification_string'])) {
            $this->string .= " - " . $transactionInfo['classification_string'];
        }
    }

    public function get(): string
    {
        return $this->string;
    }
}
