<?php
/**
 * Plugin Name: FV Product Hub
 * Description: Hub de produtos do Fórum do Vapor (Woo sync, cards, Elementor, carrossel, schema, specs).
 * Version: 0.5.1
 * Author: Forum do Vapor
 * License: GPL-2.0+
 * Text Domain: fv-product-hub
 */

if (!defined('ABSPATH')) exit;

define('FVPH_VER',  '0.5.1');
define('FVPH_PATH', plugin_dir_path(__FILE__));
define('FVPH_URL',  plugin_dir_url(__FILE__));

/** Autoloader: classes FVPH_* -> includes/<Class>.php */
spl_autoload_register(function($class){
    if (strpos($class, 'FVPH_') === 0) {
        $file = FVPH_PATH . 'includes/' . str_replace('FVPH_', '', $class) . '.php';
        if (file_exists($file)) require_once $file;
    }
});

/** Assets de front */
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style ('fvph-style',    FVPH_URL . 'assets/css/style.css', [], FVPH_VER);
    wp_enqueue_script('fvph-carousel', FVPH_URL . 'assets/js/carousel.js', [], FVPH_VER, true);
});

/** Admin (settings/menu) */
add_action('admin_init', ['FVPH_Admin', 'register_settings']);
add_action('admin_menu', ['FVPH_Admin', 'register_menu']);

/** Registro do CPT/shortcodes/metabox */
add_action('init',               ['FVPH_CPT',        'register_types']);
add_action('init',               ['FVPH_Shortcodes', 'register']);
add_action('add_meta_boxes',     ['FVPH_Metabox',    'register']);
add_action('save_post_produto',  ['FVPH_Metabox',    'save']);

/** Elementor (carrega apenas se Elementor estiver ativo) */
/** Elementor (carrega só depois do Elementor e registra dentro do hook certo) */
add_action('plugins_loaded', function () {
    if (!did_action('elementor/loaded') || !class_exists('\Elementor\Plugin')) {
        return; // Elementor não está ativo/carregado
    }

    // Registra widgets dentro do hook do Elementor
    add_action('elementor/widgets/register', function($widgets_manager){

        // Widget de grade antigo (se existir)
        if (class_exists('FVPH_ElementorWidget')) {
            // aceita método estático ou instância; mantenho a assinatura que você usa
            FVPH_ElementorWidget::register_widget($widgets_manager);
        }

        // Novo widget: Catálogo (filtros + busca + paginação)
        $file = FVPH_PATH . 'includes/ElementorCatalog.php';
        if (file_exists($file)) {
            require_once $file; // agora é seguro: Elementor já está carregado
            if (class_exists('FVPH_ElementorCatalog')) {
                // registra via instância (API moderna do Elementor)
                $widgets_manager->register( new \FVPH_ElementorCatalog() );
            }
        }
    });
});



/** Cron de sincronização */
add_action('fvph_sync_run', ['FVPH_Synchronizer', 'run']);

register_activation_hook(__FILE__, function(){
    // registra CPT antes do flush (evita 404 ao ativar)
    if (class_exists('FVPH_CPT')) {
        FVPH_CPT::register_types();
    }
    if (!wp_next_scheduled('fvph_sync_run')) {
        wp_schedule_event(time()+60, 'twicedaily', 'fvph_sync_run');
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function(){
    if ($ts = wp_next_scheduled('fvph_sync_run')) {
        wp_unschedule_event($ts, 'fvph_sync_run');
    }
    flush_rewrite_rules();
});

/** Template loader (single/lista do CPT) */
add_filter('template_include', ['FVPH_Template', 'maybe_load'], 20);
