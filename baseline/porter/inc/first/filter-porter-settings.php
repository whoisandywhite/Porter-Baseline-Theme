<?php 



// Add support for the author field to the post type
add_filter( 'porter_default_object_args', function( $args, $group ) {
    if ( 'posttype' === $group ) {
        $args['supports'][] = 'author';
        $args['supports'][] = 'revisions';
    }
    return $args;
}, 10, 2 );
