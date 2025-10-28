<?php
if (!defined('ABSPATH')) exit;

class FVPH_Synchronizer {

    // Ajuste aqui se seus slugs reais forem outros
    const TAX_MARCA   = 'marca_prod';        // ex.: 'marca'
    const TAX_CATEG   = 'categoria_prod';    // ex.: 'categoria_produto'
    const PER_PAGE    = 50;

    /**
     * Busca produtos da API WooCommerce (autenticação por querystring).
     */
    protected static function fetch_wc_products($page = 1, $per_page = self::PER_PAGE){
        $base = rtrim(get_option('fvph_wc_url'), '/');
        $ck   = trim((string) get_option('fvph_wc_key'));
        $cs   = trim((string) get_option('fvph_wc_secret'));

        if (!$base || !$ck || !$cs) {
            error_log('[FVPH] Config ausente: URL/CK/CS');
            return [];
        }

        $url = add_query_arg([
            'status'          => 'publish',
            'per_page'        => (int) $per_page,
            'page'            => (int) $page,
            'consumer_key'    => $ck,
            'consumer_secret' => $cs,
        ], $base . '/wp-json/wc/v3/products');

        $args = [
            'timeout' => 30,
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => 'FVProductHub/0.6 (' . home_url() . ')'
            ]
        ];

        // tentativa 1
        $res = wp_remote_get($url, $args);
        $data = self::parse_response($res);
        if ($data !== null) return $data;

        // backoff simples para 429/503
        $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
        if (in_array($code, [429, 503], true)) {
            $retryAfter = (int) wp_remote_retrieve_header($res, 'retry-after');
            if ($retryAfter > 0 && $retryAfter <= 10) {
                sleep($retryAfter);
            } else {
                usleep(400000); // 0.4s
            }
            $res2 = wp_remote_get($url, $args);
            $data2 = self::parse_response($res2);
            if ($data2 !== null) return $data2;
        }

