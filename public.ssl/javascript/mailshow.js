function toggle_mail_error(id)
{
    var content = document.getElementById("mailError_" + id);
    var expander = document.getElementById("expander_" + id);

    if (content.style.display == "block") {
        content.style.display = "none";
        expander.innerHTML = "+";
    } else {
        content.style.display = "block";
        expander.innerHTML = "-";
    }
}