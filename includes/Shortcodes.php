<?php
if (!defined('ABSPATH')) exit;

class FVPH_Shortcodes {
    public static function register(){
        add_shortcode('fv_products', [__CLASS__, 'products']);
    }

    protected static function build_query_args($a){
        $args = [
            'post_type'      => 'equipamento',
            'posts_per_page' => intval($a['limit']),
            'tax_query'      => [],
        ];
        if(!empty($a['category'])){
            $args['tax_query'][] = [
                'taxonomy'=>'categoria_equip',
                'field'=>'slug',
                'terms'=> sanitize_title($a['category'])
            ];
        }
        if(!empty($a['brand'])){
            $args['tax_query'][] = [
                'taxonomy'=>'marca_equip',
                'field'=>'slug',
                'terms'=> sanitize_title($a['brand'])
            ];
        }
        $order_by = strtolower($a['order_by']);
        $order = $a['order'] === 'ASC' ? 'ASC' : 'DESC';
        if($order_by === 'title'){
            $args['orderby'] = 'title';
            $args['order'] = $order;
        } elseif($order_by === 'rating'){
            $args['meta_key'] = '_fvph_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = $order;
        } elseif($order_by === 'sticky_first'){
            $args['meta_key'] = '_fvph_sticky';
            $args['orderby'] = ['meta_value_num'=>$order, 'date'=>$order];
        } else {
            $args['orderby'] = 'date';
            $args['order'] = $order;
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
        ], $atts);

        $args = self::build_query_args($a);
        $q = new WP_Query($args);

        ob_start();
        echo '<div class="fv-grid">';
        while($q->have_posts()){ $q->the_post();
            $price = get_post_meta(get_the_ID(), '_fvph_price', true);
            $buy   = get_post_meta(get_the_ID(), '_fvph_buy_url', true);
            $puffs = get_post_meta(get_the_ID(), '_fvph_attr_puffs', true);
            $nic   = get_post_meta(get_the_ID(), '_fvph_attr_nicotina', true);
            $bat   = get_post_meta(get_the_ID(), '_fvph_attr_bateria', true);
            $rating= get_post_meta(get_the_ID(), '_fvph_rating', true);
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
}
