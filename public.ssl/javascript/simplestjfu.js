/*
 * jQuery File Upload Demo
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/* global $ */

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
                  let text = "<p>Traiter le batch <a href ='"+link_to_batch+"'>"+batch_id+"</a></p>";
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