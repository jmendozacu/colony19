<?php


add_theme_support('post-thumbnails');

//Initialisation du plugin SDW
//add_action('init','colony_get_products');

// Register Custom Navigation Walker
require_once('wp_bootstrap_navwalker.php');

function declarePostType($postType, $postTypeSlug, $singularLabel, $pluralLabel, $description, $menuName, $menu_position, $supports)
{
    $labels = array(
        'name'                  => _x($pluralLabel, 'Post Type General Name', 'sdw-plugins'),
        'singular_name'         => _x($singularLabel, 'Post Type Singular Name', 'sdw-plugins'),
        'menu_name'             => __($menuName, 'sdw-plugins'),
        'name_admin_bar'        => __($pluralLabel, 'sdw-plugins'),
        'archives'              => __('Archives ' . $pluralLabel, 'sdw-plugins'),
        'parent_item_colon'     => __('Parent', 'sdw-plugins'),
        'all_items'             => __('Liste', 'sdw-plugins'),
        'add_new_item'          => __('Ajouter', 'sdw-plugins'),
        'add_new'               => __('Ajouter', 'sdw-plugins'),
        'new_item'              => __('Nouveau', 'sdw-plugins'),
        'edit_item'             => __('Modifier', 'sdw-plugins'),
        'update_item'           => __('Mettre à jour', 'sdw-plugins'),
        'view_item'             => __('Voir', 'sdw-plugins'),
        'search_items'          => __('Rechercher', 'sdw-plugins'),
        'not_found'             => __('Aucun résultat', 'sdw-plugins'),
        'not_found_in_trash'    => __('Aucun résultat dans la corbeille', 'sdw-plugins'),
        'featured_image'        => __('Image', 'sdw-plugins'),
        'set_featured_image'    => __('Définir l\'image', 'sdw-plugins'),
        'remove_featured_image' => __('Retirer l\'image', 'sdw-plugins'),
        'use_featured_image'    => __('Utiliser comme image principale', 'sdw-plugins'),
        'insert_into_item'      => __('Insérer', 'sdw-plugins'),
        'uploaded_to_this_item' => __('Uploaded to this item', 'sdw-plugins'),
        'items_list'            => __('Liste', 'sdw-plugins'),
        'items_list_navigation' => __('Naviguer en liste', 'sdw-plugins'),
        'filter_items_list'     => __('Filtrer la liste', 'sdw-plugins'),
    );
    $args = array(
        'label'               => __($singularLabel, 'sdw-plugins'),
        'description'         => __($description, 'sdw-plugins'),
        'labels'              => $labels,
        'supports'            => $supports,
        'taxonomies'          => array(),
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => $menu_position,
        'menu_icon'           => '',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => $archive,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'page',
        'rewrite'             => array('slug' => $postTypeSlug, 'with_front' => true),
    );
    register_post_type($postType, $args);
}

//Ce sont les images en fondu de la page d'accueil
declarePostType('slideshow', 'slideshows', 'Slideshow', 'Slideshows', 'Image en fondu', 'Slideshows', 20, $supports = array('title', 'thumbnail'), $archive = false);

//Custom post type pour les vidéos
declarePostType('video', 'videos', 'Video', 'Videos', 'Vidéo', 'Videos', 20, $supports = array('title', 'editor'), $archive = 'videos');


function getAllCustomPostType($type)
{
    $query = new WP_query(array(
        'post_type'      => $type,
        'posts_per_page' => '-1'
    ));
    $results = $query->get_posts();

    foreach ($results as $result):

        $original_image = wp_get_attachment_image_src(get_post_thumbnail_id($result->ID), false); // <= false pour accéder à l'image originale directement
        $original_image = $original_image[0];

        $thumb_image = wp_get_attachment_image_src(get_post_thumbnail_id($result->ID)); // <= false pour accéder à l'image originale directement
        $thumb_image = $thumb_image[0];

        $block[$result->post_name] = array(
            'title'     => $result->post_title,
            'date'      => $result->post_date,
            'content'   => $result->post_content,
            'image_url' => $original_image,
            'thumb_url' => $thumb_image

        );
    endforeach;

    return $block;
}


if (function_exists('register_sidebar'))
    register_sidebar(array('name'          => 'Sidebar',
                           'before_widget' => '',
                           'after_widget'  => '',
                           'before_title'  => '<div class="module-title">',
                           'after_title'   => '</div>',
    ));

function register_my_menu()
{
    register_nav_menu('header-menu', __('Header Menu'));
}

add_action('init', 'register_my_menu');

function register_my_menus()
{
    register_nav_menus(
        array(
            'header-menu' => __('Header Menu'),
            'extra-menu'  => __('Extra Menu')
        )
    );
}

add_action('init', 'register_my_menus');

// Fonction pour initialiser les iframes
function add_iframe($initArray)
{
    $initArray['extended_valid_elements'] = "iframe[id|class|title|style|align|frameborder|height|longdesc|marginheight|marginwidth|name|scrolling|src|width]";
    return $initArray;
}

// Ajoute la fonction aux filtres de l'éditeur de texte de WordPress
add_filter('tiny_mce_before_init', 'add_iframe');


// get the "author" role object
$role = get_role( 'author' );
// add "organize_gallery" to this role object
$role->add_cap( 'unfiltered_html' );

?>
