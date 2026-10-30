<?php

declare(strict_types=1);

namespace Hostily\Captcha\Model\Frontend;

use Hostily\Captcha\Model\Config;
use Hostily\Captcha\Model\ValidationConfig;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\ReCaptchaUi\Model\ValidationConfigProviderInterface;
use Magento\ReCaptchaValidationApi\Api\Data\ValidationConfigInterface;

class ValidationConfigProvider implements ValidationConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    public function get(): ValidationConfigInterface
    {
        return new ValidationConfig(
            $this->config->getSecret(),
            (string) $this->remoteAddress->getRemoteAddress(),
            $this->config->getServiceUrl(),
            $this->config->blocksOnError()
        );
    }
}
