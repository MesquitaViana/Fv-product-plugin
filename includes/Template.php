<?php
if (!defined('ABSPATH')) exit;

class FVPH_Template {
    public static function maybe_load($template){
        if(is_singular('equipamento')){
            $tpl = FVPH_PATH.'templates/single-equipamento.php';
            if(file_exists($tpl)) return $tpl;
        }
        if(is_post_type_archive('equipamento')){
            $tpl = FVPH_PATH.'templates/archive-equipamento.php';
            if(file_exists($tpl)) return $tpl;
        }
        return $template;
    }
}
