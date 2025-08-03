<?php
/**
 * Meta boxes for the 'Publication' post type
 */

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