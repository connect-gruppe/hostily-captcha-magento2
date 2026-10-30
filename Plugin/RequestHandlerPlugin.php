<?php

declare(strict_types=1);

namespace Hostily\Captcha\Plugin;

use Hostily\Captcha\Model\ActiveType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\ReCaptchaUi\Model\CaptchaTypeResolverInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;

/**
 * Meldet vor jeder Formularpruefung, ob das Formular (Schluessel wie "contact") den Typ "hostily" hat.
 */
class RequestHandlerPlugin
{
    public function __construct(
        private readonly CaptchaTypeResolverInterface $typeResolver,
        private readonly ActiveType $activeType
    ) {
    }

    /**
     * @return array<int, mixed>
     */
    public function beforeExecute(
        RequestHandlerInterface $subject,
        string $key,
        RequestInterface $request,
        HttpResponseInterface $response,
        string $redirectOnFailureUrl
    ): array {
        $this->activeType->set(\in_array($this->typeResolver->getCaptchaTypeFor($key), ['hostily', 'frontend_hostily'], true));

        return [$key, $request, $response, $redirectOnFailureUrl];
    }
}
