<?php
/**
 * Plugin Name: FV Product Hub
 * Description: Hub de produtos do Fórum do Vapor (Woo sync, cards, Elementor, carrossel, schema, specs).
 * Version: 0.5.0
 * Author: Forum do Vapor
 * License: GPL-2.0+
 * Text Domain: fv-product-hub
 */

if (!defined('ABSPATH')) exit;

define('FVPH_VER', '0.5.0');
define('FVPH_PATH', plugin_dir_path(__FILE__));
define('FVPH_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function($class){
    if(strpos($class, 'FVPH_') === 0){
        $file = FVPH_PATH . 'includes/' . str_replace('FVPH_', '', $class) . '.php';
        if(file_exists($file)) require_once $file;
    }
});

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('fvph-style', FVPH_URL.'assets/css/style.css', [], FVPH_VER);
    wp_enqueue_script('fvph-carousel', FVPH_URL.'assets/js/carousel.js', [], FVPH_VER, true);
});

add_action('admin_init', ['FVPH_Admin', 'register_settings']);
add_action('admin_menu', ['FVPH_Admin', 'register_menu']);

add_action('init', ['FVPH_CPT', 'register_types']);
add_action('init', ['FVPH_Shortcodes', 'register']);
add_action('add_meta_boxes', ['FVPH_Metabox', 'register']);
add_action('save_post_equipamento', ['FVPH_Metabox', 'save']);

// Elementor widget
add_action('elementor/widgets/register', ['FVPH_ElementorWidget', 'register_widget']);

add_action('fvph_sync_run', ['FVPH_Synchronizer', 'run']);
register_activation_hook(__FILE__, function(){
    if(!wp_next_scheduled('fvph_sync_run')){
        wp_schedule_event(time()+60, 'twicedaily', 'fvph_sync_run');
    }
});
register_deactivation_hook(__FILE__, function(){
    $ts = wp_next_scheduled('fvph_sync_run');
    if($ts) wp_unschedule_event($ts, 'fvph_sync_run');
});

// Template loader
add_filter('template_include', ['FVPH_Template', 'maybe_load'], 20);
