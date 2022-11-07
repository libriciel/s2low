
$(document).ready(function () {


    $(".zselect_authorities").select2();


});


(function ( $ ) {
    $.fn.pastell_zselect = function (placeholder_str) {
        this.each(function () {
            $(this).select2({
                placeholder: $('<i>').text(placeholder_str),
                renderItem: function (item, term) {
                    return $('<span>').text(item.label);
                },
                noResults: function (term) {
                    return $('<span>').addClass('no-results').text("Pas de résultat pour " + term + ".")
                }
            })
        })
        return this;
    }


}(jQuery));
