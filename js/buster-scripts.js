jQuery(document).ready(function($) {
    // Listen for clicks on the custom button
    $('.cache-buster').on('click', function(e) {
        e.preventDefault();
        // Get the post ID from the data attribute
        const postId = $(this).data('post-id');
        // Make AJAX call to execute custom action
        $.ajax({
            url: buster.ajax_url, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'clear_post_cache',
                post_id: postId,
                _ajax_nonce: buster.nonce // Pass the nonce
            },
            success: function({status, msg}) {
                document.querySelector(`#cache-status-${postId}`).innerText = "NO";

                let notification = status ? `<div class="notice notice-success is-dismissible my_notice"><p><strong>SUCCESS: </strong>${msg}</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>` : `<div class="notice notice-error is-dismissible my_notice"><p><strong>ERROR: </strong>${msg}</p><button type="button" class="notice-dismiss" onclick="javascript: return px_dissmiss_notice(this);"><span class="screen-reader-text">Dismiss this notice.</span></button></div>`;
                jQuery('.wp-header-end').after(notification);
            },
            error: function(xhr, status, error) {
                // Handle error
                console.error(error);
            }
        });
    });

    $('#wp-admin-bar-global-cache-buster').on('click', function(e) {
        e.preventDefault();
        // Get the post ID from the data attribute
        // Make AJAX call to execute custom action
        $.ajax({
            url: buster.ajax_url, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'clear_all_cache',
                _ajax_nonce: buster.nonce // Pass the nonce
            },
            success: function() {
                e.currentTarget.innerHTML = `<a class="ab-item" href=""><span class="ab-icon dashicons dashicons-marker"></span> No cache</a>`;
            },
            error: function (xhr, status, error) {
                // Handle error
                console.error(error);
            }
        });
    });
});

function px_dissmiss_notice(dobj)
{
    jQuery(dobj).parent().slideUp("normal", function() {jQuery(this).remove();});
    return false;
}

function clear_cache(postId) {
    console.log(postId);

    $.ajax({
        url: buster.ajax_url, // WordPress AJAX URL
        type: 'POST',
        data: {
            action: 'clear_post_cache',
            post_id: postId,
            _ajax_nonce: buster.nonce // Pass the nonce
        },
        success: function() {
            document.querySelector(`#wp-admin-bar-bust-cache`).innerHTML = `<div class="ab-item ab-empty-item"><span class="ab-icon dashicons dashicons-marker"></span> No cache</div>`;
        },
        error: function(xhr, status, error) {
            // Handle error
            console.error(error);
        }
    });
}