<?php
/**
 * Plugin Name: Nginx Cache Buster
 * Plugin URI: https://www.danutflorian.com/cache-buster
 * Description: A cache buster plugin for nginx.
 * Version: 1.0.0
 * Author: Danut Florian
 * Author URI: https://www.danutflorian.com/
 **/

/* Column */
function cached_column($cols)
{
    $cols['cache'] = __('Cached', 'cache-buster');
    return $cols;
}
add_filter('manage_posts_columns', 'cached_column');
add_filter('manage_pages_columns', 'cached_column');

function cached_column_value($column_name, $post_id)
{
    if ('cache' == $column_name) {
        $msg = nginx_check_cache(get_permalink($post_id)) ? '<span class="dashicons dashicons-saved"></span> YES' : '<span class="dashicons dashicons-no"></span> NO';
        echo "<p id='cache-status-$post_id'>$msg</p>";
    }
}
add_action('manage_posts_custom_column', 'cached_column_value', 10, 2);
add_action('manage_pages_custom_column', 'cached_column_value', 10, 2);
/* End Column */

function custom_post_list_row_action($actions, $post)
{
    // Check if the post type is 'post'. You can change it to your desired post type if needed.
    if (in_array($post->post_type, ['post', 'page'])) {
        // Get the post ID
        $post_id = $post->ID;
        // Add your custom button
        $custom_button = '<a href="#" class="button-link-delete cache-buster" data-post-id="' . $post_id . '">🧹️ Bust cache</a>';

        // Insert the custom button after 'View' link
        $actions['custom_button'] = $custom_button;
    }
    return $actions;
}
add_filter('post_row_actions', 'custom_post_list_row_action', 10, 2);
add_filter('page_row_actions', 'custom_post_list_row_action', 10, 2);

function custom_bulk_actions($actions)
{
    // Add your custom bulk action
    $actions['clear_bulk_cache'] = __('🧹️ Bust Cache', 'cache-buster');
    return $actions;
}
add_filter('bulk_actions-edit-post', 'custom_bulk_actions');
add_filter('bulk_actions-edit-page', 'custom_bulk_actions');

function custom_admin_bar_button($wp_admin_bar)
{
    global $post;

    if (is_admin()) {
        $has_cache = nginx_check_general_cache();
        if ($has_cache) {
            $wp_admin_bar->add_node([
                'id' => 'global-cache-buster',
                'title' => '<span class="ab-icon dashicons dashicons-trash"></span> Bust all cache',
                'href' => '#', // Replace '#' with the desired URL
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id' => 'no-cache',
                'title' => '<span class="ab-icon dashicons dashicons-marker"></span> No cache',
            ]);
        }
    } else if ($post) {
        // Get the current post ID
        $post_id = $post->ID;
        $has_cache = nginx_check_cache(get_permalink($post_id));

        if ($has_cache) {
            // Add a parent node to the admin bar with an icon
            $wp_admin_bar->add_node([
                'id' => 'bust-cache',
                'title' => '<span class="ab-icon dashicons dashicons-trash"></span> Bust cache',
                'href' => '#',
                'meta' => [
                    'onclick' => "clear_cache($post_id)",
                ]
            ]);
        } else {
            $wp_admin_bar->add_node([
                'id' => 'no-cache',
                'title' => '<span class="ab-icon dashicons dashicons-marker"></span> No cache',
            ]);
        }
    }
}
add_action('admin_bar_menu', 'custom_admin_bar_button', 999);

function clear_post_cache(): void
{
    if (wp_verify_nonce($_POST['_ajax_nonce'], 'clear_post_cache')) {
        // Check if the post ID is provided in the URL
        if (isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
            $status = nginx_clear_cache(get_permalink($post_id));
            wp_send_json([
                'status' => $status,
                'msg' => $status ? "The cache for post $post_id has been cleared." : "No cache found for the post $post_id.",
            ]);
        } else {
            // Handle case where post ID is not provided
            wp_send_json([
                'status' => false,
                'msg' => 'Post ID not provided'
            ]);
        }
    } else {
        die("Nonce check failed");
    }
}
add_action('wp_ajax_clear_post_cache', 'clear_post_cache');

