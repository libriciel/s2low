 /**
   *\brief  java scripte pour créer dynamiquement les upload bar.
   *\param FileNumber: le nombre total de upload fichier.
   *
   */
  var FileNumber=1;
  function InsertNewFile()  
  {    
    var divElement  = document.createElement("div"); 
    divElement.innerHTML="<input type='button' value='Delete'  name='Delete"+FileNumber+"' id='delete"+FileNumber+"' onClick='javascript:DeleteFile("+FileNumber+")'>"; 
    divElement.innerHTML+="<input type='file'  name='files["+FileNumber+"]' id='file"+FileNumber+"'>";
    document.getElementById("file").appendChild(divElement);
    FileNumber++;
  }
  function DeleteFile(id)
  {
    var trnode=document.getElementById("file"+id);
    trnode.parentNode.removeChild(trnode);    
    trnode=document.getElementById("delete"+id);
    trnode.parentNode.removeChild(trnode); 
  }