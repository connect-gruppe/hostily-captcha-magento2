'use strict';

/* Erweitert die Komponenten von Magento_ReCaptcha*: Hat ein Formular den Typ "hostily", setzen sie
   <hostily-captcha> statt des Google-Widgets (alle anderen Typen laufen unveraendert durch). */
var config = {
    config: {
        mixins: {
            'Magento_ReCaptchaFrontendUi/js/reCaptcha': {
                'Hostily_Captcha/js/reCaptcha-mixin': true
            },
            'Magento_ReCaptchaWebapiUi/js/webapiReCaptcha': {
                'Hostily_Captcha/js/webapiReCaptcha-mixin': true
            },
            'Magento_Ui/js/view/messages': {
                'Hostily_Captcha/js/messages-mixin': true
            }
        }
    }
};
