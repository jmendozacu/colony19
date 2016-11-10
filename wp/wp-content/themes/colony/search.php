<?php
/*
Template Name: Search
*/


get_header(); 

get_template_part('parts/front-header'); 

?>
<div class="row">
    <div class="col-md-12">
        <?php get_template_part('parts/navigation'); ?>         
    </div>
</div>
<?php
$s=get_search_query();
$args = array(
    's' =>$s
);
// The Query
$the_query = new WP_Query( $args );
if ( $the_query->have_posts() ) {
        _e("<div style='font-weight:bold;color:#000'>Search Results for: ".get_query_var('s')."</div>");
        while ( $the_query->have_posts() ) {
           $the_query->the_post();
        ?>
            <p>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </p>
        <?php
        }
    }else{
?>
    <p class="lead">Aucun résultat</p>
<?php } ?>

<div class="row mg-v-20">
    <div class="col-md-12 pd-0">
        <?php 
        $footer_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".footer-container", array('output' => 'html')); 
        echo utf8_decode($footer_colony);

        ?>
    </div>
</div>

<?php get_footer(); ?>