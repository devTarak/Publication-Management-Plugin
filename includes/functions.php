<?php
/**
 * Register the 'Publication' custom post type.
 */
function pmdbt_register_publication_post_type() {
    $args = array(
        'labels' => array(
            'name' => __('Publications', 'publication-management-by-devtarak'),
            'singular_name' => __('Publication', 'publication-management-by-devtarak'),
            'add_new' => __('Add New', 'publication-management-by-devtarak'),
            'add_new_item' => __('Add New Publication', 'publication-management-by-devtarak'),
            'edit_item' => __('Edit Publication', 'publication-management-by-devtarak'),
            'new_item' => __('New Publication', 'publication-management-by-devtarak'),
            'view_item' => __('View Publication', 'publication-management-by-devtarak'),
            'search_items' => __('Search Publications', 'publication-management-by-devtarak'),
            'not_found' => __('No Publications found', 'publication-management-by-devtarak'),
            'all_items' => __('All Publications', 'publication-management-by-devtarak'),
            'menu_name' => __('Publications', 'publication-management-by-devtarak'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-media-document',
        'rewrite' => array('slug' => 'publications'),
    );
    register_post_type('publication', $args);
}
add_action('init', 'pmdbt_register_publication_post_type');
// Modify the query for the publication archive page for pagination
function modify_publication_archive_query($query) {
    if (is_post_type_archive('publication') && $query->is_main_query()) {
        $query->set('posts_per_page', 20); // Set 20 publications per page
    }
}
add_action('pre_get_posts', 'modify_publication_archive_query');

// Load custom archive template for 'publication' post type
function load_custom_publication_archive_template($template) {
    if (is_post_type_archive('publication')) {
        // Set the path to the custom archive template
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/archive-publication.php';

        // Check if the custom template file exists
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_custom_publication_archive_template');
// Enforce PDF upload requirement before publishing the post
function check_publication_pdf_before_publish($post_id) {
    // Avoid redirect loop
    if (isset($_GET['message']) && $_GET['message'] == 1) {
        return $post_id;
    }

    // Check for autosave and ensure we are saving the correct post type
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if (get_post_type($post_id) !== 'publication') {
        return $post_id;
    }

    // Only check when publishing or updating
    if (isset($_POST['post_status']) && $_POST['post_status'] === 'publish') {
        // Prefer submitted value, but fall back to saved meta
        $pdf_url = isset($_POST['publication_pdf']) ? trim($_POST['publication_pdf']) : '';
        if (empty($pdf_url)) {
            $pdf_url = get_post_meta($post_id, '_publication_pdf', true);
        }

        // If no PDF is found, redirect and prevent publishing
        if (empty($pdf_url)) {
            wp_redirect(
                add_query_arg(
                    array(
                        'post' => $post_id,
                        'message' => 1,
                    ),
                    admin_url('post.php')
                )
            );
            exit;
        }
    }
    return $post_id;
}
add_action('save_post', 'check_publication_pdf_before_publish');
// Display PDF link on the frontend
function display_publication_pdf($content) {
    if (is_singular('publication')) {
        $pdf_url = get_post_meta(get_the_ID(), '_publication_pdf', true);
        if ($pdf_url) {
            $content .= '
                <div class="publication-pdf">
                    <iframe src="' . esc_url($pdf_url) . '" width="100%" height="1000px" style="border:1px solid #ccc; margin-top:10px; margin-bottom:50px"></iframe>
                </div>
            ';
        } else {
            $content .= '<p>' . __('No PDF available for this publication.', 'publication-management-by-devtarak') . '</p>';
        }
    }
    return $content;
}
add_filter('the_content', 'display_publication_pdf');

// Disable Gutenberg for 'publication' post type
add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
    if ($post_type === 'publication') {
        return false;  // Disable Gutenberg
    }
    return $use_block_editor;  // Enable Gutenberg for other post types
}, 10, 2);

// Enqueue custom styles for the publication archive page
function pmdbt_enqueue_archive_styles() {
    if (is_post_type_archive('publication')) {
        wp_enqueue_style('pmdbt-archive-style', plugin_dir_url(__FILE__) . '../assets/css/style.css', array(), '1.0', 'all');
    }
}
add_action('wp_enqueue_scripts', 'pmdbt_enqueue_archive_styles');

// Enqueue custom JavaScript for the publication post type
function pmdbt_enqueue_scripts($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_script('pmdbt-custom-script', plugin_dir_url(__FILE__) . '../assets/js/script.js', array('jquery'), '1.0', true);
    }
}
add_action('admin_enqueue_scripts', 'pmdbt_enqueue_scripts');
