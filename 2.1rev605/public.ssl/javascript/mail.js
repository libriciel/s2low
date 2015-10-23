function checkFormCreateMail() {
	var returnvalue=true;
	if (checkpsw()==false)
	{
		alert("les 2 mots de passes ne sont pas indentiques.");
		returnvalue=false;
	}
	var email=document.getElementById("mailto");
	var str=email.value;
	if (str==null || str=="")
	{
		alert("L'adresse mail (A) est obligatoire");
		returnvalue=false;
	}

	var email=document.getElementById("mailcc");
	var strcc=email.value;
	var email=document.getElementById("mailcci");
	var strbcc=email.value;	
	var subject=document.getElementById("objet");
	var message=document.getElementById("message");
	if (subject.value=="" )
	{
		alert("L'objet du mail est obligatoire");
		returnvalue=false;
	}
	if (message.value=="" || message.value==null)
	{
		alert("Le message ne peut pas être vide");
		returnvalue=false;
	}
	return returnvalue;
}
	
	function checkpsw()
	{
		 var returnval;
		 pswone=document.getElementById("psw1");
		 pswtwo=document.getElementById("psw2");
		  if ( pswone.value == pswtwo.value) 
		     returnval = true;
		  else
		     {
		     pswone.value="";
		     pswtwo.value="";
		     returnval = false;
		     }
		
		return returnval;
	}

	function checkAllEmail(str)
	{

		var mailArray=str.split(",");
		
		var returnValue=true;
		for (i=0;i<mailArray.length;i++)
		{
			str = mailArray[i];
			str=str.replace(" ","");
			str = str.replace("[","<");
			str = str.replace("]",">");
			if (str!="")
			{
				if (checkEmail(str)==false)
					returnValue=false;
			}
		}
		return returnValue;
	}

	function checkEmail(str) {

		var at="@"
		var dot="."
		var lat=str.indexOf(at)
		var lstr=str.length
		var ldot=str.indexOf(dot)
		if (str.indexOf(at)==-1){
		   return false
		}

		if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
		   return false
		}

		if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
		    return false
		}

		 if (str.indexOf(at,(lat+1))!=-1){
		    return false
		 }

		 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
		    return false
		 }

		 if (str.indexOf(dot,(lat+2))==-1){
		    return false
		 }
		
		 if (str.indexOf(" ")!=-1){
		    return false
		 }

 		 return true					
	}
	
  /**
   *\brief  java scripte pour créer dynamiquement les upload bar.
   *\param FileNumber: le nombre total de upload fichier.
   *
   */
  var FileNumber=1;
  function InsertNewFile()  
  {  
   
    var divElement  = document.createElement("div"); 
    divElement.className= "form-group";
    divElement.innerHTML="<label class='form-label col-md-2' for='file"+FileNumber+"'>Fichier"+FileNumber+"</label>"; 
    divElement.innerHTML+="<input type='file' class='col-md-4' name='uploadFile"+FileNumber+"' id='file"+FileNumber+"'>";
    divElement.innerHTML+="<input class='btn btn-warning col-md-1 col-md-offset-1 btn-sm' type='button' value='Supprimer'  name='Delete"+FileNumber+"' id='delete"+FileNumber+"' onClick='javascript:DeleteFile("+FileNumber+")'>"; 
    var parentElement = document.getElementById("file").parentNode.parentNode;
    /*on commente pour être compatible avec IE > 10
     * console.log(parentElement);
    console.log(parentElement.parentNode);  
    console.log(parentElement.nextSibling);*/
    parentElement.parentNode.insertBefore(divElement, parentElement.nextSibling);
    FileNumber++;
  }
  
  function InsertFileNumber()
  {
        var Input= document.createElement("input");
        Input.type= "hidden";
        Input.name="FileNumber";
        Input.value=FileNumber;
        document.getElementById("file").appendChild(Input);
  }
  
  function getFileNumber()
  {
    return FileNumber;
  }
  
  function DeleteFile(id)
  {
    var trnode=document.getElementById("file"+id);
    trnode.parentNode.remove();    
//    trnode=document.getElementById("delete"+id);
//    trnode.parentNode.removeChild(trnode); 
  }