        return [];
    }

    /**
     * Interpreta a resposta HTTP e devolve array ou null quando falha.
     */
    protected static function parse_response($res){
        if (is_wp_error($res)) {
            error_log('[FVPH] fetch_wc_products WP_Error: ' . $res->get_error_message());
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);

        if ($code !== 200) {
            error_log('[FVPH] fetch_wc_products HTTP ' . $code . ' - Body: ' . substr($body, 0, 500));
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            error_log('[FVPH] fetch_wc_products JSON inválido: ' . substr($body, 0, 500));
            return null;
        }
        return $data;
    }

    /**
     * Faz sideload de todas as imagens; define a primeira como thumbnail.
     * Evita erros quando media_* ainda não está incluso (cron/CLI).
     */
    protected static function sideload_all_images($images, $post_id){
        if (empty($images) || !is_array($images)) return [];

        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $ids = [];
        foreach ($images as $i => $img) {
            $src = isset($img['src']) ? trim($img['src']) : '';
            if ($src === '') continue;

            // opcional: meta para evitar rebaixar mesma URL
            $hash = md5($src);
            $existing = self::find_image_by_hash($post_id, $hash);
            if ($existing) {
                $ids[] = $existing;
                if ($i === 0) set_post_thumbnail($post_id, $existing);
                continue;
            }

            $id = media_sideload_image($src, $post_id, null, 'id');
            if (!is_wp_error($id)) {
                $ids[] = (int) $id;
                update_post_meta($id, '_fvph_img_hash', $hash);
                if ($i === 0) set_post_thumbnail($post_id, $id);
            } else {
                error_log('[FVPH] media_sideload_image erro: ' . $id->get_error_message() . ' - ' . $src);
            }
        }
        if (!empty($ids)) {
            update_post_meta($post_id, '_fvph_gallery_ids', array_map('intval', $ids));
        }
        return $ids;
    }

    /**
     * Procura no post galerias/imagem destacada por um anexo com o mesmo hash de origem.
     */
    protected static function find_image_by_hash($post_id, $hash){
        $thumb_id = (int) get_post_thumbnail_id($post_id);
        if ($thumb_id && get_post_meta($thumb_id, '_fvph_img_hash', true) === $hash) {
            return $thumb_id;
        }
        $g = get_post_meta($post_id, '_fvph_gallery_ids', true);
        if (is_array($g)) {
            foreach ($g as $aid) {
                $aid = (int) $aid;
                if ($aid && get_post_meta($aid, '_fvph_img_hash', true) === $hash) {
                    return $aid;
                }
            }
        }
        return 0;
    }

    /**
     * Cria/atualiza um post do tipo 'produto' com dados do Woo.
     * Respeita o flag _fvph_manual=1 para não sobrescrever.
     */
    protected static function upsert($p){
        $name = isset($p['name']) ? wp_strip_all_tags($p['name']) : '';
        if ($name === '') return 0;

        $slug = sanitize_title(!empty($p['slug']) ? $p['slug'] : $name);
        $exists = get_page_by_path($slug, OBJECT, 'produto');

        $content = '';
        if (!empty($p['description'])) {
            $content = wp_kses_post($p['description']);
        } elseif (!empty($p['short_description'])) {
            $content = wp_kses_post($p['short_description']);
        }

        $postarr = [
            'post_title'   => $name,
            'post_name'    => $slug,
            'post_type'    => 'produto',
            'post_status'  => 'publish',
            'post_content' => $content,
        ];

        if ($exists && $exists->ID) {
            $postarr['ID'] = (int) $exists->ID;
            $post_id = wp_update_post($postarr, true);
            if (is_wp_error($post_id)) {
                error_log('[FVPH] wp_update_post erro: ' . $post_id->get_error_message());
                return 0;
            }
        } else {
            $post_id = wp_insert_post($postarr, true);
            if (is_wp_error($post_id)) {
                error_log('[FVPH] wp_insert_post erro: ' . $post_id->get_error_message());
                return 0;
            }
        }

        // se marcado como manual, não sobrescreve nada
        $skip = get_post_meta($post_id, '_fvph_manual', true) === '1';
        if ($skip) return $post_id;

        // imagens
        if (!empty($p['images'])) {
            self::sideload_all_images($p['images'], $post_id);
        }

        // metas básicas
        if (isset($p['price']))     update_post_meta($post_id, '_fvph_price', (string) $p['price']);
        if (isset($p['permalink'])) update_post_meta($post_id, '_fvph_buy_url', esc_url_raw($p['permalink']));
        if (isset($p['sku']))       update_post_meta($post_id, '_fvph_sku', sanitize_text_field($p['sku']));

        // atributos
        if (!empty($p['attributes']) && is_array($p['attributes'])) {
            foreach ($p['attributes'] as $att) {
                $att_name = isset($att['name']) ? (string) $att['name'] : '';
                $key = sanitize_key($att_name);
                $val = '';
                if (isset($att['options'])) {
                    $val = is_array($att['options']) ? implode(', ', array_map('wp_strip_all_tags', $att['options'])) : wp_strip_all_tags((string) $att['options']);
                }
                if ($key) {
                    update_post_meta($post_id, '_fvph_attr_' . $key, $val);
                }

                // marca/brand → taxonomia
                $lname = strtolower($att_name);
                if ($lname === 'brand' || $lname === 'marca') {
                    $brands = [];
                    if (isset($att['options'])) {
                        $brands = is_array($att['options']) ? $att['options'] : [$att['options']];
                    }
                    $brand_slugs = array_filter(array_map(function($b){
                        return sanitize_title((string) $b);
                    }, $brands));

                    if (!empty($brand_slugs)) {
                        foreach ($brand_slugs as $bslug) {
                            if (!term_exists($bslug, self::TAX_MARCA)) {
                                wp_insert_term(ucwords(str_replace('-', ' ', $bslug)), self::TAX_MARCA, ['slug' => $bslug]);
                            }
                        }
                        wp_set_object_terms($post_id, $brand_slugs, self::TAX_MARCA, false);
                    }
                }
            }
        }

        // categorias
        if (!empty($p['categories']) && is_array($p['categories'])) {
            $terms = array_filter(array_map(function($c){
                $n = isset($c['name']) ? (string) $c['name'] : '';
                return $n ? sanitize_title($n) : '';
            }, $p['categories']));

            if (!empty($terms)) {
                foreach ($terms as $t) {
                    if (!term_exists($t, self::TAX_CATEG)) {
                        wp_insert_term(ucwords(str_replace('-', ' ', $t)), self::TAX_CATEG, ['slug' => $t]);
                    }
                }
                wp_set_object_terms($post_id, $terms, self::TAX_CATEG, false);
            }
        }

        return $post_id;
    }

    /**
     * Loop de sincronização com paginação.
     */
    public static function run(){
        $page = 1;
        do {
            $batch = self::fetch_wc_products($page, self::PER_PAGE);
            if (!is_array($batch) || empty($batch)) {
                // se na primeira página já veio vazio, não há o que fazer
                if ($page === 1) {
                    error_log('[FVPH] Nenhum produto retornado pela API.');
                }
                break;
            }
            foreach ($batch as $p) {
                self::upsert($p);
            }
            $has_more = count($batch) === self::PER_PAGE;
            $page++;
        } while ($has_more);
    }
}
