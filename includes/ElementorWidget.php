<?php
if (!defined('ABSPATH')) exit;

class FVPH_ElementorWidget {
    public static function register_widget( $widgets_manager ){
        if( ! class_exists('\Elementor\\Widget_Base') ) return;
        require_once FVPH_PATH . 'includes/Elementor_Widget_Products.php';
        $widgets_manager->register( new \FVPH_Elementor_Widget_Products() );
    }
}
