function toggleAllRetours(idName)
{
    const checked = document.getElementById(idName).checked;
    document.querySelectorAll('#helios-retour-mark-as-unread [name="ids[]"]')
        .forEach(box => {
            box.checked = checked;
        });
}

function toggle_visibility(id)
{
    var elt = document.getElementById(id);

    if (elt.style.display == "none") {
        elt.style.display = "block";
    } else if (elt.style.display == "block") {
        elt.style.display = "none";
    } else {
        elt.style.display = "block";
    }
}

function expand_area(id)
{
    var elt = document.getElementById(id);
    elt.style.display = "block";
}

function collapse_area(id)
{
    var elt = document.getElementById(id);
    elt.style.display = "none";
}

function toggle_upload(id, image)
{
    var elt = document.getElementById(id);

    let html = '<img id="progress_bar_img" alt="Barre de progression" /><br />';
    html += 'Soumission du formulaire et controle en cours.<br />';
    html += 'Merci de patienter...';

    elt.innerHTML = html;

    var img = document.getElementById('progress_bar_img');
    img.src = image.src;
}
