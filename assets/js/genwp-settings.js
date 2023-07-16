// Handles showing terms based on post type selection

jQuery(document).ready(function($) {
    $('#genwp-post-type').on('change', function() {
        var postType = $(this).val();
        $.post(
            genwp_ajax_object.ajaxurl,
            {
                action: 'get_terms',
                post_type: postType
            },
            function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '';
                    for (var taxonomy in data) {
                        html += '<h3>' + taxonomy + '</h3>';
                        html += '<ul>';
                        for (var i in data[taxonomy]) {
                            html += '<li><input type="checkbox" name="genwp_taxonomy_terms[]" value="' + data[taxonomy][i] + '">' + data[taxonomy][i] + '</li>';
                        }
                        html += '</ul>';
                    }
                    $('#renderTerms').html(html);
                }
            }
        );
    });
});
