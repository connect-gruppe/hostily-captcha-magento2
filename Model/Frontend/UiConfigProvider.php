<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model\Frontend;

use Hostily\Captcha\Model\Config;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\ReCaptchaUi\Model\UiConfigProviderInterface;

/**
 * Einstellungen fuer das Widget im Browser (kommen als "settings" in die Komponente Magento_ReCaptchaFrontendUi/js/reCaptcha).
 * Der Schluessel "rendering.sitekey" ist der, den Magento fuer "Captcha ist eingerichtet" pruefen (IsCaptchaEnabled).
 */
class UiConfigProvider implements UiConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly DynamicCollector $cspCollector
    ) {
    }

    public function get(): array
    {
        $url = $this->config->getServiceUrl();
        if ($url !== '' && $this->config->getSiteKey() !== '') {
            $this->allowInCsp($url);
        }

        return [
            'rendering' => [
                'sitekey' => $this->config->getSiteKey(),
            ],
            'invisible' => $this->config->isInvisible(),
            'hostily' => [
                'api' => $url === '' ? '' : $url . '/api/',
                'script' => $url === '' ? '' : $url . '/widget/hostily-captcha.js',
                'mode' => $this->config->isInvisible() ? 'unsichtbar' : 'sichtbar',
                'language' => $this->config->getLanguage(),
                'tokenField' => Config::TOKEN_FIELD,
            ],
        ];
    }

    /**
     * Eine eigene Dienst-Adresse (nicht captcha.hostily.de) kommt zur Laufzeit in die Content-Security-Policy.
     */
    private function allowInCsp(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!\is_string($host) || $host === '') {
            return;
        }
        $port = parse_url($url, PHP_URL_PORT);
        $source = $host . ($port ? ':' . $port : '');
        foreach (['script-src', 'connect-src'] as $directive) {
            $this->cspCollector->add(new FetchPolicy($directive, false, [$source]));
        }
    }
}
