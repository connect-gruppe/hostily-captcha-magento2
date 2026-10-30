<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model;

use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigExtensionInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * Pruefeinstellungen des Typs "hostily". Die Klasse ist das Erkennungszeichen fuer das Plugin am Validator:
 * Konfigurationen anderer Typen (Google) laufen unveraendert durch.
 */
class ValidationConfig implements ValidationConfigInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly string $remoteIp,
        private readonly string $serviceUrl,
        private readonly bool $blockOnError,
        private readonly string $validationFailureMessage = ''
    ) {
    }

    public function getPrivateKey(): string
    {
        return $this->secret;
    }

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function getValidationFailureMessage(): string
    {
        return $this->validationFailureMessage;
    }

    public function getExtensionAttributes(): ?ValidationConfigExtensionInterface
    {
        return null;
    }

    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }

    public function blocksOnError(): bool
    {
        return $this->blockOnError;
    }
}
