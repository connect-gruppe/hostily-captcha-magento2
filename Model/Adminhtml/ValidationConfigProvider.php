<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model\Adminhtml;

use Hostily\Captcha\Model\ValidationConfig;
use Magento\ReCaptchaUi\Model\ValidationConfigProviderInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

/**
 * Siehe UiConfigProvider: leerer Schluessel = Captcha im Admin-Bereich aus.
 */
class ValidationConfigProvider implements ValidationConfigProviderInterface
{
    public function get(): ValidationConfigInterface
    {
        return new ValidationConfig('', '', '', false);
    }
}
