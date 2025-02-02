<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function essp_search_handler() {
    // Check nonce for security
    check_ajax_referer('essp_nonce', 'security');

    // Check if the user is logged in and is an admin
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_send_json_error('You are not authorized to perform this action.', 403);
        wp_die();
    }

    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $is_create = isset($_POST['is_create']) && $_POST['is_create'] === 'true' ? true : false;
    $include_admin_pages = isset($_POST['include_admin_pages']) && $_POST['include_admin_pages'] === 'true' ? true : false;
    $settings = essp_get_settings();

    // Default action and builder
    $action = 'view';
    $builder = $settings['default_builder'];

    // Improved Command Parsing
    $pattern = '/^(?:(edit|new)\s+)?(?:(bricks|elementor|default)\s+)?(.+)$/i';
    preg_match($pattern, $query, $matches);

    if (!empty($matches)) {
        $action = isset($matches[1]) && !empty($matches[1]) ? strtolower($matches[1]) : $action;
        $builder = isset($matches[2]) && !empty($matches[2]) ? strtolower($matches[2]) : $builder;
        $search_term = isset($matches[3]) ? $matches[3] : '';
    } else {
        $search_term = $query;
    }

    if ($is_create) {
        // Handle 'new' command
        if ($action == 'new') {
            // Check user capabilities
            if (!current_user_can('edit_posts')) {
                wp_send_json(array('status' => 'error', 'message' => 'You do not have permission to create new content.'));
                wp_die();
            }

            // Create new post/page
            $new_post_type = $settings['default_post_type'];

            $new_post = array(
                'post_title'   => $search_term,
                'post_status'  => 'draft',
                'post_type'    => $new_post_type,
                'post_author'  => get_current_user_id(),
            );

            $new_post_id = wp_insert_post($new_post);

            if ($new_post_id) {
                $response = array(
                    'status' => 'success',
                    'message' => 'New ' . $new_post_type . ' "' . $search_term . '" has been created.',
                    'view_link' => get_permalink($new_post_id),
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Error creating new content.',
                );
            }

            wp_send_json($response);
        } else {
            wp_send_json(array('status' => 'error', 'message' => 'Invalid action for creation.'));
        }
    } else {
        // Handle search
        // Ignore 'new' commands during search
        if ($action == 'new') {
            $action = 'view';
        }

        // Initialize results arrays
        $content_results = [];
        $admin_results = [];

        // Perform search on posts/pages
        $args = array(
            's' => $search_term,
            'post_type' => $settings['post_types'],
            'post_status' => 'any',
            'posts_per_page' => 10,
        );

        $search_query = new WP_Query($args);

        if ($search_query->have_posts()) {
            while ($search_query->have_posts()) {
                $search_query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();

                // Generate appropriate links based on action and builder
                if ($action == 'edit') {
                    if (!current_user_can('edit_post', $post_id)) {
                        continue; // Skip posts the user can't edit
                    }
                    if ($builder == 'elementor') {
                        $link = admin_url("post.php?post={$post_id}&action=elementor");
                    } elseif ($builder == 'bricks') {
                        $link = add_query_arg('bricks', 'run', get_permalink($post_id));
                    } else {
                        $link = admin_url("post.php?post={$post_id}&action=edit");
                    }
                } else {
                    $link = get_permalink($post_id);
                }

                $content_results[] = array(
                    'title' => $post_title,
                    'link' => $link,
                );
            }
            wp_reset_postdata();
        }

        // Include admin pages if enabled
        if ($include_admin_pages && $settings['enable_admin_pages'] && current_user_can('read')) {
            $admin_pages = essp_get_admin_pages();

            // Filter admin pages based on the search term
            $search_term_lower = strtolower($search_term);
            foreach ($admin_pages as $admin_page) {
                if (strpos(strtolower($admin_page['title']), $search_term_lower) !== false) {
                    $admin_results[] = array(
                        'title' => $admin_page['title'],
                        'link'  => $admin_page['link'],
                        'type'  => 'admin_page', // Optional, for distinguishing in JavaScript
                    );
                }
            }
        }

        // Combine results based on user preference
        $results = [];
        if ($settings['results_order'] === 'admin_first') {
            $results = array_merge($admin_results, $content_results);
        } else {
            $results = array_merge($content_results, $admin_results);
        }

        wp_send_json(array('status' => 'success', 'results' => $results));
    }

    wp_die();
}
