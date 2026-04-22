<?php

/**
 * @var $libersign_applet_url
 * @var $libersign_help_url
 * @var  $libersign_extension_update_url
 */

// phpcs:disable Generic.Files.LineLength.MaxExceeded

use S2lowLegacy\Class\Helpers;

?>
<script type="text/javascript" src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jquery.js");?>"></script>
<script type="text/javascript" src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jqueryui.js");?>"></script>

<script>

    /**
     This file is part of IPARAPHEUR-WEB.

     Copyright (c) 2012-2016, ADULLACT-Projet
     Initiated by ADULLACT-Projet S.A.
     Developped by ADULLACT-Projet S.A.

     contact@adullact-projet.coop

     IPARAPHEUR-WEB is free software: you can redistribute it and/or modify
     it under the terms of the GNU Affero General Public License as published by
     the Free Software Foundation, either version 3 of the License, or
     (at your option) any later version.

     IPARAPHEUR-WEB is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU Affero General Public License for more details.

     You should have received a copy of the GNU Affero General Public License
     along with IPARAPHEUR-WEB.  If not, see <http://www.gnu.org/licenses/>.
     */

    ;(function ($) {

        var css = '.libersign-loader {font-size: 10px;margin: 10px auto;text-indent: -9999em;width: 9em;height: 9em;border-radius: 50%;background: #000;background: -moz-linear-gradient(left, #000 10%, rgba(255, 255, 255, 0) 42%);background: -webkit-linear-gradient(left, #000 10%, rgba(255, 255, 255, 0) 42%);background: -o-linear-gradient(left, #000 10%, rgba(255, 255, 255, 0) 42%);background: -ms-linear-gradient(left, #000 10%, rgba(255, 255, 255, 0) 42%);background: linear-gradient(to right, #000 10%, rgba(255, 255, 255, 0) 42%);position: relative;-webkit-animation: load3 1.4s infinite linear;animation: load3 1.4s infinite linear;-webkit-transform: translateZ(0);-ms-transform: translateZ(0);transform: translateZ(0)}.libersign-loader:before {width: 50%;height: 50%;background: #000;border-radius: 100% 0 0 0;position: absolute;top: 0;left: 0;content: \'\'}.libersign-loader:after {background: #fff;width: 75%;height: 75%;border-radius: 50%;content: \'\';margin: auto;position: absolute;top: 0;left: 0;bottom: 0;right: 0}@-webkit-keyframes load3 {0% {    -webkit-transform: rotate(0deg);    transform: rotate(0deg)}100% {    -webkit-transform: rotate(360deg);    transform: rotate(360deg)}}@keyframes load3 {0% {    -webkit-transform: rotate(0deg);    transform: rotate(0deg)}100% {    -webkit-transform: rotate(360deg);    transform: rotate(360deg)}}#libersign-cert-select table {margin-bottom: 10px}#libersign-cert-select table thead tr th {padding: 1px}#libersign-cert-select table tbody {text-align: left;padding-left: 10px;cursor: pointer;background-color: rgba(83, 91, 133, .1)}#libersign-cert-select table tbody tr td {padding: 1px !important}#libersign-cert-select table tbody tr.active-line {background-color: rgba(119, 219, 123, .5)}#libersign-cert-select table tbody tr:not(.active-line):hover, #libersign-cert-select table tbody tr:not(.active-line):focus {background-color: rgba(141, 192, 219, .18)}',
            icons = {
                'fa': {
                    warn: 'fa-warning',
                    star: 'fa-star',
                    emptyStar: 'fa-star-o'
                },
                'glyphicon': {
                    warn: 'glyphicon-warning-sign',
                    star: 'glyphicon-star',
                    emptyStar: 'glyphicon-star-empty'
                }
            },
            comment = "",
            certsList = [],
            selectedCert,
            config = {
                appletUrl: '<?php echo $libersign_applet_url ?>',
                extensionUpdateUrl: '<?php echo $libersign_extension_update_url ?>',
                height: 140,
                width: '100%',
                iconType: 'fa',
                installRedirect: '<?php echo $libersign_help_url ?>'
            },
            // Opera 8.0+
            isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0,
            // Firefox 1.0+
            isFirefox = typeof InstallTrigger !== 'undefined',
            // At least Safari 3+: "[object HTMLElementConstructor]"
            isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0,
            // Internet Explorer 6-11
            isIE = /*@cc_on!@*/false || !!document.documentMode,
            // Edge 20+
            isEdge = !isIE && !!window.StyleMedia,
            // Chrome 1+
            isChrome = !!window.chrome && !!window.chrome.webstore,
            // Blink engine detection
            isBlink = (isChrome || isOpera) && !!window.CSS,
            // General 'this' object
            that;

        var handleApplet = function () {
            var that = $(this);

            // Ok, so... let's do it ! First, let's define window function et launch events !
            window.cancelSignature = function () {
                that.trigger('libersign.cancel');
            };

            window.injectSignature = function () {
                var signatures = [];
                // Get signatures;
                for (var i = 1; i <= config.signatureInformations.length; i++) {
                    var sign = that.find('applet')[0].returnSignature("hash_" + i);
                    if (sign !== "hash_" + i) {
                        signatures.push(sign);
                    }
                }
                that.trigger('libersign.sign', [signatures]);
            };

            window.appletIsLoaded = function () {
                that.find(".libersign-loader").hide();
                that.find('#libersign-applet-load').hide();
                that.find('applet').attr('height', config.height);
                that.trigger('libersign.loaded');
            };

            window.giveCommentToApplet = function () {
                that.find('applet')[0].giveCommentToApplet(comment);
            };

            // Finally, create the Applet HTML !
            var applet = '<applet codebase="' + config.appletUrl + '" ' +
                'code="org/adullact/parapheur/applets/splittedsign/Main.class" ' +
                'archive="SplittedSignatureApplet.jar" ' +
                'name="Signature i-Parapheur" width="' + config.width + '" height="0">' +
                '<param name="permissions" value="all-permissions"/>' +
                '<param name="codebase_lookup" value="false" />' +
                '<param name="display_cancel" value="false" />' +
                '<param name="cancel_mode" value="javascript"/>' +
                '<param name="hash_count" value="' + config.signatureInformations.length + '"/>';

            for (var i = 0; i < config.signatureInformations.length; i++) {
                /** @namespace config.signatureInformations */
                applet += '<param name="hash_' + (i + 1) + '" value="' + config.signatureInformations[i].hash + '">';
                applet += '<param name="pesid_' + (i + 1) + '" value="' + config.signatureInformations[i].pesid + '">';
                applet += '<param name="pespolicyid_' + (i + 1) + '" value="' + config.signatureInformations[i].pespolicyid + '">';
                applet += '<param name="pespolicydesc_' + (i + 1) + '" value="' + config.signatureInformations[i].pespolicydesc + '">';
                applet += '<param name="pespolicyhash_' + (i + 1) + '" value="' + config.signatureInformations[i].pespolicyhash + '">';
                applet += '<param name="pesspuri_' + (i + 1) + '" value="' + config.signatureInformations[i].pesspuri + '">';
                applet += '<param name="pescity_' + (i + 1) + '" value="' + config.signatureInformations[i].pescity + '">';
                applet += '<param name="pespostalcode_' + (i + 1) + '" value="' + config.signatureInformations[i].pespostalcode + '">';
                applet += '<param name="pescountryname_' + (i + 1) + '" value="' + config.signatureInformations[i].pescountryname + '">';
                applet += '<param name="pesclaimedrole_' + (i + 1) + '" value="' + config.signatureInformations[i].pesclaimedrole + '">';
                applet += '<param name="p7s_' + (i + 1) + '" value="' + config.signatureInformations[i].p7s + '">';
                applet += '<param name="pesencoding_' + (i + 1) + '" value="' + config.signatureInformations[i].pesencoding + '">';
                applet += '<param name="format_' + (i + 1) + '" value="' + config.signatureInformations[i].format + '">';
            }

            applet += '<param name="return_mode" value="form" />' +
                'Vous devez activer Java 1.7 minimum pour pouvoir signer des documents.' +
                '</applet>';

            that.append('<div class="libersign-loader"></div>' +
                '<span id="libersign-applet-load" class="text-info">Chargement de l\'applet de signature en cours...</span>');

            return applet;
        };

        var handleSignature = function () {
            var that = $(this);
            that.css('text-align', 'center');
            that.find('#libersign-cert-select').hide();
            that.find(".libersign-loader").show();

            that.find("#libersign-signing").text("Signature en cours...");
            that.find("#libersign-signing").show();

            var handleMessage = function (ev) {
                var message = ev.detail.progress;
                that.find("#libersign-signing").text(message);
            };

            window.addEventListener('libersignmessage', handleMessage);

            LiberSign.sign(selectedCert, config.signatureInformations).then(function (signatures) {
                that.trigger('libersign.sign', [signatures]);
                that.find("#libersign-signing").hide();
                that.find("#libersign-signed").show();
                window.removeEventListener('libersignmessage', handleMessage);
            }).catch(function (error) {
                that.find(".libersign-loader").hide();
                that.find("#libersign-signing").hide();
                that.find('#libersign-error').show();
                that.find('#libersign-message').text(error.exception);
                window.removeEventListener('libersignmessage', handleMessage);
            });
        };

        var handleLoadedCerts = function () {
            that = this;
            $(this).children().hide();
            $(this).css('text-align', 'left');

            // Prepare favorite
            var favID = localStorage.getItem("libersign-fav");
            var favCertIndex = -1;

            var certSelector = '<div id="libersign-cert-select">';
            if (certsList.length > 1) {
                certSelector += '<span class="text-info">Sélectionnez un certificat de signature :</span>';
            }

            if (certsList.length === 0) {
                certSelector += '<span class="text-danger"><i class="'+ config.iconType + ' ' + icons[config.iconType].warn + '"></i> Aucun certificat de signature détecté</span>';
            } else {
                certSelector += '<table class="table"><thead> <tr> <th></th> <th></th> <th>Nom</th> <th>Émetteur</th> <th>Date d\'expiration</th></tr></thead><tbody class="certificateList">';

                certsList.forEach(function (element, index) {
                    certSelector += constructCertificateRow(element);
                    if (favID === element.ID) {
                        favCertIndex = index;
                    }
                });

                certSelector += '</tbody></table>';
            }

            certSelector += '<span style="float:right;">';
            certSelector += '<button class="btn btn-primary" id="libersign-confirm" disabled>Confirmer</button></span>';
            certSelector += '<span style="display:none;" class="libersign-nofav text-warning"><i class="'+ config.iconType + ' ' + icons[config.iconType].warn + '"></i> Certificat favori introuvable</span></div>';

            $(this).append(certSelector);

            $(this).find("tbody > tr").click(clickOnRow);
            $(this).find("tbody > tr > .libersign-fav").click(clickOnFav);
            $(this).find('#libersign-cancel').click(clickCancel);
            $(this).find('#libersign-confirm').click(clickConfirm);
            $(this).find('#libersign-error-confirm').click(clickErrorMsg);

            $(this).find('.libersign-fav > span').tooltip();

            // Handle special cases
            if (favCertIndex !== -1) {
                var certLine = $(this).find("tbody > tr").get(favCertIndex);
                $(certLine).trigger("click");
                var favLine = $(this).find("tbody > tr > .libersign-fav").get(favCertIndex);
                $(favLine).trigger("click");
            } else if (favID && certsList.length > 0) {
                $(this).find(".libersign-nofav").show();
            }
            if (certsList.length === 1) {
                // And select the certificate
                $(this).find("tbody > tr").trigger("click");
            }
        };

        var constructCertificateRow = function (cert) {
            var certSelector = '';
            var date = new Date(cert.NOTAFTER);

            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();

            certSelector += '<tr><td class="libersign-fav"><span title="Sélectionner en favori" class="'+ config.iconType + ' ' + icons[config.iconType].emptyStar + '"></span></td>';
            certSelector += '<td style="text-align: center">';
            if (cert.verifiedWith.indexOf('RGS') != -1) {
                certSelector += '<span class="libersign-tooltip label label-success">RGS</span>'
            } else if (cert.verifiedWith.indexOf('ADULLACT') != -1) {
                certSelector += '<span class="libersign-tooltip label label-info">Adullact</span>'
            }
            certSelector += '</td><td>' + cert.CN + '</td>';
            certSelector += '<td>' + cert.ISSUERDN + '</td>';
            certSelector += '<td>' + day + '/' + month + '/' + year + '</td>';
            certSelector += '</tr>';

            return certSelector;
        };

        var clickOnRow = function () {
            $(that).find("tbody > tr").removeClass("active-line");
            $(that).find("#libersign-confirm").prop('disabled', false);
            $(this).addClass("active-line");
            selectedCert = certsList[$(this).index()];
        };

        var clickOnFav = function () {
            // '+ config.iconType + ' ' + icons[config.iconType].warn + '
            $(that).find("tbody > tr > .libersign-fav > span").removeClass("text-warning").removeClass(icons[config.iconType].star).addClass(icons[config.iconType].emptyStar).attr("data-original-title", "Sélectionner en favori");
            $(this).find("span").removeClass(icons[config.iconType].emptyStar).addClass("text-warning").addClass(icons[config.iconType].star).attr("data-original-title", "Certificat favori");

            localStorage.setItem("libersign-fav", certsList[$(this).parent().index()].ID);
        };

        var clickCancel = function () {
            $(that).trigger('libersign.cancel');
        };

        var clickConfirm = function () {
            handleSignature.apply(that);
        };

        var clickErrorMsg = function () {
            $(that).css('text-align', 'left');
            $(that).find('#libersign-error').hide();
            $(that).find('#libersign-cert-select').show();
        };

        var handleError = function () {
            var that = this;

            $(that).trigger('libersign.loaded');

            var htmlError = '<div class="text-danger"><i class="'+ config.iconType + ' ' + icons[config.iconType].warn + '"></i> Chargement de LiberSign impossible. Merci de vérifier l\'installation.</div>';

            if (config.installRedirect) {
                htmlError += '<a onclick="$(this).trigger(\'libersign.cancel\');" href="' + config.installRedirect + '" >Aide d\'installation</a>';
            }

            return htmlError;
        };

        var handleExtension = function () {
            var that = this;

            LiberSign.setUpdateUrl(config.extensionUpdateUrl);
            LiberSign.getCertificates().then(function (certs) {
                certsList = certs;
                handleLoadedCerts.apply(that);
            }).catch(function (error) {
                console.log(error);
                $(that).children().hide();
                $('#libersign-no-impl').show();
            });

            setTimeout(function () {
                $(that).trigger('libersign.loaded');
            }, 0);

            return '<div  class="libersign-loader"></div>' +
                '<span id="libersign-cert-load" class="text-info">Chargement des certificats disponibles en cours...</span>' +
                '<span id="libersign-signing" style="display:none;" class="text-info">Signature en cours...</span>' +
                '<span id="libersign-signed" style="display:none;" class="text-success">Document(s) signé(s). Envoi des informations de signature au serveur...</span>' +
                '<span id="libersign-error" style="display:none;" class="text-danger"><i class="'+ config.iconType + ' ' + icons[config.iconType].warn + '"></i> Erreur lors de la signature.<br/><span id="libersign-message"></span><br/><button id="libersign-error-confirm" class="btn btn-default">Retour</button></span>' +
                '<span id="libersign-no-impl" style="display:none;" class="text-danger"><i class="'+ config.iconType + ' ' + icons[config.iconType].warn + '"></i> Installation de LiberSign incomplète. Merci d\'installer l\'application cliente</span>';
        };

        var methods = {
            init: function () {
                if (arguments[0] && typeof(arguments[0]) == 'object') {
                    config = $.extend({}, config, arguments[0]);
                }

                this.filter("div").each(function () {
                    var block = $(this);

                    // Create style element
                    var style = document.createElement('style');
                    style.type = 'text/css';
                    style.innerHTML = css;
                    this.appendChild(style);
                    block.css('text-align', 'center');

                    if (typeof LiberSign === 'object') {
                        block.append(handleExtension.apply(this));
                    } else {
                        if (isOpera || isChrome || !navigator.javaEnabled()) {
                            console.log("in file type");
                            block.append(handleError.apply(this));
                        } else {
                            // We do this because of IE
                            var divApplet = document.createElement('div');
                            divApplet.innerHTML = handleApplet.apply(this);
                            this.appendChild(divApplet);
                        }
                    }
                });
                return this;
            },
            comment: function () {
                if (arguments.length === 0) {
                    return new Error("libersign:comment function require 1 argument");
                }
                comment = arguments[0];
                return this;
            },
            destroy: function () {
                // We do this because of IE
                // http://concord-consortium.github.io/applet-tests/removal-of-tag/
                this.get(0).innerHTML = "";
            }
        };

        $.fn.libersign = function (methodOrOptions) {
            if (methods[methodOrOptions]) {
                return methods[methodOrOptions].apply(this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
                // Default to "init"
                return methods.init.apply(this, [methodOrOptions]);
            } else {
                $.error('Method ' + methodOrOptions + ' does not exist on jQuery.libersign');
            }
        };
    })(jQuery);

</script>
