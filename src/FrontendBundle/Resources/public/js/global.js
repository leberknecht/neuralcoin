$(document).on('change', '.range-input',  function(){
    let template = $(this).data('label-template');
    let labelText =  $(this).parent().find('label').text();

    if (template) {
        labelText = template.replace('{value}', $(this).val());
    } else {
        if (labelText.indexOf('(') != -1) {
            labelText = labelText.substring(0, labelText.indexOf('(') - 1);
        }

        labelText += ' (' + $(this).val() + ')';
    }

    $(this).parent().find('label').text(labelText);
});
