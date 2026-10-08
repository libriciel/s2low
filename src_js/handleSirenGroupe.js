import {
    getSirensToShow,
    updateSirenSelectList
} from "./sirenSelector.js";

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

    const chosenGroups = modules.filter(({groupSelect}) => groupSelect);

    // Deux appels peuvent se croiser : seule la réponse au dernier est affichée.
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
            throw new Error(`Recalcul des SIREN proposés refusé : HTTP ${response.status}`);
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

    const refreshSirensOrReport = () => refreshSirens().catch(error => console.error(error));

    modules.forEach(({block, groupSelect, moduleCheckbox}) => {
        if (groupSelect) {
            // Sans largeur explicite, select2 mesure un bloc encore masqué et se rend minuscule.
            window.jQuery(groupSelect).select2({width: '100%'}).on('change', refreshSirensOrReport);
        }

        moduleCheckbox.addEventListener('change', () => {
            block.style.display = moduleCheckbox.checked ? '' : 'none';
            refreshSirensOrReport();
        });
    });
};
