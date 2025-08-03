<?php
/**
 * Admin-specific functionality for the plugin, such as adding meta boxes.
 */
// Add PDF Meta Box for 'publication' post type
function pmdbt_add_publication_pdf_meta_box() {
    add_meta_box(
        'publication_pdf',
        __('Upload PDF', 'publication-management-by-devtarak'),
        'pmdbt_publication_pdf_meta_box_callback',
        'publication',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'pmdbt_add_publication_pdf_meta_box');

// Meta box callback to display the PDF upload field
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
// Enqueue media uploader for publication post type
add_action('admin_enqueue_scripts', function($hook) {
    global $post_type;
    if ($post_type === 'publication') {
        wp_enqueue_media();  // Enqueue the WordPress media uploader
        wp_enqueue_script('jquery');  // Ensure jQuery is loaded
    }
});
