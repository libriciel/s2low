import {
    getSirensToShow,
    updateSirenSelectList
} from "./sirenSelector.js";

/**
 * Les SIREN proposés dépendent des groupes qui administreront la collectivité : ils sont
 * l'intersection de ce que chacun autorise. Seul le super administrateur choisit ces groupes,
 * lui seul dispose donc de ces listes et du point d'entrée qui les recalcule.
 */
window.onload = () => {
    const modules = [...document.querySelectorAll('.administered-module')]
        .map(block => ({
            block,
            groupSelect: block.querySelector('select'),
            moduleCheckbox: document.getElementById(`perm_${block.dataset.module}`)
        }))
        .filter(({moduleCheckbox}) => moduleCheckbox);

    if (modules.length === 0) {
        return;
    }

    const sirenSelect = document.getElementById('SelectSirenInput');
    const originalSirenInput = document.getElementById('originalSiren');
    const availableSirensUrl = document.getElementById('availableSirensUrl');

    // Seul le super administrateur choisit les groupes : lui seul a des SIREN à recalculer, et lui
    // seul a droit au point d'entrée qui les fournit.
    const chosenGroups = modules.filter(({groupSelect}) => groupSelect);

    // Cocher un module et changer son groupe lancent deux appels : sans ce jeton, le premier revenu
    // en dernier réafficherait des SIREN qui ne correspondent plus aux groupes retenus.
    let lastAskedSirens = 0;

    const refreshSirens = async () => {
        if (chosenGroups.length === 0 || !sirenSelect || !originalSirenInput || !availableSirensUrl) {
            return;
        }

        const authorityIdInput = document.querySelector('input[name="id"]');
        const parameters = new URLSearchParams({authority_id: authorityIdInput ? authorityIdInput.value : ''});

        chosenGroups.forEach(({groupSelect, moduleCheckbox}) => {
            if (moduleCheckbox.checked && groupSelect.value) {
                parameters.set(groupSelect.name, groupSelect.value);
            }
        });

        const askedAt = ++lastAskedSirens;

        const response = await fetch(`${availableSirensUrl.value}?${parameters}`, {
            headers: {'Accept': 'application/json'}
        });

        if (!response.ok) {
            return;
        }

        const {sirens} = await response.json();

        if (askedAt !== lastAskedSirens) {
            return;
        }

        const sirensToShow = getSirensToShow(sirens, originalSirenInput.value);

        updateSirenSelectList(sirensToShow, sirenSelect);

        const emptyNotice = document.getElementById('noSirenAvailable');

        if (emptyNotice) {
            emptyNotice.style.display = sirens.length === 0 ? '' : 'none';
        }
    };

    modules.forEach(({block, groupSelect, moduleCheckbox}) => {
        if (groupSelect) {
            // Sans largeur explicite, select2 mesure un bloc encore masqué et se rend minuscule.
            window.jQuery(groupSelect).select2({width: '100%'}).on('change', refreshSirens);
        }

        moduleCheckbox.addEventListener('change', () => {
            // Un module éteint ne se règle pas : son bloc se referme et sort du calcul des SIREN.
            block.style.display = moduleCheckbox.checked ? '' : 'none';
            refreshSirens();
        });
    });
};
