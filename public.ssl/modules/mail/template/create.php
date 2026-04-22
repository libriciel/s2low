<script src="<?php use S2lowLegacy\Class\Helpers;

echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jquery.js"); ?>"></script>
<script src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jqueryui.js"); ?>"></script>
 <h1> Mail - Système de mail sécurisé</h1>
  <h2>Actions</h2>
    <div id="actions_area"> 
            <a class="btn btn-primary" href="index.php?command=list">Messages envoyés</a>
    </div>

  <h2 id="in_list">Nouveau message</h2>
    
  <script>
  $(document).ready(function(){

      function split( val ) {
          return val.split( /,\s*/ );
      }
      function extractLast( term ) {
          return split( term ).pop();
      }

      $( ".annuaire-autocomplete" )
          // don't navigate away from the field on tab when selecting an item
          .bind( "keydown", function( event ) {
              if ( event.keyCode === $.ui.keyCode.TAB &&
                  $( this ).autocomplete( "instance" ).menu.active ) {
                  event.preventDefault();
              }
          })
          .autocomplete({
              source: function( request, response ) {
                  $.getJSON( "<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/mail/liste-mail.php?");?>", {
                      term: extractLast( request.term )
                  }, response );

              },
              response: function( event, ui ) {
                    if (ui.content.length == 0) {
                        if (this.value.lastIndexOf(",") == -1) {
                            this.value = '';
                        } else {

                        this.value = this.value.substr(0, this.value.lastIndexOf(',') + 2);
                        }
                    }
              },

              focus: function() {
                  // prevent value inserted on focus
                  return false;
              },
              select: function( event, ui ) {
                  var terms = split( this.value );
                  // remove the current input
                  terms.pop();
                  // add the selected item
                  terms.push( ui.item.value );
                  // add placeholder to get the comma-and-space at the end
                  terms.push( "" );
                  this.value = terms.join( ", " );
                  return false;
              }
          });
  });
  </script>
  
    
        <form class="form-horizontal mail-form" action="index.php?command=send" method="post"  id="mailform" onSubmit="InsertFileNumber();return checkFormCreateMail();" enctype="multipart/form-data" autocomplete="off">
            <div class="form-group">
                <label class="col-md-2" for="mailto">À : </label>
                <div class="col-md-10">
                    <input name='mailto' id="mailto" class="form-control annuaire-autocomplete"/>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="mailcc">CC : </label>
                <div class="col-md-10">
                    <input name='mailcc' id="mailcc" class="form-control annuaire-autocomplete"/>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="mailcci">CCI : </label>
                <div class="col-md-10">
                    <input name='mailcci' id="mailcci" class="form-control annuaire-autocomplete"/>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="objet">Objet : </label>
                <div class="col-md-10">
                    <input name='objet' id="objet" class="form-control"/>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="message">Message : </label>
                <div class="col-md-10">
                    <textarea maxlength="2000" name="message" rows="8" cols="80" id="message" class="form-control"></textarea>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="psw1">Mot de passe </label>
                <div class="col-md-4">
                    <input type="password" name="psw1" id="psw1" class="form-control"/>
        </div>
                <label class="col-md-2" for="psw2">Confirmation du mot de passe</label>
                <div class="col-md-4">
                    <input type="password" name="psw2" id="psw2" class="form-control"/>
        </div>
            </div>
            <div class="form-group">
                <label class="col-md-5" for="send_password">
                    <input type='checkbox' name='send_password' id='send_password'/>Envoyer le mot de passe en clair
                </label>
            </div>
            <div class="form-group">
                <label class="col-md-2" for="file">Pièces jointes</label>
                <div class="col-md-4">
                    <input id="file" class="btn btn-success btn-sm" type="button" name="ajouter" value="Joindre un fichier" onclick="InsertNewFile();" />
        </div>
            </div>
            <div class="form-group">
                <input id="sendemail" type="submit" value="Envoyer" class="col-md-offset-4 col-md-4 btn btn-primary"/>
            </div>
        </form> 
