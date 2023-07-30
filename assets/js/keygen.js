jQuery(document).ready(function($) {
    // Quick Edit button click handler
    $('.quick-edit-button').on('click', function(e) {
        e.preventDefault();

        var keyword = $(this).data('keyword');
        var inputField = $('input.keyword-input[data-keyword="' + keyword + '"]');

        // Make the input field editable
        inputField.prop('readonly', false);

        // Hide the Quick Edit button and show the Save button
        $(this).hide();
        $(this).next('.quick-save-button').show();
    });

    // Save button click handler
    $('.quick-save-button').on('click', function(e) {
        e.preventDefault();

        var keyword = $(this).data('keyword');
        var inputField = $('input.keyword-input[data-keyword="' + keyword + '"]');

        // Get the new keyword from the input field
        var newKeyword = inputField.val();

        // Send AJAX request to save the new keyword
        $.post(genwp_ajax_object.ajaxurl, {
            action: 'genwp_update_keyword',
            nonce: genwp_ajax_object.updateKeywordNonce,
            old_keyword: keyword,
            new_keyword: newKeyword
        }, function(response) {
            if (response.success) {
                // Update the keyword data attribute and make the input field read-only
                inputField.data('keyword', response.data.new_keyword).prop('readonly', true);

                // Hide the Save button and show the Quick Edit button
                $('.quick-save-button[data-keyword="' + keyword + '"]').hide().prev('.quick-edit-button').show().data('keyword', newKeyword);
            } else {
                alert(response.data.message);
            }
        }).fail(function() {
            alert('An error occurred while trying to save the keyword.');
        });
    });

    // Save Map Button
    $(document).on('click', "input[type='submit'][name^='save_map']", function(e) {
        e.preventDefault();

        var keyword = $(this).attr('name').match(/\[(.*)\]/)[1];
        var user_id = $(`select[name='user_select[${keyword}]']`).val();
        var term_id = $(`select[name='category-select[${keyword}]']`).val();

        $.post(genwp_ajax_object.ajaxurl, {
            action: 'genwp_save_map',
            nonce: genwp_ajax_object.saveMapNonce,
            keyword: keyword,
            user_id: user_id,
            term_id: term_id
        }, function(response) {
            if(response.success) {
                alert('Keyword mapping was successful. Reload Page');
            } else {
                alert('Keyword mapping failed. ' + response.data.message);
            }            
        });
    });

});