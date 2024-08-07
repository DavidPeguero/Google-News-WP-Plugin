<?php
/*
Plugin Name: google-news-rss-search
Plugin URI: 
Description: Displays Search Form for Google News API and returns according to query   
Author: David Peguero
Version: 1.0.0
Author URI: 
*/

include 'lib\shortcode.php';

function googleRssEnqueueStyles() {
    // Only enqueue the styles if the shortcode is present on the page
    if ( is_singular() && has_shortcode( get_post()->post_content, 'grss' ) ) {
        wp_enqueue_style('google-rss-styles', plugins_url('css/style.css', __FILE__));
    }
}
add_action('wp_enqueue_scripts', 'googleRssEnqueueStyles');