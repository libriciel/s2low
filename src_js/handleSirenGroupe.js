window.onload = (event) => {
    var elements = window.document.getElementsByName('authority_group_id');
    if(elements.length != 1){
        console.log("Plusieurs éléments authority_group_id présents !");
        return;
    }
    elements[0].addEventListener('change', groupchange);
};

function groupchange()  {
    var groupId=window.document.getElementsByName("authority_group_id")[0];
    var sirenSelect=window.document.getElementById("sirenId");
    var originalSiren = window.document.getElementById("originalSiren").value;

    for (var i=0;i< window.groupIdArray.length;i++)
    {
        if (window.groupIdArray[i]==groupId.value)
        {
            var siren=window.sirenArray[i];
            sirenSelect.options.length = 0;
            var sirenInList = false;
            for(var j = 0; j < siren.length; j++) {
                var selected = false;
                if(siren[j] == originalSiren)
                {
                    selected=true;
                    sirenInList=true;
                }
                sirenSelect.options[j]=new Option(siren[j],siren[j],selected,selected);
            }
            if(!sirenInList)
            {
                sirenSelect.options[j+1]=new Option(originalSiren+' (hors groupe)','',true,true);
                sirenSelect.options[j+1].disabled=true;
            }
            break;
        }
    }
}