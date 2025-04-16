<?php
/**
 * Plugin Name: Custom message plugin
 * Description: Shows a message at the top of every page.
 * Version: 1.0.2
 * Author: Anahit Sultanova
 */

add_action('wp_footer', 'smp_show_message');

function smp_show_message() {
    echo '<div style="background:#ff0; padding:10px; text-align:center;">
        This is a simple message from your plugin!!!!
    </div>';
}

/** 
 * Checking the updates of plugin with tag 
**/
add_filter('plugin_action_links_custom-message/custom-message.php', function ($links) {
    $links[] = '<a href="' . esc_url(add_query_arg('check_github_update', '1')) . '">Check for updates!</a>';
    return $links;
});

add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('update_plugins') || !isset($_GET['check_github_update'])) return;
    delete_site_transient('update_plugins');
    wp_redirect(remove_query_arg('check_github_update'));
    exit;
});

if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

add_filter('pre_set_site_transient_update_plugins', function ($transient) {
    if (empty($transient->checked)) return $transient;

    $slug = 'custom-message/custom-message.php';
    $plugin_path = WP_PLUGIN_DIR . '/' . $slug;

    // Path to your version metadata JSON
    $meta_url = 'https://raw.githubusercontent.com/anahit-sultanova/custom-message/main/public/latest-release.json';

    $res = wp_remote_get($meta_url, [
        'headers' => [
            'User-Agent' => 'WordPress/' . get_bloginfo('version'),
        ]
    ]);

    if (is_wp_error($res)) return $transient;

    $data = json_decode(wp_remote_retrieve_body($res));
    if (empty($data->tag_name) || empty($data->zip_url)) return $transient;

    $latest = ltrim($data->tag_name, 'v');
    $plugin_data = get_plugin_data($plugin_path);
    $current = $plugin_data['Version'];

    if (version_compare($latest, $current, '>')) {
        $transient->response[$slug] = (object)[
            'slug' => dirname($slug),
            'plugin' => $slug,
            'new_version' => $latest,
            'package' => $data->zip_url,
            'url' => $data->html_url ?? '',
        ];
    }

    return $transient;
});
