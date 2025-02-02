<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add settings pages to the admin menu
function essp_add_settings_pages() {
    // Main settings page
    add_menu_page(
        'Spotlight Search Settings',
        'Spotlight Search',
        'manage_options',
        'essp-settings',
        'essp_render_settings_page',
        'dashicons-search', // Icon for the menu
        81 // Position in the admin menu
    );

    // Submenu page for admin pages management
    add_submenu_page(
        'essp-settings',
        'Quick Admin Navigation Pages',
        'Admin Pages',
        'manage_options',
        'essp-admin-pages',
        'essp_render_admin_pages_settings' // Defined in admin-pages-settings.php
    );
}
add_action('admin_menu', 'essp_add_settings_pages');

// Render the main settings page
function essp_render_settings_page() {
    // Check if user is allowed
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>You do not have permission to access this page.</p></div>';
        return;
    }

    // Save settings
    if (isset($_POST['essp_settings_submit'])) {
        check_admin_referer('essp_settings_save', 'essp_settings_nonce');

        $post_types = isset($_POST['essp_post_types']) && is_array($_POST['essp_post_types']) ? array_map('sanitize_text_field', $_POST['essp_post_types']) : array('post', 'page');
        $default_builder = isset($_POST['essp_default_builder']) ? sanitize_text_field($_POST['essp_default_builder']) : 'default';
        $default_post_type = isset($_POST['essp_default_post_type']) ? sanitize_text_field($_POST['essp_default_post_type']) : 'page';
        $enable_admin_pages = isset($_POST['essp_enable_admin_pages']) ? 1 : 0;
        $results_order = isset($_POST['essp_results_order']) ? sanitize_text_field($_POST['essp_results_order']) : 'content_first';

        $settings = array(
            'post_types'       => $post_types,
            'default_builder'  => $default_builder,
            'default_post_type'=> $default_post_type,
            'enable_admin_pages'=> $enable_admin_pages,
            'results_order'    => $results_order,
        );

        update_option('essp_settings', $settings);

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }

    $settings = essp_get_settings();
    $available_post_types = get_post_types(array('public' => true), 'objects');
    ?>
    
    <div class="wrap">
        <h1>Spotlight Search Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field('essp_settings_save', 'essp_settings_nonce'); ?>

            <h2>General Settings</h2>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Post Types to Search</th>
                    <td>
                        <?php foreach ($available_post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" name="essp_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $settings['post_types'])); ?>>
                                <?php echo esc_html($post_type->labels->name); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Default Page Builder</th>
                    <td>
                        <select name="essp_default_builder">
                            <option value="default" <?php selected($settings['default_builder'], 'default'); ?>>Default Editor</option>
                            <option value="elementor" <?php selected($settings['default_builder'], 'elementor'); ?>>Elementor</option>
                            <option value="bricks" <?php selected($settings['default_builder'], 'bricks'); ?>>Bricks Builder</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Default Post Type for New Content</th>
                    <td>
                        <select name="essp_default_post_type">
                            <?php foreach ($available_post_types as $post_type): ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($settings['default_post_type'], $post_type->name); ?>>
                                    <?php echo esc_html($post_type->labels->singular_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Quick Admin Navigation</th>
                    <td>
                        <label>
                            <input type="checkbox" name="essp_enable_admin_pages" value="1" <?php checked($settings['enable_admin_pages'], 1); ?> />
                            Allow searching and navigating to WordPress admin pages.
                        </label>
                        <p class="description">You can manage the admin pages <a href="<?php echo admin_url('admin.php?page=essp-admin-pages'); ?>">here</a>.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Search Results Order</th>
                    <td>
                        <label>
                            <input type="radio" name="essp_results_order" value="content_first" <?php checked($settings['results_order'], 'content_first'); ?> />
                            Show Pages/Posts/Custom Post Types first
                        </label><br>
                        <label>
                            <input type="radio" name="essp_results_order" value="admin_first" <?php checked($settings['results_order'], 'admin_first'); ?> />
                            Show Quick Admin Navigation results first
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings', 'primary', 'essp_settings_submit'); ?>
        </form>
    </div>
    <?php
}
