<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Einstellungen des Captcha-Typs "hostily" (Stores > Konfiguration > Sicherheit > Google reCAPTCHA Storefront).
 * Alle Werte stehen unter recaptcha_frontend/type_hostily/ und werden im Geltungsbereich der aktuellen Store-View
 * gelesen (faellt auf Website und Standard zurueck).
 */
class Config
{
    public const DEFAULT_URL = 'https://captcha.hostily.de';
    public const TOKEN_FIELD = 'hostily-captcha-token';
    public const TYPE = 'hostily';

    private const PATH = 'recaptcha_frontend/type_hostily/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function getSiteKey(): string
    {
        return $this->value('site_key');
    }

    /**
     * Das Secret steht verschluesselt in der Datenbank (Backend-Modell "Encrypted"); aus config.php oder
     * einer Umgebungsvariable darf es auch im Klartext kommen.
     */
    public function getSecret(): string
    {
        $value = $this->value('secret');
        if ($value !== '' && preg_match('/^\d+:\d+:/', $value) === 1) {
            return trim($this->encryptor->decrypt($value));
        }

        return $value;
    }

    /**
     * Dienst-Adresse ohne Schraegstrich am Ende; nur http(s), sonst leer.
     */
    public function getServiceUrl(): string
    {
        $url = rtrim($this->value('service_url'), '/');
        if ($url === '') {
            $url = self::DEFAULT_URL;
        }

        return preg_match('#^https?://[^\s/]+#i', $url) === 1 ? $url : '';
    }

    public function isInvisible(): bool
    {
        return $this->value('mode') === 'invisible';
    }

    /**
     * @return string "de", "en" oder "" (automatisch: Sprache der Seite)
     */
    public function getLanguage(): string
    {
        $lang = $this->value('language');

        return $lang === 'de' || $lang === 'en' ? $lang : '';
    }

    public function blocksOnError(): bool
    {
        return $this->value('on_error') === 'block';
    }

    public function isConfigured(): bool
    {
        return $this->getSiteKey() !== '' && $this->getSecret() !== '' && $this->getServiceUrl() !== '';
    }

    private function value(string $name): string
    {
        return trim((string) $this->scopeConfig->getValue(self::PATH . $name, ScopeInterface::SCOPE_STORE));
    }
}
