/* Mixin fuer Magento_ReCaptchaWebapiUi/js/webapiReCaptcha (Kasse: Bestellung aufgeben; die Loesung geht im Kopf X-ReCaptcha). */
define([
    'Magento_ReCaptchaWebapiUi/js/webapiReCaptchaRegistry'
], function (webapiRegistry) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /** @inheritdoc */
            initParentForm: function (parentForm, widget) {
                var self = this,
                    id = this.getReCaptchaId();

                if (!this.isHostily || !this.isHostily()) {
                    return this._super(parentForm, widget);
                }
                webapiRegistry._isInvisibleType[id] = !!this.getIsInvisibleRecaptcha();
                webapiRegistry.triggers[id] = function () {
                    var done = function (token) {
                        self.reCaptchaCallback(token);
                        // Ein Token gilt nur einmal: fuer einen weiteren Versuch (z. B. nach einem Fehler) neu loesen
                        if (widget && widget.reset) {
                            setTimeout(function () {
                                widget.reset();
                            }, 0);
                        }
                    };

                    if (!widget) {
                        // Widget nicht geladen: leer senden, der Server entscheidet ("Bei Ausfall")
                        done('');

                        return;
                    }
                    // Sichtbarer Modus: Magento entfernt den Empfaenger gleich nach dem Ausloesen (die Pruefbox hat das Widget schon
                    // geloest), das Token muss also sofort da sein. Unsichtbar: loesen, dann melden.
                    if (widget.token) {
                        done(widget.token);

                        return;
                    }
                    widget.loesen().then(done, function () {
                        done('');
                    });
                };
                if (this.autoTrigger) {
                    webapiRegistry.triggers[id]();
                    webapiRegistry.triggers[id] = function () {};
                }
                this.tokenField = null;
            }
        });
    };
});
