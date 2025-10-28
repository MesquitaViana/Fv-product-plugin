<?php
if (!defined('ABSPATH')) exit;

class FVPH_Synchronizer {

    protected static function fetch_wc_products($page=1, $per_page=50){
        $base = rtrim(get_option('fvph_wc_url'),'/');
        $ck = get_option('fvph_wc_key'); $cs = get_option('fvph_wc_secret');
        if(!$base || !$ck || !$cs) return [];
        $url = $base.'/wp-json/wc/v3/products?status=publish&per_page='.intval($per_page).'&page='.intval($page);
        $args = [
            'headers'=>['Authorization'=>'Basic '.base64_encode($ck.':'.$cs)],
            'timeout'=>25
        ];
        $res = wp_remote_get($url,$args);
        if(is_wp_error($res)) return [];
        if(wp_remote_retrieve_response_code($res)!==200) return [];
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return is_array($data) ? $data : [];
    }

    protected static function sideload_all_images($images, $post_id){
        if(empty($images) || !is_array($images)) return [];
        $ids = [];
        foreach($images as $i=>$img){
            if(empty($img['src'])) continue;
            $id = media_sideload_image($img['src'], $post_id, null, 'id');
            if(!is_wp_error($id)){
                $ids[] = (int)$id;
                if($i===0) set_post_thumbnail($post_id, $id);
            }
        }
        if(!empty($ids)){
            update_post_meta($post_id, '_fvph_gallery_ids', $ids);
        }
        return $ids;
    }

    protected static function upsert($p){
        $slug = sanitize_title(!empty($p['slug'])?$p['slug']:$p['name']);
        $exists = get_page_by_path($slug, OBJECT, 'equipamento');

        $postarr = [
            'post_title'  => $p['name'],
            'post_name'   => $slug,
            'post_type'   => 'equipamento',
            'post_status' => 'publish',
            'post_content'=> wp_kses_post($p['description'] ?: $p['short_description'])
        ];
        $post_id = $exists ? $exists->ID : wp_insert_post($postarr);
        if($exists){
            $postarr['ID'] = $exists->ID;
            wp_update_post($postarr);
        }

        if(!empty($p['images'])) self::sideload_all_images($p['images'], $post_id);

        if(isset($p['price'])) update_post_meta($post_id,'_fvph_price',$p['price']);
        if(isset($p['permalink'])) update_post_meta($post_id,'_fvph_buy_url',$p['permalink']);
        if(isset($p['sku'])) update_post_meta($post_id,'_fvph_sku',$p['sku']);

        if(!empty($p['attributes'])){
            foreach($p['attributes'] as $att){
                $name = sanitize_key($att['name']);
                $val  = is_array($att['options']) ? implode(', ', $att['options']) : $att['options'];
                update_post_meta($post_id, '_fvph_attr_'.$name, $val);
                if(in_array(strtolower($att['name']), ['brand','marca'])){
                    $brands = is_array($att['options']) ? $att['options'] : [$att['options']];
                    $brands_slugs = array_map(function($b){ return sanitize_title($b); }, $brands);
                    foreach($brands_slugs as $slug_b){
                        if(!term_exists($slug_b, 'marca_equip')){
                            wp_insert_term(ucwords(str_replace('-', ' ', $slug_b)), 'marca_equip', ['slug'=>$slug_b]);
                        }
                    }
                    wp_set_object_terms($post_id, $brands_slugs, 'marca_equip', false);
                }
            }
        }

        if(!empty($p['categories'])){
            $terms = array_map(function($c){ return sanitize_title($c['name']); }, $p['categories']);
            if(!empty($terms)){
                foreach($terms as $t){
                    if(!term_exists($t, 'categoria_equip')){
                        wp_insert_term(ucwords(str_replace('-', ' ', $t)), 'categoria_equip', ['slug'=>$t]);
                    }
                }
                wp_set_object_terms($post_id, $terms, 'categoria_equip', false);
            }
        }
        return $post_id;
    }

    public static function run(){
        $page = 1;
        do{
            $batch = self::fetch_wc_products($page, 50);
            foreach($batch as $p){ self::upsert($p); }
            $has_more = is_array($batch) && count($batch) === 50;
            $page++;
        } while($has_more);
    }
}
