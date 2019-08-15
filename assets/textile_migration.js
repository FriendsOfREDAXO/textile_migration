/**
 * Created by joachimdoerr on 17.12.16.
 */

$(document).on('rex:ready', function(e, container) {

    let counting = $('.textile_migration_processbar_empty .counting'),
        process_bar = $('.textile_migration_processbar_empty'),
        button = $(".textile_migration-action-button"),
        pre = $('.textile_migration_list_empty .result'),
        action_wrapper = $('.textile_migration_action_wrapper'),
        process_wrapper = $('.textile_migration_process_wrapper'),
        call_type_inputs = $(".call_types input"),
        definition_textarea = $('textarea.action_definition'),
        success_message = $('.textile_migration_process_success_msg');

    definition_textarea.hide();

    function countit(step, steps) {
        counting.text(step + ' / ' + steps);
    }

    function showSuccessMsg(count) {
        success_message.html(success_message.html().replace('%o', count));
        success_message.show();
    }

    function goforit(step, size, calls, definition) {

        let pre = $('.textile_migration_result .result'),
            url = "index.php?page=textile_migration/index&textile_migration_ajax=1&step=" + step + "&size=" + size + "&calls=" + calls + "&definition=" + encodeURIComponent(definition);

        console.log(url);

        $.ajax({
            url: url,
            dataType: "json",
            success: function(result){
                // add info text
                if (result.content === '') {
                } else {
                    pre.prepend('Step: ' + result.step + "\n" + result.content + "\n");
                }

                countit(step, result.steps);

                // next
                if (step < result.steps)
                    goforit(step + 1, size, calls, definition);

                if (step === result.steps)
                    showSuccessMsg(result.count);
            },
            error: function (xhr, type, exception) {
                // add info text
                pre.prepend('Step:' + step + ' Error: ' + exception + "\n\n");
            }
        });
    }

    call_type_inputs.change(function() {
        if (this.value == 'article_automatic') {
            definition_textarea.hide();
        } else {
            definition_textarea.show();
        }
    });

    button.on('click', function() {
        let calls = '',
            brands = '',
            size = 4;

        call_type_inputs.each(function(){
            if ($(this).is(":checked")) {
                calls = $(this).val();
                if ($(this).data('size') > 0) {
                    size = $(this).data('size');
                }
            }
        });

        $('.rex-page-main-inner .rex-page-header + .alert').remove();

        success_message.hide();
        action_wrapper.hide();
        process_wrapper.show();
        process_bar.show();
        pre.show();

        goforit(0, size, calls, definition_textarea.val());

        return false;
    });

});