<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model\Adminhtml;

use Magento\ReCaptchaUi\Model\UiConfigProviderInterface;

/**
 * hostily Captcha schuetzt nur den Shop (Storefront), nicht die Administration. Damit eine versehentlich dort
 * gewaehlte Einstellung die Anmeldung nicht zerstoert, ist der Typ im Admin-Bereich "nicht eingerichtet" (Captcha aus).
 */
class UiConfigProvider implements UiConfigProviderInterface
{
    public function get(): array
    {
        return ['rendering' => ['sitekey' => ''], 'invisible' => false];
    }
}
