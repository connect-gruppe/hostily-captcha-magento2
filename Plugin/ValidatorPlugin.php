<?php

declare(strict_types=1);

namespace Hostily\Captcha\Plugin;

use Hostily\Captcha\Model\ValidationConfig;
use Hostily\Captcha\Model\Verifier;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;
use Magento\ReCaptchaValidationApi\Api\ValidatorInterface;

/**
 * Setzt fuer Konfigurationen des Typs "hostily" die Pruefung beim hostily-Dienst an die Stelle der Pruefung bei Google.
 * Alle anderen Konfigurationen laufen unveraendert durch (proceed). Gilt fuer Formulare (RequestHandler) und fuer die
 * REST-Schnittstelle der Kasse (X-ReCaptcha).
 */
class ValidatorPlugin
{
    public function __construct(
        private readonly Verifier $verifier,
        private readonly ValidationResultFactory $resultFactory
    ) {
    }

    public function aroundIsValid(
        ValidatorInterface $subject,
        callable $proceed,
        string $reCaptchaResponse,
        ValidationConfigInterface $validationConfig
    ): ValidationResult {
        if (!$validationConfig instanceof ValidationConfig) {
            return $proceed($reCaptchaResponse, $validationConfig);
        }
        $result = $this->verifier->verify($reCaptchaResponse, $validationConfig);
        $errors = [];
        if ($result['status'] === 'rejected') {
            // Bekannter "Pruef-Fehler": Magento zeigt die Meldung "reCAPTCHA verification failed." (einstellbar)
            $errors['hostily-rejected'] = 'hostily Captcha: ' . implode(', ', $result['reasons']);
        } elseif ($result['status'] === 'error') {
            // Unbekannter Code = technischer Fehler: Magento zeigt die technische Meldung und schreibt ins Protokoll
            $errors['hostily-unavailable'] = 'hostily Captcha: service unavailable';
        }

        return $this->resultFactory->create(['errors' => $errors]);
    }
}
