<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Register the 'Publication' custom post type
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
            'not_found_in_trash' => __('No Publications found in Trash', 'publication-management-by-devtarak'),
            'all_items' => __('All Publications', 'publication-management-by-devtarak'),
            'menu_name' => __('Publications', 'publication-management-by-devtarak'),
            'name_admin_bar' => __('Publication', 'publication-management-by-devtarak'),
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

// Register Meta Box for PDF Upload
function pmdbt_add_publication_pdf_meta_box() {
    add_meta_box(
        'publication_pdf',
        __('Upload PDF', 'publication-management-by-devtarak'),
        'pmdbt_publication_pdf_meta_box_callback',
        'publication',
        'normal',
        'high',
        array('__back_compat_meta_box' => true)
    );
}
add_action('add_meta_boxes', 'pmdbt_add_publication_pdf_meta_box');

// Meta Box Callback to display the PDF upload option (for editing)
function pmdbt_publication_pdf_meta_box_callback($post) {
    $pdf_url = get_post_meta($post->ID, '_publication_pdf', true);
    ?>
    <label for="publication_pdf_link"><?php esc_html_e('Select PDF:', 'publication-management-by-devtarak'); ?></label>
    <input type="text" id="publication_pdf_link" name="publication_pdf" value="<?php echo esc_attr($pdf_url); ?>" style="width:80%;"/>
    <button type="button" class="button" id="publication_pdf_button"><?php esc_html_e('Select PDF', 'publication-management-by-devtarak'); ?></button>
    <div id="publication_pdf_preview">
        <?php if ($pdf_url) : ?>
            <p><strong><?php esc_html_e('Current PDF:', 'publication-management-by-devtarak'); ?></strong> <a href="<?php echo esc_url($pdf_url); ?>" target="_blank"><?php echo esc_html(basename($pdf_url)); ?></a></p>
        <?php else : ?>
            <p><?php esc_html_e('No PDF uploaded yet.', 'publication-management-by-devtarak'); ?></p>
        <?php endif; ?>
    </div>
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        $('#publication_pdf_button').click(function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: '<?php echo esc_js(__('Select or Upload PDF', 'publication-management-by-devtarak')); ?>',
                button: {
                    text: '<?php echo esc_js(__('Use this PDF', 'publication-management-by-devtarak')); ?>'
                },
                library: {
                    type: 'application/pdf'
                },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#publication_pdf_link').val(attachment.url);
                $('#publication_pdf_preview').html('<p><strong><?php echo esc_js(__('Current PDF:', 'publication-management-by-devtarak')); ?></strong> <a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a></p>');
            });
            mediaUploader.open();
        });
    });
    </script>
    <?php
}

// Save PDF URL as post metadata
function pmdbt_save_publication_pdf($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if (get_post_type($post_id) !== 'publication') {
        return $post_id;
    }
    if (isset($_POST['publication_pdf'])) {
        $pdf_url = sanitize_text_field($_POST['publication_pdf']);
        update_post_meta($post_id, '_publication_pdf', $pdf_url);
    }
    return $post_id;
}
add_action('save_post_publication', 'pmdbt_save_publication_pdf');

// Enforce PDF upload requirement before publishing the post
function pmdbt_check_publication_pdf_before_publish($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if (get_post_type($post_id) !== 'publication') {
        return $post_id;
    }
    if (isset($_POST['post_status']) && $_POST['post_status'] === 'publish') {
        $pdf_url = get_post_meta($post_id, '_publication_pdf', true);
        if (empty($pdf_url)) {
            // Prevent publishing and show admin notice
            remove_action('save_post', 'pmdbt_check_publication_pdf_before_publish');
            wp_update_post(array(
                'ID' => $post_id,
                'post_status' => 'draft'
            ));
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('pmdbt_pdf_missing', 1, $location);
            });
        }
    }
    return $post_id;
}
add_action('save_post', 'pmdbt_check_publication_pdf_before_publish');

// Display PDF link on the frontend
function pmdbt_display_publication_pdf($content) {
    if (is_singular('publication')) {
        $pdf_url = get_post_meta(get_the_ID(), '_publication_pdf', true);
        if ($pdf_url) {
            $content .= '
                <div class="publication-pdf">
                    <iframe src="' . esc_url($pdf_url) . '" width="100%" height="1000px" style="border:1px solid #ccc; margin-top:10px; margin-bottom:50px"></iframe>
                </div>
            ';
        }
    }
    return $content;
}
add_filter('the_content', 'pmdbt_display_publication_pdf');

// Display a custom message on the post edit page when PDF is not uploaded
function pmdbt_publication_publish_warning_message() {
    if (isset($_GET['pmdbt_pdf_missing']) && $_GET['pmdbt_pdf_missing'] == 1) {
        echo '<div class="notice notice-error"><p>' . esc_html__('You must upload a PDF before publishing this publication.', 'publication-management-by-devtarak') . '</p></div>';
    }
}
add_action('admin_notices', 'pmdbt_publication_publish_warning_message');

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
    }
});

// Modify the query for the publication archive page for pagination
function pmdbt_modify_publication_archive_query($query) {
    if (is_post_type_archive('publication') && $query->is_main_query()) {
        $query->set('posts_per_page', 20);
    }
}
add_action('pre_get_posts', 'pmdbt_modify_publication_archive_query');

// Load custom archive template for 'publication' post type
function pmdbt_load_custom_publication_archive_template($template) {
    if (is_post_type_archive('publication')) {
        $plugin_template = plugin_dir_path(__FILE__, 2) . 'templates/archive-publication.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter('template_include', 'pmdbt_load_custom_publication_archive_template');