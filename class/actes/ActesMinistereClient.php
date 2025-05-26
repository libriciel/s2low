<?php

namespace S2lowLegacy\Class\actes;

use Exception;
use S2lowLegacy\Class\CurlWrapper;

class ActesMinistereClient
{
    public function __construct(
        private readonly string $actesMinistereUrl,
        private readonly int $actesMinistereErrorCode,
        private readonly CurlWrapper $curlWrapper,
        private readonly ICertificateValidationStrategy $certificateValidationStrategy,
    ) {
    }

    /**
     * @throws Exception
     */
    public function send($filepath): void
    {
        $this->curlWrapper->addPostFile(basename($filepath), $filepath);
        $this->curlWrapper->get($this->actesMinistereUrl);

        if ($this->curlWrapper->getHTTPCode() != $this->actesMinistereErrorCode) {
            throw new Exception($this->curlWrapper->getLastError());
        }

        $this->certificateValidationStrategy->postConnectionAction($this->curlWrapper);
    }
}
