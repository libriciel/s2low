export function getSirensToShow(availableSirens, currentSiren) {

    const currentSirenIsAvailable = availableSirens.includes(currentSiren);

    const sirensToShow = availableSirens.map(siren => ({
        siren,
        selected: siren === currentSiren,
        disabled: false
    }));

    if (currentSiren && !currentSirenIsAvailable) {
        // On va afficher le SIREN original et le sélectionner pour éviter d'en selectionner un autre par défaut
        sirensToShow.push({
            siren: `${currentSiren} (hors groupe)`,
            selected: true,
            disabled: true
        });
    }

    return sirensToShow;
}

export function updateSirenSelectList(sirensToShow, sirenSelectHTMLElement) {
    sirenSelectHTMLElement.options.length = 0;
    sirensToShow.forEach(siren => {
        sirenSelectHTMLElement.add(getOption(siren));
    });
}

function getOption({siren, selected, disabled}){
    const value = disabled ? "" : siren;

    const option = new Option(siren, value, selected, selected);

    option.disabled = disabled;

    return option;
}
