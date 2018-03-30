$(function () {
    var $container = $(".ezautomatedtranlsation-services-container:first");
    $container.find(".ez-field-edit--ezboolean .ez-data-source__label").click(function () {
        var $input = $(this).find("input[type='checkbox']");
        var isChecked = $input.attr('checked') === 'checked';
        if (isChecked) {
            $input.removeAttr('checked');
            $(this).removeClass('is-checked');
        } else {
            $(this).addClass('is-checked');
            $input.attr('checked', 'checked');
        }
        return false;
    });
});
