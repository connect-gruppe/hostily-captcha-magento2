<?php

declare(strict_types=1);

namespace Hostily\Captcha\Plugin;

use Hostily\Captcha\Model\ActiveType;
use Hostily\Captcha\Model\Config;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\ReCaptchaUi\Model\CaptchaResponseResolverInterface;

/**
 * Magento liest die Loesung aus dem Formularfeld "g-recaptcha-response". Das hostily-Widget legt "hostily-captcha-token" an;
 * fehlt das Google-Feld, wird dieses gelesen. Fehlt auch das, bleibt es bei der Ausnahme von Magento ("Can not resolve
 * reCAPTCHA parameter") - ausser das Formular hat den Typ "hostily": dann geht ein leeres Token zum Dienst
 * (erreichbar: abgelehnt; nicht erreichbar: es gilt "Bei Ausfall", sonst sperrt ein nicht ladendes Widget alle aus).
 */
class ResponseResolverPlugin
{
    public function __construct(private readonly ActiveType $activeType)
    {
    }

    public function aroundResolve(CaptchaResponseResolverInterface $subject, callable $proceed, RequestInterface $request): string
    {
        try {
            return $proceed($request);
        } catch (InputException $e) {
            if (!$this->activeType->isHostily()) {
                throw $e;
            }
            $token = $request->getParam(Config::TOKEN_FIELD);

            return \is_string($token) ? $token : '';
        }
    }
}
