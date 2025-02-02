<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Render the admin pages settings page
function essp_render_admin_pages_settings() {
    // Check if user is allowed
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>You do not have permission to access this page.</p></div>';
        return;
    }

    // Save admin pages
    if (isset($_POST['essp_admin_pages_submit'])) {
        check_admin_referer('essp_admin_pages_save', 'essp_admin_pages_nonce');

        if (isset($_POST['essp_admin_pages']) && is_array($_POST['essp_admin_pages'])) {
            $admin_pages_input = $_POST['essp_admin_pages'];
            $admin_pages = array();

            foreach ($admin_pages_input as $page_input) {
                $title = sanitize_text_field($page_input['title']);
                $link = esc_url_raw($page_input['link']);
                $capability = sanitize_text_field($page_input['capability']);

                if ($title && $link && $capability) {
                    $admin_pages[] = array(
                        'title'       => $title,
                        'link'        => $link,
                        'capability'  => $capability,
                    );
                }
            }

            update_option('essp_admin_pages', $admin_pages);
            echo '<div class="notice notice-success is-dismissible"><p>Admin pages saved successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>No admin pages data provided.</p></div>';
        }
    }

    // Get admin pages
    $admin_pages = get_option('essp_admin_pages', essp_get_default_admin_pages());
    ?>
    
    <div class="wrap">
        <h1>Quick Admin Navigation Pages</h1>
        <form method="post" action="">
            <?php wp_nonce_field('essp_admin_pages_save', 'essp_admin_pages_nonce'); ?>

            <p>Manage the list of admin pages available in the Quick Admin Navigation feature.</p>

            <table class="form-table" id="essp-admin-pages-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Title</th>
                        <th style="width: 35%;">Link</th>
                        <th style="width: 20%;">Capability</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admin_pages as $index => $page): ?>
                        <tr>
                            <td>
                                <input type="text" name="essp_admin_pages[<?php echo $index; ?>][title]" value="<?php echo esc_attr($page['title']); ?>" required />
                            </td>
                            <td>
                                <input type="url" name="essp_admin_pages[<?php echo $index; ?>][link]" value="<?php echo esc_attr($page['link']); ?>" required />
                            </td>
                            <td>
                                <input type="text" name="essp_admin_pages[<?php echo $index; ?>][capability]" value="<?php echo esc_attr($page['capability']); ?>" required />
                            </td>
                            <td>
                                <button type="button" class="essp-remove-admin-page button button-secondary">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <button type="button" id="essp-add-admin-page" class="button">Add Admin Page</button>
            </p>

            <?php submit_button('Save Admin Pages', 'primary', 'essp_admin_pages_submit'); ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const addButton = document.getElementById('essp-add-admin-page');
        const tableBody = document.querySelector('#essp-admin-pages-table tbody');

        addButton.addEventListener('click', function () {
            const rowCount = tableBody.rows.length;
            const newRow = document.createElement('tr');

            newRow.innerHTML = `
                <td>
                    <input type="text" name="essp_admin_pages[${rowCount}][title]" value="" required />
                </td>
                <td>
                    <input type="url" name="essp_admin_pages[${rowCount}][link]" value="" required />
                </td>
                <td>
                    <input type="text" name="essp_admin_pages[${rowCount}][capability]" value="manage_options" required />
                </td>
                <td>
                    <button type="button" class="essp-remove-admin-page button button-secondary">Remove</button>
                </td>
            `;

            tableBody.appendChild(newRow);
        });

        tableBody.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('essp-remove-admin-page')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        });
    });
    </script>
    <?php
}
