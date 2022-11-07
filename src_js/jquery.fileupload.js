import jQuery from "jquery";
import $ from 'jquery';

window.$ = $;

require('blueimp-file-upload/js/vendor/jquery.ui.widget');
require('blueimp-tmpl/js/tmpl');

require('blueimp-load-image/js/load-image.all.min.js');     // The Load Image plugin is included for the preview images and image resizing functionality
require('blueimp-canvas-to-blob/js/canvas-to-blob.min.js'); //The Canvas to Blob plugin is included for image resizing functionality
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