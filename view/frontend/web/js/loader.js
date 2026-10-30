/* Laedt das Widget-Skript des Dienstes einmal je Seite; das Versprechen erfuellt sich, sobald <hostily-captcha> bekannt ist. */
define([], function () {
    'use strict';

    var promises = {};

    return {
        /**
         * @param {String} src Adresse von hostily-captcha.js
         * @return {Promise}
         */
        load: function (src) {
            var script;

            if (!promises[src]) {
                promises[src] = new Promise(function (resolve, reject) {
                    if (window.customElements && window.customElements.get('hostily-captcha')) {
                        resolve();

                        return;
                    }
                    script = document.createElement('script');
                    script.async = true;
                    script.src = src;
                    script.onload = function () {
                        window.customElements.whenDefined('hostily-captcha').then(resolve);
                    };
                    script.onerror = function () {
                        reject(new Error('hostily Captcha: widget script not loaded'));
                    };
                    document.head.appendChild(script);
                });
            }

            return promises[src];
        }
    };
});
