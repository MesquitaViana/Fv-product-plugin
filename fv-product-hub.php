<?php
/**
 * Plugin Name: FV Product Hub
 * Description: Hub de produtos do Fórum do Vapor (Woo sync, cards, Elementor, carrossel, schema, specs).
 * Version: 0.5.2
 * Author: Forum do Vapor
 * License: GPL-2.0+
 * Text Domain: fv-product-hub
 */

if (!defined('ABSPATH')) exit;

define('FVPH_VER',  '0.5.2');
define('FVPH_PATH', plugin_dir_path(__FILE__));
define('FVPH_URL',  plugin_dir_url(__FILE__));

/* -------------------------------------------------
 *  Autoloader: classes FVPH_* => includes/<Class>.php
 * ------------------------------------------------- */
spl_autoload_register(function($class){
    if (strpos($class, 'FVPH_') === 0) {
        $file = FVPH_PATH . 'includes/' . str_replace('FVPH_', '', $class) . '.php';
        if (file_exists($file)) require_once $file;
    }
});

/* ----------------------
 *  Assets (frontend)
 * ---------------------- */
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style ('fvph-style',    FVPH_URL . 'assets/css/style.css', [], FVPH_VER);
    wp_enqueue_script('fvph-carousel', FVPH_URL . 'assets/js/carousel.js', [], FVPH_VER, true);
});

/* ----------------------
 *  Admin (settings/menu)
 * ---------------------- */
add_action('admin_init', ['FVPH_Admin', 'register_settings']);
add_action('admin_menu', ['FVPH_Admin', 'register_menu']);

/* -------------------------------------------
 *  Registro do CPT / Shortcodes / Metaboxes
 * ------------------------------------------- */
add_action('init',              ['FVPH_CPT',        'register_types']);
add_action('init',              ['FVPH_Shortcodes', 'register']);
add_action('add_meta_boxes',    ['FVPH_Metabox',    'register']);
add_action('save_post_produto', ['FVPH_Metabox',    'save']);

/* ------------------------------------------------
 *  Elementor — carregar widgets com segurança
 *  - Só registra quando Elementor estiver ativo
 *  - Libera o CPT 'produto' para edição no Elementor
 * ------------------------------------------------ */
add_action('plugins_loaded', function () {

    // Libera o CPT para o Elementor (opção do plugin)
    add_action('admin_init', function () {
        $supported = get_option('elementor_cpt_support', ['page','post']);
        if (!in_array('produto', $supported, true)) {
            $supported[] = 'produto';
            update_option('elementor_cpt_support', $supported);
        }
    });

    // Garante suporte via post type support (algumas instalações exigem)
    add_action('init', function () {
        if (post_type_exists('produto')) {
            add_post_type_support('produto', 'elementor');
        }
    }, 11);

    // Se Elementor não estiver carregado, não registra widgets
    if (!did_action('elementor/loaded') || !class_exists('\Elementor\Plugin')) {
        return;
    }

    // Registra widgets no momento correto da API do Elementor
    add_action('elementor/widgets/register', function($widgets_manager){

        // Widget de grade antigo (se existir na sua base)
        if (class_exists('FVPH_ElementorWidget')) {
            // Mantém compatibilidade com assinatura atual
            FVPH_ElementorWidget::register_widget($widgets_manager);
        }

        // Novo widget: Catálogo (filtros + busca + paginação)
        $file = FVPH_PATH . 'includes/ElementorCatalog.php';
        if (file_exists($file)) {
            require_once $file; // seguro agora: Elementor carregado
            if (class_exists('FVPH_ElementorCatalog')) {
                $widgets_manager->register( new \FVPH_ElementorCatalog() );
            }
        }
    });
});

/* -------------------------
 *  Cron de sincronização
 * ------------------------- */
add_action('fvph_sync_run', ['FVPH_Synchronizer', 'run']);

register_activation_hook(__FILE__, function(){

    // Registra CPT antes do flush para evitar 404
    if (class_exists('FVPH_CPT')) {
        FVPH_CPT::register_types();
    }

    // Agenda o cron se ainda não existir
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

/* -------------------------------------------------
 *  Template loader (single/lista do CPT 'produto')
 *  - O FVPH_Template deve chamar the_content() no single
 * ------------------------------------------------- */
add_filter('template_include', ['FVPH_Template', 'maybe_load'], 20);
