/* Wie die Mixin von Magento_ReCaptchaFrontendUi: nach einer Fehlermeldung (z. B. bei der Bestellung) braucht jedes Widget ein neues Token. */
define([
    'Hostily_Captcha/js/registry'
], function (registry) {
    'use strict';

    return function (originalComponent) {
        return originalComponent.extend({
            /** @inheritdoc */
            initialize: function () {
                this._super();
                this.messageContainer.errorMessages.subscribe(function () {
                    registry.elements.forEach(function (el) {
                        if (el.isConnected && el.reset) {
                            el.reset();
                        }
                    });
                }, null, 'arrayChange');

                return this;
            }
        });
    };
});
