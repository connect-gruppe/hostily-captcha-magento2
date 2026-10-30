/* Mixin fuer Magento_ReCaptchaFrontendUi/js/reCaptcha (Kontakt, Kundenkonto, Anmeldung, Newsletter, Bewertungen ...). */
define([
    'jquery',
    'Hostily_Captcha/js/loader',
    'Hostily_Captcha/js/registry'
], function ($, loader, registry) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /**
             * @return {Boolean} Gehoert diese Komponente zu einem Formular mit dem Typ "hostily"?
             */
            isHostily: function () {
                return !!(this.settings && this.settings.hostily);
            },

            /** @inheritdoc */
            _loadApi: function () {
                if (this.isHostily()) {
                    return;
                }

                return this._super();
            },

            /** @inheritdoc */
            renderReCaptcha: function () {
                if (!this.isHostily()) {
                    return this._super();
                }
                loader.load(this.settings.hostily.script).then(function () {
                    this.initCaptcha();
                }.bind(this)).catch(function (e) {
                    // Der Dienst ist nicht erreichbar: das Formular bleibt bedienbar, der Server entscheidet ("Bei Ausfall")
                    if (window.console) {
                        window.console.warn(e.message);
                    }
                    this.initParentForm($('#' + this.getReCaptchaId() + '-wrapper').parents('form'), null);
                }.bind(this));
            },

            /** @inheritdoc */
            initCaptcha: function () {
                var $wrapper, $holder, widget, cfg;

                if (!this.isHostily()) {
                    return this._super();
                }
                if (this.captchaInitialized) {
                    return;
                }
                this.captchaInitialized = true;
                cfg = this.settings.hostily;
                $wrapper = $('#' + this.getReCaptchaId() + '-wrapper');
                $holder = $wrapper.find('.g-recaptcha');
                $holder.attr('id', this.getReCaptchaId());

                widget = document.createElement('hostily-captcha');
                widget.setAttribute('data-site-key', this.settings.rendering.sitekey);
                widget.setAttribute('data-api', cfg.api);
                widget.setAttribute('data-modus', cfg.mode);
                if (cfg.language) {
                    widget.setAttribute('data-sprache', cfg.language);
                }
                // Die Pruefbox (bei sichtbarem Modus) wird erst durch das geloeste Widget angehakt
                widget.addEventListener('geloest', function () {
                    this.validateReCaptcha(true);
                }.bind(this));
                widget.addEventListener('zurueckgesetzt', function () {
                    this.validateReCaptcha(false);
                }.bind(this));
                widget.addEventListener('fehler', function () {
                    this.validateReCaptcha(false);
                }.bind(this));
                $holder.append(widget);

                this.hostilyWidget = widget;
                registry.elements.push(widget);
                this.initParentForm($wrapper.parents('form'), widget);
            },

            /** @inheritdoc */
            initParentForm: function (parentForm, widget) {
                var submitButton;

                if (!this.isHostily()) {
                    return this._super(parentForm, widget);
                }
                // Das Widget legt sein Feld "hostily-captcha-token" selbst im Formular an und haelt im
                // unsichtbaren Modus das Absenden an, bis es geloest ist.
                this.tokenField = null;
                submitButton = parentForm.find('button:not([type]), [type=submit]');
                if (submitButton.length) {
                    submitButton.prop('disabled', false);
                }
            },

            /** @inheritdoc */
            validateReCaptcha: function (state) {
                var $wrapper;

                if (!this.isHostily()) {
                    return this._super(state);
                }
                if (!this.getIsInvisibleRecaptcha()) {
                    $wrapper = $('#' + this.getReCaptchaId() + '-wrapper');

                    return $wrapper.find('input[type=checkbox].required-captcha').prop('checked', state);
                }
            }
        });
    };
});
