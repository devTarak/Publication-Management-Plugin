<?php
/**
 * Template for displaying Publication Archive with pagination
 */

get_header(); // Include header

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

// Check if there are any posts to display
if ( have_posts() ) : ?>
    <div class="publication-archive">
        <div class="title-of-publication">
            <h3>Publications</h3>
        </div>
        <ul class="publication-list">
        <?php
        // Loop through publications
        while ( have_posts() ) : the_post();
            ?>
            <li>
            <div class="publication-item">
                <h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
            </div>
            </li>
            <?php
        endwhile;
        ?>
        </ul>
        <!-- Pagination -->
        <div class="pagination">
            <?php
            // Display pagination
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => __( 'Previous', 'publication-management-by-devtarak' ),
                'next_text' => __( 'Next', 'publication-management-by-devtarak' ),
            ) );
            ?>
        </div>
    </div>

<?php else : ?>
    <p><?php esc_html_e( 'No publications found.', 'publication-management-by-devtarak' ); ?></p>
<?php endif; ?>

<?php get_footer(); // Include footer ?>