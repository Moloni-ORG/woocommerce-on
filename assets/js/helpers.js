function showMoloniErrors() {
    let errorConsole = document.getElementsByClassName("MoloniConsoleLogError");

    if (errorConsole.length > 0) {
        Array.from(errorConsole).forEach(function (element) {
            element.style['display'] = element.style['display'] === 'none' ? 'block' : 'none';
        });
    }
}

function createMoloniDocument(redirectUrl) {
    var select = document.getElementById('moloni_document_type');

    if (select) {
        redirectUrl += '&document_type=' + select.value;
    }

    window.open(redirectUrl, '_blank')
}
