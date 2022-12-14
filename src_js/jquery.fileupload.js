import jQuery from "jquery";
import $ from 'jquery';

window.$ = $;

require('blueimp-file-upload/js/vendor/jquery.ui.widget');
require('blueimp-tmpl/js/tmpl');

require('blueimp-load-image/js/load-image');
require('blueimp-load-image/js/load-image-scale');
require('blueimp-load-image/js/load-image-orientation');
require('blueimp-load-image/js/load-image-meta');
require('blueimp-load-image/js/load-image-exif');
//require('blueimp-load-image/js/load-image.all.min.js');     // The Load Image plugin is included for the preview images and image resizing functionality

require('blueimp-canvas-to-blob/js/canvas-to-blob'); //The Canvas to Blob plugin is included for image resizing functionality
require('bootstrap/dist/js/bootstrap.js');
require('blueimp-gallery/js/blueimp-gallery.js');
require('blueimp-bootstrap-image-gallery/js/bootstrap-image-gallery.js');
require('blueimp-file-upload/js/jquery.iframe-transport.js');      //The Iframe Transport is required for browsers without support for XHR file upload
require('blueimp-file-upload/js/jquery.fileupload.js');            //The basic File Upload plugin
require('blueimp-file-upload/js/jquery.fileupload-process.js');    //The File Upload processing plugin
require('blueimp-file-upload/js/jquery.fileupload-image');         //The File Upload image preview & resize plugin
require('blueimp-file-upload/js/jquery.fileupload-audio');         //The File Upload audio preview plugin
require('blueimp-file-upload/js/jquery.fileupload-video');         //The File Upload video preview plugin
require('blueimp-file-upload/js/jquery.fileupload-validate');      //The File Upload validation plugin
require('blueimp-file-upload/js/jquery.fileupload-ui.js');         //The File Upload user interface plugin

window.locale = {
    "fileupload": {
        "errors": {
            "maxFileSize": "La taille maximum du fichier est dépassée.",
            "minFileSize": "Le taille minimum du fichier n'est pas atteinte.",
            "acceptFileTypes": "Type de fichier non autorisé",
            "maxNumberOfFiles": "Nombre maximum de fichiers dépassé",
            "uploadedBytes": "La taille des fichiers envoyés est dépassée.",
            "emptyResult": "Aucun retour."
        },
        "error": "Erreur",
        "start": "Envoyer",
        "cancel": "Annuler",
        "destroy": "Supprimer"
    }
};

$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        multipart: true,
        //singleFileUploads: false,
        acceptFileTypes: /(\.|\/)(pdf)$/i,
        submit: function (e, data) {
            // Si non, faire une demande de batch
            if($('#jfu_batch_id').val() != ''){   // Un requete a déja été faite
                //TODO : comment vérifier les fichiers pour éviter les non-pdf??
                return true;
            }
            if($('#jfu_batch_id').val() == ''){   // Aucune requete n'a été faite
                //TODO : comment vérifier les fichiers pour éviter les non-pdf??
                //TODO : bloquer le submit
                $.post('actes_batch_create.php',
                    {
                        description: $('#jfu_intitule').val(),
                        num_prefix: $('#jfu_num_prefix').val()
                    }, function (result) {
                        let batch_id = result["id"];
                        let link_to_batch = "actes_batch_show.php?id="+batch_id;
                        let text = "<a href ='"+link_to_batch+"' class=\"btn btn-primary\">Traiter le lot "+batch_id+"</a>";
                        $('#jfu_batch_id').val(result["id"]);
                        $('#jfu_batch_link').html(text);
                        // TODO : débloquer le submit
                        data.submit();
                    }
                );
                return false;
            }
        }
    });
});