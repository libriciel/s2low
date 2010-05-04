<script src="<?php echo WEBSITE_SSL ?>/javascript/jquery-1.2.6.min.js"></script>
<script src="<?php echo WEBSITE_SSL ?>/javascript/jquery.autocomplete.min.js"></script>    
<link rel="stylesheet" href="<?php echo WEBSITE_SSL ?>/custom/styles/jquery.autocomplete.css" type="text/css" />

<div id="content">
 <h1> Mail - Système de mail sécurisé</h1>
  <h2>Actions</h2>
	<div id="actions_area"> 
	 	<a href="index.php?command=list" class="bouton">Messages envoyés</a>
	</div>

  <h2 id="in_list">Nouveau message</h2>
	
  <script>
  $(document).ready(function(){
		$("#mailto").autocomplete("liste-mail.php",  {multiple: true, mustMatch: true, cacheLength:0, max: 20});
  });

  $(document).ready(function(){
		$("#mailbcc").autocomplete("liste-mail.php",  {multiple: true, mustMatch: true, cacheLength:0, max: 20});
});
  
  $(document).ready(function(){
		$("#mailcc").autocomplete("liste-mail.php",  {multiple: true, mustMatch: true, cacheLength:0, max: 20});
});
  
  </script>
  
	
<form action="index.php?command=send" method="post"  id="mailform" onSubmit="InsertFileNumber();return checkFormCreateMail();" enctype="multipart/form-data" >
	<table>
		<tr>
			<td class="td_mailAddress">À&nbsp;:</td>	
			<td class="td_mailDescription"> <input name='mailto' id="mailto"  size="100" /></td>
		</tr>
		<tr>
			<td class="td_mailAddress">CC&nbsp;:</td>
			<td class="td_mailDescription"><input name='mailcc' id="mailcc"  size="100"/></td>
		</tr>
		<tr>
			<td class="td_mailAddress">CCI&nbsp;:</td>	
			<td class="td_mailDescription">
				<input id="mailbcc" name='mailcci' size="100"/> 
			</td> 
		</tr>			
		<tr>
			<td class="td_mailAddress">Objet&nbsp;:	</td>
			<td class="td_mailDescription"><input type="text" name="objet" id="objet" size="100"/></td>
		</tr>
		<tr>
			<td class="td_mailAddress">Message&nbsp;:</td>
			<td class="td_mailDescription"><textarea name="message" rows="8" cols="80" id="message"></textarea></td>
		</tr>
	</table>
	<br />
	<table>
		<tbody>
			<tr>
			  <th class="data">Mot de passe</th>
			  <th class="data">Confirmation du mot de passe</th>
			</tr>
			<tr class="">
			  <td class="td-input"><center><input type="password" name="psw1" id="psw1"/></center></td>
			  <td class="td-input"><center><input type="password" name="psw2" id="psw2"/></center></td>
			</tr>
			<tr>
			<td colspan='2'>
			<input type='checkbox' name='send_password' id='send_password' />Envoyer le mot de passe en clair
			</td>
			</tr>
		</tbody>
	</table>
	<h3>Pièces jointes&nbsp;:</h3>
	<div id="file">
	<input class="submit_button" type="button" name="ajouter" value="Joindre un fichier" onclick="InsertNewFile();" />
	</div>
	<center><input class="submit_button" type="submit" value="Envoyer" /></center>
</form> 