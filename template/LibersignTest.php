<?php

/** @var LibersignController $libersignController  */
?>
<h1>Test de Libersign</h1>

<?php $libersignController->displayLibersignJS() ?>


<script>
	$(window).load(function() {

		$(document).ready(function () {

			$("#box_result").hide();

			var siginfos = [];

			siginfos.push({
				hash: "cc78058a4d1967d4d0d26f5dcc4c8cd89defbb4e",
				format: "CMS"
			});

			$(".libersign").libersign({
				iconType: "glyphicon",
				signatureInformations: siginfos
			}).on('libersign.sign', function (event, signatures) {

				// Les signatures sont là
				console.log(signatures);
				$("#libersign_result").html("Signature : " + JSON.stringify(signatures));
				$("#box_signature").hide();
				$("#box_result").show();

			});

		});
	});
</script>

<div id='box_signature' class='box' style="width:920px" >
	<h2>Signature</h2>
	<div class="libersign"></div>
</div>

<div id='box_result' class='box' style="word-wrap: break-word; max-width: 920px;">
	<h2>Résultat de la signature</h2>
	<div id="libersign_result"></div>
</div>