function clear_all_cache(): void
{
    if (wp_verify_nonce($_POST['_ajax_nonce'], 'clear_all_cache')) {
        // Check if the post ID is provided in the URL
        $status = nginx_clear_general_cache();
        wp_send_json([
            'status' => $status,
            'msg' => $status ? "All cache has been cleared." : "Something bad happened.",
        ]);
    } else {
        die("Nonce check failed");
    }
}
add_action('wp_ajax_clear_all_cache', 'clear_all_cache');

function handle_custom_bulk_action(): void
{
    // Check nonce
    if (! check_admin_referer('bulk-posts')) {
        die ("Nonce check failed");
    }

    // Check if the action is triggered and the nonce is valid
    if (isset($_GET['action']) && $_GET['action'] === 'clear_bulk_cache') {
        foreach ($_GET['post'] as $postId) {
            nginx_clear_cache(get_permalink($postId));
        }

        // Set a flag to indicate that the bulk action has been triggered
        set_transient('bulk_clear_cache_completed', $_GET['post_type'], 5); // Set the transient for 5 seconds

        wp_redirect(admin_url('edit.php?post_type=' . $_GET['post_type']));
        exit;
    }
}

add_action('admin_action_clear_bulk_cache', 'handle_custom_bulk_action');


function display_bulk_action_notification(): void
{
    // Check if the custom bulk action flag is set
    if ($post_type = get_transient('bulk_clear_cache_completed')) {
        echo "<div class='notice notice-success is-dismissible'>
            <p>Cache busted successfully for selected {$post_type}s.</p>
        </div>";
        // Delete the transient to prevent displaying the notice on subsequent page loads
        delete_transient('bulk_clear_cache_completed');
    }
}

add_action('admin_notices', 'display_bulk_action_notification');

// Add scripts to wordpress
function enqueue_custom_scripts(): void
{
    // Enqueue JavaScript file with jQuery dependency
    wp_enqueue_script('buster-scripts', plugins_url('js/buster-scripts.js', __FILE__), array('jquery'), '1.0', true);

    wp_localize_script(
        'buster-scripts',
        'buster',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'clear_post_cache_nonce' => wp_create_nonce('clear_post_cache'),
            'clear_all_cache_nonce' => wp_create_nonce('clear_all_cache'),
        ]
    );

}
add_action('admin_enqueue_scripts', 'enqueue_custom_scripts');
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// Define a callback function to execute when a post is updated
function post_updated_callback($post_id, $post, $update): void
{
    // Check if the post type is 'post' or 'page'
    if ($post->post_type == 'post' || $post->post_type == 'page') {
        nginx_clear_cache(get_permalink($post_id));
    }
}

// Hook the callback function to the save_post action
add_action('save_post', 'post_updated_callback', 10, 3);

function generate_hash(string $url): string
{
    $url = wp_parse_url($url);
    if (!$url) {
        echo 'Invalid URL entered';
        die();
    }
    $scheme = $url['scheme'];
    $host = $url['host'];
    $request_uri = $url['path'];
    return md5($scheme . 'GET' . $host . $request_uri);
}

function generate_path(string $path, string $hash): string
{
    return $path . substr($hash, -1) . '/' . substr($hash, -3, 2) . '/' . $hash;
}

function nginx_clear_cache(string $url): bool
{
    $cache_path = '/etc/nginx/cache/';
    return @unlink(generate_path($cache_path, generate_hash($url)));
}

function nginx_check_cache(string $url): bool
{
    $cache_path = '/etc/nginx/cache/';
    return file_exists(generate_path($cache_path, generate_hash($url)));
}

function nginx_check_general_cache(): bool
{
    $cache_path = '/etc/nginx/cache/';
    if (!is_dir($cache_path)) {
        return false;
    }

    // Scan the directory
    $files = scandir($cache_path);

    // Remove . and .. from the list of files
    $files = array_diff($files, array('.', '..'));

    // Check if the directory is empty
    if (count($files) === 0) {
        return false;
    }

    return true;
}

function nginx_clear_general_cache(): bool
{
    $cache_path = '/etc/nginx/cache/';
    return recursiveDelete($cache_path);
}

function recursiveDelete($dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            recursiveDelete($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
    return true;
}