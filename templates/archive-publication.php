<?php
/**
 * Template for displaying Publication Archive with pagination
 */
get_header();
 // Include header

if (have_posts()) : ?>
    <div class="publication-archive">
        <div class="Titleofpublication"><h3>Publications</h3></div>
        <?php
        // Loop through publications
        while (have_posts()) : the_post();
            ?>
            <div class="publication-item">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            </div>
            <?php
        endwhile;
        ?>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('Previous'),
                'next_text' => __('Next'),
            ));
            ?>
        </div>
    </div>

<?php else : ?>
    <p>No publications found.</p>
<?php
endif;

get_footer(); // Include footer
?>