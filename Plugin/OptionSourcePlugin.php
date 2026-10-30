<?php

declare(strict_types=1);

namespace Hostily\Captcha\Plugin;

use Magento\Framework\App\RequestInterface;

/**
 * Die Auswahl "hostily Captcha" gibt es nur im Abschnitt Storefront, nicht in den Einstellungen fuer die Administration
 * (beide Abschnitte teilen sich dieselbe Auswahlliste; das Plugin haengt an der Klasse, nicht am virtuellen Typ, fuer den Magento keinen Interceptor erzeugen kann).
 */
class OptionSourcePlugin
{
    public function __construct(private readonly RequestInterface $request)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $result
     *
     * @return array<int, array<string, mixed>>
     */
    public function afterToOptionArray(object $subject, array $result): array
    {
        if ($this->request->getParam('section') !== 'recaptcha_backend') {
            return $result;
        }

        return array_values(array_filter($result, static fn ($option) => ($option['value'] ?? null) !== 'hostily'));
    }
}
