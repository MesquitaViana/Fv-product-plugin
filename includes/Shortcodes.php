<?php
if (!defined('ABSPATH')) exit;

class FVPH_Shortcodes {

    public static function register(){
        // já existentes
        add_shortcode('fv_products',   [__CLASS__, 'products']);
        add_shortcode('fvph_catalog',  [__CLASS__, 'catalog']);

        // novos utilitários de compra
        add_shortcode('fvph_buy_url',   [__CLASS__, 'buy_url']);    // URL com UTM opcional
        add_shortcode('fvph_buy_label', [__CLASS__, 'buy_label']);  // Label/fallback

        // novos para Elementor/dinâmicos
        add_shortcode('fvph_title',         [__CLASS__, 'title_sc']);
        add_shortcode('fvph_price',         [__CLASS__, 'price_sc']);
        add_shortcode('fvph_partner_logo',  [__CLASS__, 'partner_logo_sc']);
        add_shortcode('fvph_image',         [__CLASS__, 'image_sc']);
        add_shortcode('fvph_attr',          [__CLASS__, 'attr_sc']);
    }

    /* ---------------------------------------------
     * Helpers internos
     * -------------------------------------------*/

    // ===== Helper robusto: aceita id, slug e valida post atual =====
    protected static function resolve_post_id($atts){
        // prioridade: id -> slug -> post atual (se for 'produto')
        if (!empty($atts['id'])) {
            $pid = absint($atts['id']);
            if ($pid) return $pid;
        }
        if (!empty($atts['slug'])) {
            $p = get_page_by_path(sanitize_title($atts['slug']), OBJECT, 'produto');
            if ($p && !is_wp_error($p)) return (int) $p->ID;
        }
        $pid = get_the_ID();
        if ($pid && get_post_type($pid) === 'produto') return (int) $pid;
        return 0;
    }

    /* =========================================================
     *  [fvph_buy_url] e [fvph_buy_label]
     * =======================================================*/

    /**
     * [fvph_buy_url id="" slug="" utm="1" source="forumdovapor" medium="referral" campaign="produto"]
     * Retorna a URL de compra (_fvph_buy_url) com UTM anexada (opcional).
     */
    public static function buy_url($atts = []){
        $a = shortcode_atts([
            'id'       => '',
            'slug'     => '',
            'utm'      => '1',               // "1" liga UTM, "0" desliga
            'source'   => 'forumdovapor',
            'medium'   => 'referral',
            'campaign' => 'produto',
        ], $atts, 'fvph_buy_url');

        $post_id = self::resolve_post_id($a);
        if (!$post_id) return '';

        $url = get_post_meta($post_id, '_fvph_buy_url', true);
        if (!$url) return '';

        if ($a['utm'] === '1') {
            $url = add_query_arg([
                'utm_source'   => sanitize_title($a['source']),
                'utm_medium'   => sanitize_title($a['medium']),
                'utm_campaign' => sanitize_title($a['campaign']),
            ], $url);
        }
        return esc_url($url);
    }

    /**
     * [fvph_buy_label id="" slug="" fallback="Comprar"]
     * Retorna _fvph_buy_label, senão "Comprar na {parceiro}", senão fallback.
     */
    public static function buy_label($atts = []){
        $a = shortcode_atts([
            'id'       => '',
            'slug'     => '',
            'fallback' => 'Comprar',
        ], $atts, 'fvph_buy_label');

        $post_id = self::resolve_post_id($a);
        if (!$post_id) return esc_html($a['fallback']);

        $label   = get_post_meta($post_id, '_fvph_buy_label', true);
        $partner = get_post_meta($post_id, '_fvph_partner_name', true);

        if (!empty($label))   return esc_html($label);
        if (!empty($partner)) return 'Comprar na ' . esc_html($partner);
        return esc_html($a['fallback']);
    }

