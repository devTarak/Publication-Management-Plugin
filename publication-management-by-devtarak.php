<?php
/*
Plugin Name: Publication Management by devTarak
Plugin URI: https://github.com/devTarak/Publication-Management-Plugin
Description: A custom plugin to manage "Publication" custom post type with PDF uploads.
Version: 1.0
Author: Tarak Rahman
Author URI: https://devtarak.github.io/
License: GPL2
*/

// Register the 'Publication' custom post type
function register_publication_post_type() {
    $args = array(
        'labels' => array(
            'name' => 'Publications',
            'singular_name' => 'Publication',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Publication',
            'edit_item' => 'Edit Publication',
            'new_item' => 'New Publication',
            'view_item' => 'View Publication',
            'search_items' => 'Search Publications',
            'not_found' => 'No Publications found',
            'not_found_in_trash' => 'No Publications found in Trash',
            'all_items' => 'All Publications',
            'menu_name' => 'Publications',
            'name_admin_bar' => 'Publication',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true, // for Gutenberg support
        'menu_icon' => 'dashicons-media-document',
        'rewrite' => array('slug' => 'publications'),
    );
    register_post_type('publication', $args);
}
add_action('init', 'register_publication_post_type');

// Register Meta Box for PDF Upload
function add_publication_pdf_meta_box() {
    add_meta_box(
        'publication_pdf',
        'Upload PDF',
        'publication_pdf_meta_box_callback',
        'publication',
        'normal',
        'high',
        array('__back_compat_meta_box' => true)
    );
}
add_action('add_meta_boxes', 'add_publication_pdf_meta_box');

// Meta Box Callback to display the PDF upload option (for editing)
function publication_pdf_meta_box_callback($post) {
    // Retrieve the current PDF URL
    $pdf_url = get_post_meta($post->ID, '_publication_pdf', true);
    ?>
    <label for="publication_pdf_link">Select PDF:</label>
    <input type="text" id="publication_pdf_link" name="publication_pdf" value="<?php echo esc_attr($pdf_url); ?>" style="width:80%;"/>
    <button type="button" class="button" id="publication_pdf_button">Select PDF</button>
    <div id="publication_pdf_preview">
        <?php if ($pdf_url) : ?>
            <p><strong>Current PDF:</strong> <a href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php echo basename($pdf_url); ?></a></p>
        <?php else : ?>
            <p>No PDF uploaded yet.</p>
        <?php endif; ?>
    </div>
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        $('#publication_pdf_button').click(function(e) {
            e.preventDefault();
            if (!mediaUploader) {
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Select a PDF',
                    button: { text: 'Select PDF' },
                    library: { type: 'application/pdf' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#publication_pdf_link').val(attachment.url);
                    $('#publication_pdf_link').prop('readonly', true);
                    $('#publication_pdf_preview').html('<p><strong>Current PDF:</strong> <a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a></p>');
                });
            }
            mediaUploader.open();
        });
    });
    </script>
    <?php
}

// Save PDF URL as post metadata
function save_publication_pdf($post_id) {
    // Check if it's autosave or revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // Check if it's a "publication" post type
    if (get_post_type($post_id) !== 'publication') {
        return $post_id;
    }

    // Debug: Check if $_POST['publication_pdf'] is populated
    if (isset($_POST['publication_pdf'])) {
        $pdf_url = sanitize_text_field($_POST['publication_pdf']);

        // Debugging: Log PDF URL
        error_log('Saving PDF URL: ' . $pdf_url); // This will log the saved PDF URL in the debug log

        // Check if the PDF URL is not empty and save it
        if (!empty($pdf_url)) {
            update_post_meta($post_id, '_publication_pdf', $pdf_url);
        } else {
            error_log('No PDF URL to save for post ID: ' . $post_id); // Log if the URL is empty
        }
    }

    return $post_id;
}
add_action('save_post_publication', 'save_publication_pdf');

// Enforce PDF upload requirement before publishing the post
function check_publication_pdf_before_publish($post_id) {
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
            $content .= '<div class="publication-pdf"><a href="' . esc_url($pdf_url) . '" target="_blank">Download PDF</a></div>';
        }
    }
    return $content;
}
add_filter('the_content', 'display_publication_pdf');

// Display a custom message on the post edit page when PDF is not uploaded
function publication_publish_warning_message() {
    if (isset($_GET['message']) && $_GET['message'] == 1) {
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $pdf_url = $post_id ? get_post_meta($post_id, '_publication_pdf', true) : '';
        if (empty($pdf_url)) {
            echo '<div class="error"><p><strong>Error:</strong> You must upload a PDF before publishing this Publication.</p></div>';
        }
    }
}
add_action('admin_notices', 'publication_publish_warning_message');

// Disable Gutenberg for 'publication' post type
add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
    if ($post_type === 'publication') {
        return false;
    }
    return $use_block_editor;
}, 10, 2);

// Enqueue media uploader for publication post type
add_action('admin_enqueue_scripts', function($hook) {
    global $post_type;
    if ($post_type === 'publication') {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }
});