<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model;

/**
 * Merkt sich fuer die laufende Anfrage, ob das gerade geprueften Formular den Typ "hostily" nutzt
 * (gesetzt von RequestHandlerPlugin, gelesen von ResponseResolverPlugin).
 */
class ActiveType
{
    private bool $hostily = false;

    public function set(bool $hostily): void
    {
        $this->hostily = $hostily;
    }

    public function isHostily(): bool
    {
        return $this->hostily;
    }
}
