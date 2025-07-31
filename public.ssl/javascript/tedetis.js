function show_all()
{
    toggle_all("block", "-");
}

function hide_all()
{
    toggle_all("none", "+");
}

function toggle_all(style, symbol)
{
    let done = false;
    let i = 1;

    while (! done) {
        var content = document.getElementById("envelope_content_" + i);
        var expander = document.getElementById("expander_" + i);

        if (content && expander) {
            content.style.display = style;
            expander.innerHTML = symbol;
        } else {
            done = true;
        }
        i++;
    }
}

function toggle_envelope_content(id)
{
    var content = document.getElementById("envelope_content_" + id);
    var expander = document.getElementById("expander_" + id);

    if (content.style.display == "block") {
        content.style.display = "none";
        expander.innerHTML = "+";
    } else {
        content.style.display = "block";
        expander.innerHTML = "-";
    }
}

function GereChkbox(conteneur, a_faire)
{
    var blnEtat = null;
    var Tab = document.getElementsByTagName("input");
    for (var i = 0; i < Tab.length; i++) {
        let Chckbox = Tab[i];
        if (Chckbox.getAttribute("type") == "checkbox") {
            blnEtat = (a_faire == '0') ? false : (a_faire == '1') ? true : (Chckbox.checked) ? false : true;
            Chckbox.checked = blnEtat;
        }
    }
}

function afficheWarning()
{
    var n = 0;
    var liste = document.getElementsByTagName("input");
    for (var i = 0; i < liste.length; i++) {
        if (liste[i].getAttribute("type") == "checkbox") {
            if (liste[i].checked) {
                n++;
            }
        }
    }
    var msg = "Voulez-vous vraiment affecter les " + n + " transactions selectionnées ? ";
    msg += "Cette action n'est pas réversible et est sous votre entière responsabilité.";
    return confirm(msg);
}