    /* =========================================================
     *  SHORTCODE ANTIGO (mantido): [fv_products]
     * =======================================================*/
    protected static function build_query_args($a){
        $args = [
            'post_type'      => 'produto',
            'posts_per_page' => intval($a['limit']),
            'tax_query'      => [],
        ];
        if(!empty($a['category'])){
            $args['tax_query'][] = [
                'taxonomy'=>'categoria_prod',
                'field'   =>'slug',
                'terms'   => sanitize_title($a['category'])
            ];
        }
        if(!empty($a['brand'])){
            $args['tax_query'][] = [
                'taxonomy'=>'marca_prod',
                'field'   =>'slug',
                'terms'   => sanitize_title($a['brand'])
            ];
        }
        $order_by = strtolower($a['order_by']);
        $order    = ($a['order'] === 'ASC') ? 'ASC' : 'DESC';

        if($order_by === 'title'){
            $args['orderby'] = 'title';
            $args['order']   = $order;
        } elseif($order_by === 'rating'){
            $args['meta_key']= '_fvph_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order']   = $order;
        } elseif($order_by === 'sticky_first'){
            $args['meta_key']= '_fvph_sticky';
            $args['orderby'] = 'meta_value_num date';
            $args['order']   = $order;
        } else {
            $args['orderby'] = 'date';
            $args['order']   = $order;
        }
        return $args;
    }

    public static function products($atts){
        $a = shortcode_atts([
            'category'   => '',
            'brand'      => '',
            'limit'      => 12,
            'order'      => 'DESC',
            'order_by'   => 'date',
            'view_label' => 'Ver mais'
        ], $atts, 'fv_products');

        $args = self::build_query_args($a);
        $q = new WP_Query($args);

        ob_start();
        echo '<div class="fv-grid">';
        while($q->have_posts()){ $q->the_post();
            $price  = get_post_meta(get_the_ID(), '_fvph_price', true);
            $buy    = get_post_meta(get_the_ID(), '_fvph_buy_url', true);
            $puffs  = get_post_meta(get_the_ID(), '_fvph_attr_puffs', true);
            $nic    = get_post_meta(get_the_ID(), '_fvph_attr_nicotina', true);
            $bat    = get_post_meta(get_the_ID(), '_fvph_attr_bateria', true);
            $rating = get_post_meta(get_the_ID(), '_fvph_rating', true);
            ?>
            <article class="fv-card">
              <a class="fv-thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
              <h3 class="fv-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <div class="fv-chips">
                <?php if($puffs) echo '<span class="fv-chip">'.esc_html($puffs).' puffs</span>'; ?>
                <?php if($nic)   echo '<span class="fv-chip">'.esc_html($nic).' mg</span>'; ?>
                <?php if($bat)   echo '<span class="fv-chip">'.esc_html($bat).' mAh</span>'; ?>
                <?php if($rating!=='') echo '<span class="fv-chip">★ '.esc_html($rating).'</span>'; ?>
              </div>
              <?php if($price){ ?><div class="fv-price">R$ <?php echo esc_html($price); ?></div><?php } ?>
              <div class="fv-actions">
                <a class="fv-btn fv-btn-outline" href="<?php the_permalink(); ?>"><?php echo esc_html($a['view_label']); ?></a>
                <?php if($buy){ ?><a class="fv-btn" href="<?php echo esc_url($buy); ?>" target="_blank" rel="nofollow sponsored noopener">Comprar</a><?php } ?>
              </div>
            </article>
            <?php
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    /* =========================================================
     *  NOVO SHORTCODE: [fvph_catalog]
     *  Filtros responsivos + busca + paginação + grade 3x5
     * =======================================================*/
    public static function catalog($atts = []){
        $atts = shortcode_atts([
            'per_page' => 15,
        ], $atts, 'fvph_catalog');

        $per_page = max(1, (int)$atts['per_page']);

        $tax_marca = class_exists('FVPH_Synchronizer') ? FVPH_Synchronizer::TAX_MARCA : 'marca_prod';
        $tax_tipo  = class_exists('FVPH_Synchronizer') ? FVPH_Synchronizer::TAX_CATEG : 'categoria_prod';

        $q     = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $marca = (isset($_GET['marca']) && is_array($_GET['marca'])) ? array_map('sanitize_title', $_GET['marca']) : [];
        $tipo  = (isset($_GET['tipo'])  && is_array($_GET['tipo']))  ? array_map('sanitize_title', $_GET['tipo'])  : [];
        $subs  = (isset($_GET['subs'])  && is_array($_GET['subs']))  ? array_map('sanitize_text_field', $_GET['subs']) : [];
        $paged = isset($_GET['pag']) ? max(1, (int)$_GET['pag']) : max(1, get_query_var('paged', 1));

        $tax_query = ['relation' => 'AND'];
        if ($marca) $tax_query[] = ['taxonomy'=>$tax_marca,'field'=>'slug','terms'=>$marca];
        if ($tipo)  $tax_query[] = ['taxonomy'=>$tax_tipo, 'field'=>'slug','terms'=>$tipo];

        $meta_query = ['relation' => 'AND'];
        if ($subs)  $meta_query[] = ['key'=>'_fvph_attr_substancia','value'=>$subs,'compare'=>'IN'];

        $args = [
            'post_type'      => 'produto',
            'post_status'    => 'publish',
            's'              => $q,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if (count($tax_query)  > 1) $args['tax_query']  = $tax_query;
        if (count($meta_query) > 1) $args['meta_query'] = $meta_query;

        $q_products = new WP_Query($args);

        $terms_marca = get_terms(['taxonomy'=>$tax_marca,'hide_empty'=>true,'number'=>1000]);
        if (is_wp_error($terms_marca)) $terms_marca = [];
        $terms_tipo  = get_terms(['taxonomy'=>$tax_tipo ,'hide_empty'=>true,'number'=>1000]);
        if (is_wp_error($terms_tipo)) $terms_tipo = [];

        global $wpdb;
        $subs_values = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status='publish'
             ORDER BY pm.meta_value ASC",
            '_fvph_attr_substancia','produto'
        ));
        // normaliza: remove vazios, trim, reindexa
        $subs_values = array_values(array_filter(array_map('trim', (array)$subs_values)));

        ob_start(); ?>
        <div class="fvph-catalog">
          <aside class="fvph-filters">
            <div class="fvph-filters__header">
              <strong><?php echo esc_html(number_format_i18n((int)$q_products->found_posts)); ?></strong> produtos
              <a class="fvph-filters__reset" href="<?php echo esc_url( remove_query_arg(['q','marca','tipo','subs','pag']) ); ?>">
                LIMPAR TODAS AS SELEÇÕES
              </a>
            </div>

            <details class="fvph-accordion">
              <summary>Melhores coleções</summary>
              <div class="fvph-accordion__body">
                <p class="fvph-muted">Coleções selecionadas (em breve)</p>
              </div>
            </details>

            <details class="fvph-accordion">
              <summary>Tipos de produtos</summary>
              <div class="fvph-accordion__body">
                <?php if ($terms_tipo) foreach($terms_tipo as $t): ?>
                  <label class="fvph-check">
                    <input type="checkbox" name="tipo[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(in_array($t->slug,$tipo,true)); ?>>
                    <span><?php echo esc_html($t->name); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </details>

            <details class="fvph-accordion">
              <summary>Substância</summary>
              <div class="fvph-accordion__body">
                <?php if ($subs_values) foreach($subs_values as $v): ?>
                  <label class="fvph-check">
                    <input type="checkbox" name="subs[]" value="<?php echo esc_attr($v); ?>" <?php checked(in_array($v,$subs,true)); ?>>
                    <span><?php echo esc_html($v); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </details>

            <details class="fvph-accordion">
              <summary>Marcas</summary>
              <div class="fvph-accordion__body fvph-scroll">
                <?php if ($terms_marca) foreach($terms_marca as $t): ?>
                  <label class="fvph-check">
                    <input type="checkbox" name="marca[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(in_array($t->slug,$marca,true)); ?>>
                    <span><?php echo esc_html($t->name); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </details>
          </aside>

          <section class="fvph-content">
            <form class="fvph-toolbar" method="get">
              <div class="fvph-search">
                <input type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="Pesquisar produtos">
                <button type="submit" class="fvph-btn fvph-btn--dark">Buscar</button>
              </div>
              <?php
                foreach ($marca as $m) echo '<input type="hidden" name="marca[]" value="'.esc_attr($m).'">';
                foreach ($tipo  as $t) echo '<input type="hidden" name="tipo[]"  value="'.esc_attr($t).'">';
                foreach ($subs  as $s) echo '<input type="hidden" name="subs[]"  value="'.esc_attr($s).'">';
              ?>
            </form>

            <div class="fvph-grid">
              <?php if ($q_products->have_posts()): while($q_products->have_posts()): $q_products->the_post();
                    $price = get_post_meta(get_the_ID(), '_fvph_price', true);
                    $buy   = get_post_meta(get_the_ID(), '_fvph_buy_url', true);
              ?>
                <article class="fvph-card">
                  <a class="fvph-card__thumb" href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
                  </a>
                  <h3 class="fvph-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                  <?php if ($price): ?><div class="fvph-card__price">R$ <?php echo esc_html($price); ?></div><?php endif; ?>
                  <div class="fvph-card__actions">
                    <a class="fvph-btn fvph-btn--ghost" href="<?php the_permalink(); ?>">Ver mais</a>
                    <?php if ($buy): ?>
                      <a class="fvph-btn fvph-btn--dark" href="<?php echo esc_url($buy); ?>" target="_blank" rel="nofollow sponsored noopener">Comprar</a>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endwhile; else: ?>
                <p class="fvph-muted">Nenhum produto encontrado.</p>
              <?php endif; wp_reset_postdata(); ?>
            </div>

            <?php
              $total_pages = max(1, (int) $q_products->max_num_pages);
              if ($total_pages > 1):
                $current = $paged;
                $base_args = $_GET; unset($base_args['pag']);
                $make_url = function($page) use ($base_args){
                  $args = array_merge($base_args, ['pag'=>$page]);
                  // remove "preview" e baseia na URL atual
                  return esc_url( add_query_arg($args, remove_query_arg('preview', home_url(add_query_arg([])))) );
                };
            ?>
              <nav class="fvph-pagination" aria-label="Navegação de páginas">
                <a class="fvph-page <?php echo $current<=1?'is-disabled':''; ?>" href="<?php echo $current>1 ? $make_url($current-1) : '#'; ?>">&#10094;</a>
                <?php
                $window = 2; $pages = [];
                for($i=1;$i<=$total_pages;$i++){
                    if ($i==1 || $i==$total_pages || ($i >= $current-$window && $i <= $current+$window)) $pages[]=$i;
                    elseif (end($pages) !== '…') $pages[]='…';
                }
                foreach ($pages as $p){
                    if ($p==='…'){ echo '<span class="fvph-page fvph-ellipsis">…</span>'; }
                    else {
                        $cls = 'fvph-page'.($p==$current?' is-active':'');
                        echo '<a class="'.$cls.'" href="'.$make_url($p).'">'.(int)$p.'</a>';
                    }
                }
                ?>
                <a class="fvph-page <?php echo $current>=$total_pages?'is-disabled':''; ?>" href="<?php echo $current<$total_pages ? $make_url($current+1) : '#'; ?>">&#10095;</a>
              </nav>
            <?php endif; ?>
          </section>
        </div>

        <script>
        (function(){
          const aside = document.querySelector('.fvph-filters');
          if(!aside) return;
          aside.addEventListener('change', function(ev){
            const input = ev.target;
            if (!input || (['marca[]','tipo[]','subs[]'].indexOf(input.name) === -1)) return;

            const form = document.createElement('form');
            form.method = 'GET';

            const params = new URLSearchParams(window.location.search);
            const q = params.get('q');
            if (q) { const i=document.createElement('input'); i.type='hidden'; i.name='q'; i.value=q; form.appendChild(i); }

            document.querySelectorAll('input[name="marca[]"]:checked').forEach(ch=>{ let i=document.createElement('input'); i.type='hidden'; i.name='marca[]'; i.value=ch.value; form.appendChild(i); });
            document.querySelectorAll('input[name="tipo[]"]:checked').forEach(ch=>{ let i=document.createElement('input'); i.type='hidden'; i.name='tipo[]'; i.value=ch.value; form.appendChild(i); });
            document.querySelectorAll('input[name="subs[]"]:checked').forEach(ch=>{ let i=document.createElement('input'); i.type='hidden'; i.name='subs[]'; i.value=ch.value; form.appendChild(i); });

            let iPag=document.createElement('input'); iPag.type='hidden'; iPag.name='pag'; iPag.value='1'; form.appendChild(iPag);

            document.body.appendChild(form);
            form.submit();
          });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* =========================================================
     *  SHORTCODES AUXILIARES PARA ELEMENTOR
     * =======================================================*/

    // [fvph_title id="" slug=""]
    public static function title_sc($atts = []){
        $a = shortcode_atts(['id'=>'','slug'=>''], $atts, 'fvph_title');
        $pid = self::resolve_post_id($a);
        return $pid ? esc_html(get_the_title($pid)) : '';
    }

    // [fvph_price id="" slug="" prefix="R$ "]
    public static function price_sc($atts = []){
        $a = shortcode_atts(['id'=>'','slug'=>'','prefix'=>'R$ '], $atts, 'fvph_price');
        $pid = self::resolve_post_id($a);
        if (!$pid) return '';
        $price = get_post_meta($pid, '_fvph_price', true);
        return $price !== '' ? esc_html($a['prefix'] . $price) : '';
    }

    // [fvph_partner_logo id="" slug="" size="thumbnail" type="html|url"]
    public static function partner_logo_sc($atts = []){
        $a = shortcode_atts(['id'=>'','slug'=>'','size'=>'thumbnail','type'=>'html'], $atts, 'fvph_partner_logo');
        $pid = self::resolve_post_id($a);
        if (!$pid) return '';
        $logo_id = (int) get_post_meta($pid, '_fvph_partner_logo', true);
        if (!$logo_id) return '';
        if ($a['type']==='url') {
            $u = wp_get_attachment_image_url($logo_id, $a['size']);
            return $u ? esc_url($u) : '';
        }
        return wp_get_attachment_image($logo_id, $a['size'], false, ['loading'=>'lazy']);
    }

    // [fvph_image id="" slug="" size="large" type="url|html" index="0" attr='class="minha-classe"']
    // Pega a 1ª da galeria; se não tiver, usa a destaque.
    public static function image_sc($atts = []){
        $a = shortcode_atts([
            'id'    => '',
            'slug'  => '',
            'size'  => 'large',
            'type'  => 'url',   // use 'url' no Elementor (dinâmico -> URL)
            'index' => '0',
            'attr'  => '',      // attributes livres quando type="html"
        ], $atts, 'fvph_image');

        $pid = self::resolve_post_id($a);
        if (!$pid) return '';

        $gallery = get_post_meta($pid, '_fvph_gallery_ids', true);

        // permite que a galeria seja string CSV ou array
        if (is_string($gallery)) {
            $parts = array_filter(array_map('trim', explode(',', $gallery)));
            $gallery = $parts ? array_map('intval', $parts) : [];
        } elseif (!is_array($gallery)) {
            $gallery = [];
        }

        $img_id = 0;
        $idx = max(0, (int)$a['index']);
        if ($gallery && isset($gallery[$idx])) {
            $img_id = (int)$gallery[$idx];
        } elseif (has_post_thumbnail($pid)) {
            $img_id = (int) get_post_thumbnail_id($pid);
        }
        if (!$img_id) return '';

        if ($a['type'] === 'html') {
            // converte attr string em array rudimentar (somente class/alt/title suportados aqui)
            $extra = ['loading'=>'lazy'];
            if (!empty($a['attr'])) {
                if (preg_match('/class="([^"]+)"/', $a['attr'], $m))  $extra['class'] = $m[1];
                if (preg_match('/alt="([^"]+)"/',   $a['attr'], $m))  $extra['alt']   = $m[1];
                if (preg_match('/title="([^"]+)"/', $a['attr'], $m))  $extra['title'] = $m[1];
            }
            return wp_get_attachment_image($img_id, $a['size'], false, $extra);
        }
        // default: URL
        $u = wp_get_attachment_image_url($img_id, $a['size']);
        return $u ? esc_url($u) : '';
    }

    // [fvph_attr key="puffs" id="" slug=""]
    // Retorna qualquer _fvph_attr_{key}
    public static function attr_sc($atts = []){
        $a = shortcode_atts(['id'=>'','slug'=>'','key'=>''], $atts, 'fvph_attr');
        if (!$a['key']) return '';
        $pid = self::resolve_post_id($a);
        if (!$pid) return '';
        $meta = get_post_meta($pid, '_fvph_attr_' . sanitize_key($a['key']), true);
        return $meta !== '' ? esc_html($meta) : '';
    }
}
