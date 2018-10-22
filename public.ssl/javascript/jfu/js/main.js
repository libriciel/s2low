

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
	//	'use strict';
	$('#fileupload').fileupload({
//		forceIframeTransport: true,
		add: function (e, data) {
			$.blueimpUI.fileupload.prototype.options.add.call(this, e, data);
			$(this).find('.template-upload').each(function() {
				var first = $(this).parent().children().first();
				var firstData;
				firstData = first.data('data');
				if (this !== first[0]) {
					var data;
					data = $(this).data('data');
					if (data.files.length > 0){
						firstData.context = firstData.context.add(data.context);
						firstData.fileInput = firstData.fileInput.add(data.fileInput[0]);
						firstData.files.push(data.files[0]);
						$(this).data('data', {
							files: []
						});
					}

					firstData.isValidated = $('#fileupload').data('fileupload')._validate(firstData.files);
					first.data('data', firstData);
				}
			});
			showHideSubmit();
			cleanFiles();
		},
		submit: function(e, data){
			var retval = false;
			var verif = {
				'intitule': true,
				'prefix': true,
				'noErrorFiles': true
			};

			if ($('#jfu_intitule').val() == ''){
				verif.intitule = false;
			}
			if ($('#jfu_num_prefix').val() == ''){
				verif.prefix = false;
			}

			if ($('.template-upload td.error', this).length > 0){
				verif.noErrorFiles = false;
			}

			if (verif.intitule && verif.prefix && verif.noErrorFiles){
				retval = true;
			} else {
				var msg = '';
				if (!verif.intitule){
					msg += 'Le champ "Intitulé du lot" est vide.';
				}
				if (!verif.prefix){
					msg += '\nLe champ "Préfixe des numéros internes" est vide.';
				}
				if (!verif.noErrorFiles){
					msg += '\nDes fichiers ne sont pas valides. Veuillez les supprimer de la liste avant l\'envoi.';
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

			msg += '<br /><br /><a class="btn btn-primary lot-action" href="/modules/actes/actes_batch_add.php">Cr&eacute;er un lot</a>';
			if (id > 0){
				msg += '<a class="btn btn-primary lot-action" href="/modules/actes/actes_batch_show.php?id=' + id + '">Traiter le lot</a>';
			}
			msg += '<a class="btn btn-primary lot-action" href="/modules/actes/actes_batch_handle.php">Terminer</a><br />';

			var resultUpload = $('<div></div>').html(msg).attr('id', 'resultUpload').css('text-align', 'center').attr('class','alert alert-success');
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
		acceptFileTypes: /(\.|\/)(pdf)$/i,
		maxFileSize: s2lowMaxFileSize
	});
});



function manualDeleteLine(element){
	var domElementToRemove = $(element).parents('.template-upload');
	var domElementToRemoveFileName = $('td.name span', domElementToRemove).html();
	var container = domElementToRemove.parent();
	var first = $('.template-upload:first', container);
	var firstFileName = $('td.name span', first).html();
	var firstData = first.data('data');

	for (var i in firstData.files){
		if (typeof(firstData.files[i]) == 'object'){
			if (firstData.files[i].name == domElementToRemoveFileName){
				firstData.files.splice(i, 1);
			}
		}
	}

	for (var i in firstData.fileInput){
		if (typeof(firstData.fileInput[i]) == 'object'){
			if (i != 'context' && i != 'prevObject'){
				var files = firstData.fileInput[i].files;
				if (typeof(files) !== "undefined"){
					if (firstData.fileInput[i].files[0].name == domElementToRemoveFileName){
						firstData.fileInput.splice(i, 1);
					}
				}else {
					var str = firstData.fileInput[i].value;
					if (str.search(domElementToRemoveFileName) != -1){
						firstData.fileInput.splice(i, 1);
					}
				}
			}
		}
	}

	if (domElementToRemoveFileName != firstFileName){
		domElementToRemove.remove();
	} else {
		var fakeData = $('#fakeData');
		if (fakeData.length == 0){
			fakeData = $('<div></div>').css('display', 'none').attr('id', 'fakeData');
			fakeData.data('data', firstData);
			$('body').append(fakeData);
		}
		domElementToRemove.remove();
		firstData = fakeData.data('data');
		first = $('.template-upload:first', container);
	}
	firstData.isValidated = $('#fileupload').data('fileupload')._validate(firstData.files);
	first.data('data', firstData);
	showHideSubmit();
	cleanFiles();
}


function cleanFiles(params){
	var defaults = {
		log: false
	};
	$.extend(defaults, params);
	var first = $('#fileupload tbody.files .template-upload:first');
	var firstFilename = $('td.name', first).html();
	var firstData = first.data('data');
	var cpt = 0;

	$('#fileupload tbody.files .template-upload').each(function(){
		var msg = cpt + " :";
		var filename = $('td.name', $(this)).html();
		if (filename != firstFilename){
			msg += " not first\n";
			var data = $(this).data('data');
			msg += 'files: \n';
			for (var i in data.files){
				if (typeof(data.files[i]) == "object"){
					msg += ' - ' + data.files[i].name;
					var fileIsPresent = false;
					for (var j in firstData.files){
						if (data.files[i].name == firstData.files[j].name){
							fileIsPresent = true;
						}
					}
					msg += " - is present : " + fileIsPresent + '\n';
					if (!fileIsPresent){
						firstData.files.push(data.files[i]);
						msg += '   pushed\n';
					}
				}
			}
			$(this).data('data', {
				files: []
			});
			msg += '\n data cleared \n\n';
		}
		cpt++;
		if (defaults.log){
			console.log(msg);
		}
	});
}

function verifStruct(){
	var cpt = 0;
	$('#fileupload tbody.files .template-upload').each(function(){
		var data = $(this).data('data');
		var msg = cpt + ': \nfiles: \n';
		for (var i in data.files){
			if (typeof(data.files[i]) == "object"){
				msg += ' - ' + data.files[i].name + '\n';
			}
		}
		console.log(msg);
		cpt++;
	});
}

function showHideSubmit(){
	if ($('.template-upload td.error').length > 0 || $('.template-upload').length == 0){
		$('.fileupload-buttonbar .start').css('display', 'none');
	} else {
		$('.fileupload-buttonbar .start').css('display', 'inline-block');
	}
}

function verifMultiUpload(){
	var selectMultipleFiles = false;
	if ( ($.browser.msie && parseFloat($.browser.version) >= 10) ||
		($.browser.mozilla && parseFloat($.browser.version) >= 3.6) ||
		($.browser.opera && parseFloat($.browser.version) >= 11) ||
		($.browser.chrome) ||
		(typeof($.browser.chrome) == 'undefined' && $.browser.webkit && parseFloat($.browser.version) >= 533.16)
		){
		selectMultipleFiles = true;
		$('.noMultipleSelect').remove();
	}
	return selectMultipleFiles;
}