<?php
if (!defined('ABSPATH')) exit;

class FVPH_Template {

    public static function maybe_load($template) {

        // === 1. SINGLE PRODUTO ===
        if (is_singular('produto')) {

            // Se Elementor Theme Builder tiver um template de "single" ativo, deixa ele renderizar
            if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('single')) {
                return $template; // Elementor assume o controle
            }

            // Caso contrário, usa nosso template físico
            $tpl = FVPH_PATH . 'templates/single-produto.php';
            if (file_exists($tpl)) return $tpl;

            // Fallback (antigo)
            $tpl_old = FVPH_PATH . 'templates/single-equipamento.php';
            if (file_exists($tpl_old)) return $tpl_old;
        }

        // === 2. ARQUIVO DE PRODUTOS ===
        if (is_post_type_archive('produto')) {

            // Se Elementor Theme Builder tiver um template de "archive" ativo, respeita ele
            if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('archive')) {
                return $template; // Elementor assume
            }

            $tpl = FVPH_PATH . 'templates/archive-produto.php';
            if (file_exists($tpl)) return $tpl;

            $tpl_old = FVPH_PATH . 'templates/archive-equipamento.php';
            if (file_exists($tpl_old)) return $tpl_old;
        }

        // === 3. Default (nenhum match) ===
        return $template;
    }
}
