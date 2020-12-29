<?php

namespace App;

/**
 * Add <body> classes
 */
add_filter('body_class', function (array $classes) {
    /** Add page slug if it doesn't exist */
    if (is_single() || is_page() && !is_front_page()) {
        if (!in_array(basename(get_permalink()), $classes)) {
            $classes[] = basename(get_permalink());
        }
    }

    /** Add class if sidebar is active */
    if (display_sidebar()) {
        $classes[] = 'sidebar-primary';
    }

    /** Clean up class names for custom templates */
    $classes = array_map(function ($class) {
        return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
    }, $classes);

    return array_filter($classes);
});

/**
 * Add "… Continued" to the excerpt
 */
add_filter('excerpt_more', function () {
    return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
});

/**
 * Template Hierarchy should search for .blade.php files
 */
collect([
    'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
    'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment', 'embed'
])->map(function ($type) {
    add_filter("{$type}_template_hierarchy", __NAMESPACE__.'\\filter_templates');
});

/**
 * Render page using Blade
 */
add_filter('template_include', function ($template) {
    collect(['get_header', 'wp_head'])->each(function ($tag) {
        ob_start();
        do_action($tag);
        $output = ob_get_clean();
        remove_all_actions($tag);
        add_action($tag, function () use ($output) {
            echo $output;
        });
    });
    $data = collect(get_body_class())->reduce(function ($data, $class) use ($template) {
        return apply_filters("sage/template/{$class}/data", $data, $template);
    }, []);
    if ($template) {
        echo template($template, $data);
        return get_stylesheet_directory().'/index.php';
    }
    return $template;
}, PHP_INT_MAX);

/**
 * Render comments.blade.php
 */
add_filter('comments_template', function ($comments_template) {
    $comments_template = str_replace(
        [get_stylesheet_directory(), get_template_directory()],
        '',
        $comments_template
    );

    $data = collect(get_body_class())->reduce(function ($data, $class) use ($comments_template) {
        return apply_filters("sage/template/{$class}/data", $data, $comments_template);
    }, []);

    $theme_template = locate_template(["views/{$comments_template}", $comments_template]);

    if ($theme_template) {
        echo template($theme_template, $data);
        return get_stylesheet_directory().'/index.php';
    }

    return $comments_template;
}, 100);


/**
 * Use Lozad (lazy loading) for attachments/featured images
 */
// add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment) {
//     // Bail on admin
//     if (is_admin()) {
//         return $attr;
//     }

//     $attr['data-src'] = $attr['src'];
//     $attr['class'] .= ' lozad';
//     unset($attr['src']);

//     return $attr;
// }, 10, 2);

// contain all native gtunenberg blocks
// add_filter( 'render_block', function ( $block_content, $block ) {
//     $blocks = [
//         'archives',
//         'audio',
//         'button',
//         'categories',
//         'code',
//         'column',
//         'columns',
//         'coverImage',
//         'embed',
//         'file',
//         'freeform',
//         'gallery',
//         'heading',
//         'image',
//         'latestComments',
//         'latestPosts',
//         'list',
//         'more',
//         'nextpage',
//         'paragraph',
//         'preformatted',
//         'pullquote',
//         'quote',
//         'reusableBlock',
//         'separator',
//         'shortcode',
//         'spacer',
//         'subhead',
//         'table',
//         'textColumns',
//         'verse',
//         'video',
//     ]; 
//     foreach($blocks as $b) {
//         if ( $block['blockName'] === 'core/' . $b ) {
//             $block_content = '<div class="container">' . $block_content . '</div>';
//         }
//     }
//   return $block_content;
// }, 10, 2 );

// defer javascript
add_filter( 'script_loader_tag', function( $url ) {
    if ( is_user_logged_in() ) return $url; //don't break WP Admin
    if ( FALSE === strpos( $url, '.js' ) ) return $url;
    if ( strpos( $url, 'jquery.js' ) ) return $url;
    return str_replace( ' src', ' defer src', $url );
}, 10 );

// remove default editor styles
add_filter( 'block_editor_settings' , function( $settings ) {
    unset($settings['styles'][0]);
    return $settings;
} );