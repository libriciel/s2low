import {
    getSirensToShow,
    updateSirenSelectList
} from "./sirenSelector.js";

window.onload = () => {
    const authorityGroupIdSelect = document.getElementsByName('authority_group_id');
    if (authorityGroupIdSelect.length !== 1) {
        console.log("Plusieurs éléments authority_group_id présents !");
        return;
    }

    const authorityGroupInput = authorityGroupIdSelect[0];
    const originalSirenInput = document.getElementById("originalSiren");
    const sirensByGroup = JSON.parse(document.getElementById("sirensByGroupArray").value);


    const sirenSelectInput = document.getElementById("SelectSirenInput");


    authorityGroupInput.addEventListener('change', function () {

        const sirensToShow = getSirensToShow(
            authorityGroupInput.value,
            originalSirenInput.value,
            sirensByGroup
        )
        updateSirenSelectList(sirensToShow, sirenSelectInput)
    });
};