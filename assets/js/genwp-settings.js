jQuery(document).ready(function($) {
    // When a .nav-tab is clicked.
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();

        // Remove the 'nav-tab-active' class from all tabs.
        $('.nav-tab').removeClass('nav-tab-active');

        // Add the 'nav-tab-active' class to this tab.
        $(this).addClass('nav-tab-active');

        // Hide all tab content.
        $('.settings-tab').hide();

        // Show the associated tab content.
        var id = $(this).attr('href');
        $(id).show();
    });

    // When the post type selection changes
    $('#genwp-post-type-field').change(function() {
        var data = {
            'action': 'genwp_get_taxonomies',
            'post_type': $(this).val(),
            'nonce': genwp_vars.nonce
        };

        $.post(genwp_vars.ajax_url, data, function(response) {
            // Replace the taxonomy dropdown options with the ones from the server
            $('#genwp-taxonomy-field').html(response);
        });
    });

    // When the taxonomy selection changes
    $('#genwp-taxonomy-field').change(function() {
        var data = {
            'action': 'genwp_get_terms',
            'taxonomy': $(this).val(),
            'nonce': genwp_vars.nonce
        };

        $.post(genwp_vars.ajax_url, data, function(response) {
            // Replace the terms dropdown options with the ones from the server
            $('#genwp-items-field').html(response);
        });
    });
});