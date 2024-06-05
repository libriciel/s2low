window.onload = (event) => {
    let elements = window.document.getElementsByName('authority_group_id');
    if (elements.length != 1) {
        console.log("Plusieurs éléments authority_group_id présents !");
        return;
    }

    elements[0].addEventListener('change', function () {
        sirensToShow = getSirensToShow(
            window.document.getElementsByName("authority_group_id")[0].value,
            window.document.getElementById("originalSiren").value,
            window.document.getElementById("originalGroupId").value,
            JSON.parse(window.document.getElementById("sirensByGroupArray").value)
        )
        updateSirenSelectList(sirensToShow, window.document.getElementById("SelectSirenInput"),)
    });
};

function getSirensToShow(selectedGroupId, originalSiren, originalGroupId, sirensByGroupArray) {

    const sirensInGroup = sirensByGroupArray[selectedGroupId];

    let originalSirenIsInSelectedGroup = false;
    let sirensToShow = [];

    sirensInGroup.forEach((siren, index) => {
        let selected = false;
        if (siren == originalSiren) {
            selected = true;
            originalSirenIsInSelectedGroup = true;
        }
        sirensToShow.push({'siren': siren, 'selected': selected, 'disabled': false});
    })

    if (!originalSirenIsInSelectedGroup) {
        // On va afficher le SIREN original et le sélectionner pour éviter d'en selectionner un autre par défaut
        sirensToShow.push({'siren': originalSiren + ' (hors groupe)', 'selected': true, 'disabled': true});
    }

    return sirensToShow;
}

function updateSirenSelectList(sirensToShow, sirenSelectHTMLElement) {
    sirenSelectHTMLElement.options.length = 0;
    sirensToShow.forEach((sirenToShow, index) => {
            sirenSelectHTMLElement.options[index] = getOption(sirenToShow);
    })
}

function getOption(sirenToShow){
    if (sirenToShow.disabled) {
        let option = new Option(
            sirenToShow.siren,
            '',
            true,
            true
        );
        option.disabled = true;
        return option;
    } else {
        return new Option(sirenToShow.siren, sirenToShow.siren, sirenToShow.selected, sirenToShow.selected);
    }
}