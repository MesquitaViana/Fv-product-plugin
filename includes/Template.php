<?php
if (!defined('ABSPATH')) exit;

class FVPH_Template {
    public static function maybe_load($template){

        // SINGLE
        if (is_singular('produto')) {
            // Preferir novo nome:
            $tpl = FVPH_PATH.'templates/single-produto.php';
            if (file_exists($tpl)) return $tpl;
            // Fallback para antigo:
            $tpl_old = FVPH_PATH.'templates/single-equipamento.php';
            if (file_exists($tpl_old)) return $tpl_old;
        }

        // ARCHIVE
        if (is_post_type_archive('produto')) {
            $tpl = FVPH_PATH.'templates/archive-produto.php';
            if (file_exists($tpl)) return $tpl;
            $tpl_old = FVPH_PATH.'templates/archive-equipamento.php';
            if (file_exists($tpl_old)) return $tpl_old;
        }

        return $template;
    }
}
