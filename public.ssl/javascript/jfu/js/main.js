/*
 * jQuery File Upload Plugin JS Example 6.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */

$(function () {
	'use strict';

	$('#fileupload').fileupload({
		add: function (e, data) {
			$.blueimpUI.fileupload.prototype.options
			.add.call(this, e, data);
			$(this).find('.template-upload').each(function() {
				var first = $(this).parent().children().first(),
				firstData,
				data;
				if (this !== first[0]) {
					firstData = first.data('data');
					data = $(this).data('data')
					firstData.context = firstData.context.add(data.context);
					firstData.fileInput = firstData.fileInput.add(data.fileInput[0]);
					firstData.files.push(data.files[0]);
					$(this).data('data', firstData);
				}
			});
		},
		submit: function(e, data){
			var retval = false;
			var verif = {
				'intitule': true,
				'prefix': true
			};

			if ($('#jfu_intitule').val() == ''){
				verif.intitule = false;
			}
			if ($('#jfu_num_prefix').val() == ''){
				verif.prefix = false;
			}

			if (verif.intitule && verif.prefix){
				retval = true;
			} else {
				var msg = '';
				if (!verif.intitule){
					msg += 'Le champ "Intitulé du lot" est vide !';
				}
				if (!verif.prefix){
					msg += '\nLe champ "Préfixe des numéros internes" est vide !';
				}
				alert(msg);
			}
			return retval;
		},
		always: function(e, data){
			var obj = data.result;

			var msg = '';
			var lvl = '';
			var id = '';

			jQuery.each(obj, function(key, value) {
				jQuery.each(value, function(key2, value2) {
					if (key2 == 'id'){
						id = value2;
					}
					if (key2 == 'msg'){
						msg = value2;
					}
					if (key2 == 'elvl'){
						lvl = value2;
					}
				});
			});

			$('#resultUpload').remove();

			msg += '<br /><br /><a class="bouton" href="/modules/actes/actes_batch_add.php">Cr&eacute;er un lot</a>&nbsp;';
			if (id > 0){
				msg += '<a class="bouton" href="/modules/actes/actes_batch_show.php?id=' + id + '">Traiter le lot</a>&nbsp;';
			}
			msg += '<a class="bouton" href="/modules/actes/actes_batch_handle.php">Terminer</a>';

			var resultUpload = $('<div></div>').html(msg).attr('id', 'resultUpload').css('text-align', 'center');
			$('#fileupload').before(resultUpload);

			//on masque la liste des fichiers et on affiche une nouvelle liste (contournement du bug d affichage des fichiers envoyés si on en supprime un de la liste avant l envoi)

			if (lvl != 1){
				var labelfilelist = $('<span>Liste des fichiers envoyés : </span>');
				var filelist = $('<ul></ul>');
				jQuery.each(obj, function(key, value) {
					var isfile = false;
					jQuery.each(value, function(key2, value2) {
						if (key2 == 'name'){
							isfile = true
						}
					});
					if (isfile){
						filelist.append('<li>' + value.name + '</li>');
					}
				});
				var finalFilelist = $('<div></div>').append(labelfilelist).append(filelist);
			}

			$('.table-striped').hide().before(finalFilelist);


			//on masque les boutons d'envoi
			$('.fileupload-buttonbar').hide();
			//on desactive les champ input
			$('#jfu_intitule').attr('disabled', 'disabled');
			$('#jfu_num_prefix').attr('disabled', 'disabled');
		},
		'option': {
			acceptFileTypes: /(\.|\/)(pdf)$/i,
			process: [
			{
				action: 'load',
				maxFileSize: 100000000 // 100MB
			}
			]
		}
	});
});


//FIXME : trouver un moyen d'intégrer correctement la fonction pemettant la suppression des fichiers de la liste à envoyer
function manualDeleteLine(element){
	var selected = $('td.name span', $(element).parents('.template-upload')).html();
	for (var i in $(element).parents('.template-upload').data('data').files){
		if (typeof($(element).parents('.template-upload').data('data').files[i]) == 'object' && $(element).parents('.template-upload').data('data').files[i].name == selected){
			$(element).parents('.template-upload').data('data').files.splice(i, 1);
		}
	}
	$(element).parents('.template-upload').remove();
}