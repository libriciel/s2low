var isDom = document.getElementById ? true : false;
var isIE  = document.all ? true : false;
var isNS4 = document.layers ? true : false;
var cellCount = 15;
var currentCell = 0;
var popupWidth = 300;
var popupHeight = 150;

function startProgress()
{
    setInterval("updateProgress()", 400);
}

function updateProgress()
{
    for (i = 0; i < cellCount; i++) {
        hideCell(i);
    }
    showCell(currentCell % cellCount);
    currentCell++;
}

function setVisibility(pElement, pVisibility)
{
    if ( pVisibility ) {
        color = '#3366CC';
    } else {
        color = '#CCCCCC';
    }
    if (isDom) {
        document.getElementById(pElement).style.background = color;
    } else if (isIE) {
        document.all[pElement].style.background = color;
    } else if (isNS4) {
        document.layers[pElement].style.background = color;
    }
}

function showCell(pCell)
{
    setVisibility('progressCell' + pCell, true);
}

function hideCell(pCell)
{
    setVisibility('progressCell' + pCell, false);
}

function launchProgressPopup()
{
    LeftPosition = (window.screen.width - popupWidth) / 2;
    TopPosition  = (window.screen.height - popupHeight) / 2;
    window.open('progressPopup.php', 'progressPopup', 'top=' + TopPosition + ',screenY=' + TopPosition + ',left=' + LeftPosition + ',screenX=' + TopPosition + ',height=' + popupHeight + ',width=' + popupWidth + ',location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');
}

function closeProgressPopup()
{
    popup_w = window.open('#', 'progressPopup','width=1,height=1');
    popup_w.close('progressPopup');
}
