<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Function to get admin pages from the database
function essp_get_admin_pages() {
    $admin_pages = get_option('essp_admin_pages', essp_get_default_admin_pages());
    $accessible_pages = array();

    // Check if the user can access the admin area
    if (!current_user_can('read')) {
        return $accessible_pages;
    }

    foreach ($admin_pages as $page) {
        if (current_user_can($page['capability'])) {
            $accessible_pages[] = array(
                'title' => $page['title'],
                'link'  => $page['link'],
            );
        }
    }

    return $accessible_pages;
}

// Function to get the default admin pages (used when no pages are saved in the database)
function essp_get_default_admin_pages() {
    $default_admin_pages = array(
        array(
            'title'       => 'Dashboard',
            'link'        => admin_url(),
            'capability'  => 'read',
        ),
        array(
            'title'       => 'Posts',
            'link'        => admin_url('edit.php'),
            'capability'  => 'edit_posts',
        ),
        array(
            'title'       => 'Add New Post',
            'link'        => admin_url('post-new.php'),
            'capability'  => 'edit_posts',
        ),
        array(
            'title'       => 'Media Library',
            'link'        => admin_url('upload.php'),
            'capability'  => 'upload_files',
        ),
        array(
            'title'       => 'Pages',
            'link'        => admin_url('edit.php?post_type=page'),
            'capability'  => 'edit_pages',
        ),
        array(
            'title'       => 'Add New Page',
            'link'        => admin_url('post-new.php?post_type=page'),
            'capability'  => 'edit_pages',
        ),
        array(
            'title'       => 'Plugins',
            'link'        => admin_url('plugins.php'),
            'capability'  => 'activate_plugins',
        ),
        array(
            'title'       => 'Add New Plugin',
            'link'        => admin_url('plugin-install.php'),
            'capability'  => 'install_plugins',
        ),
        array(
            'title'       => 'Users',
            'link'        => admin_url('users.php'),
            'capability'  => 'list_users',
        ),
        array(
            'title'       => 'Settings',
            'link'        => admin_url('options-general.php'),
            'capability'  => 'manage_options',
        ),
        // Add more admin pages as needed
    );

    return $default_admin_pages;
}
