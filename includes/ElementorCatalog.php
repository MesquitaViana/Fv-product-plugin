<?php
if (!defined('ABSPATH')) exit;

/* Evita fatal se alguém incluir este arquivo cedo demais */
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class FVPH_ElementorCatalog extends Widget_Base {

    public function get_name() { return 'fvph_catalog_widget'; }
    public function get_title(){ return 'Catálogo FV (Filtro + Grid + Paginação)'; }
    public function get_icon() { return 'eicon-products'; }
    public function get_categories(){ return ['general']; } // ajuste a categoria se tiver uma própria

    protected function register_controls() {
        $this->start_controls_section('section_settings', [
            'label' => 'Configurações',
        ]);

        $this->add_control('per_page', [
            'label'   => 'Produtos por página',
            'type'    => Controls_Manager::NUMBER,
            'default' => 15, // 3 x 5
            'min'     => 3,
            'max'     => 60,
            'step'    => 1,
        ]);

        $this->add_control('show_filters', [
            'label'        => 'Mostrar filtros laterais',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Sim',
            'label_off'    => 'Não',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_search', [
            'label'        => 'Mostrar barra de busca',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Sim',
            'label_off'    => 'Não',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();
    }

    /** Helpers */
    protected static function get_tax_slugs(){
        $tax_marca = class_exists('FVPH_Synchronizer') ? FVPH_Synchronizer::TAX_MARCA : 'marca_prod';
        $tax_tipo  = class_exists('FVPH_Synchronizer') ? FVPH_Synchronizer::TAX_CATEG : 'categoria_prod';
        return [$tax_marca, $tax_tipo];
    }

    protected static function get_filters_from_request(){
        $q     = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        $marca = (isset($_GET['marca']) && is_array($_GET['marca'])) ? array_map('sanitize_title', $_GET['marca']) : [];
        $tipo  = (isset($_GET['tipo'])  && is_array($_GET['tipo']))  ? array_map('sanitize_title', $_GET['tipo'])  : [];
        $subs  = (isset($_GET['subs'])  && is_array($_GET['subs']))  ? array_map('sanitize_text_field', $_GET['subs']) : [];
        $paged = isset($_GET['pag']) ? max(1, (int)$_GET['pag']) : max(1, get_query_var('paged', 1));
        return compact('q','marca','tipo','subs','paged');
    }

    protected function query_products($per_page, $filters, $tax_marca, $tax_tipo){
        $tax_query = ['relation'=>'AND'];
        if (!empty($filters['marca'])) $tax_query[] = ['taxonomy'=>$tax_marca,'field'=>'slug','terms'=>$filters['marca']];
        if (!empty($filters['tipo']))  $tax_query[] = ['taxonomy'=>$tax_tipo, 'field'=>'slug','terms'=>$filters['tipo']];

        $meta_query = ['relation'=>'AND'];
        if (!empty($filters['subs']))  $meta_query[] = ['key'=>'_fvph_attr_substancia','value'=>$filters['subs'],'compare'=>'IN'];

        $args = [
            'post_type'      => 'produto',
            'post_status'    => 'publish',
            's'              => $filters['q'],
            'posts_per_page' => $per_page,
            'paged'          => $filters['paged'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if (count($tax_query)  > 1) $args['tax_query']  = $tax_query;
        if (count($meta_query) > 1) $args['meta_query'] = $meta_query;

        return new WP_Query($args);
    }

    protected function get_filter_data($tax_marca, $tax_tipo){
        $terms_marca = get_terms(['taxonomy'=>$tax_marca,'hide_empty'=>true,'number'=>1000]);
        $terms_tipo  = get_terms(['taxonomy'=>$tax_tipo, 'hide_empty'=>true,'number'=>1000]);

        global $wpdb;
        $subs_values = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT meta_value 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status='publish'
             ORDER BY meta_value ASC",
            '_fvph_attr_substancia','produto'
        ));
        $subs_values = array_values(array_filter(array_map('trim', (array)$subs_values)));
        return [$terms_marca, $terms_tipo, $subs_values];
    }

    protected function render_filters($show_filters, $found_posts, $terms_marca, $terms_tipo, $subs_values, $filters){
        if ($show_filters !== 'yes') return;
        ?>
        <aside class="fvph-filters">
          <div class="fvph-filters__header">
            <strong><?php echo esc_html(number_format_i18n($found_posts)); ?></strong> produtos
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
                  <input type="checkbox" name="tipo[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(in_array($t->slug,$filters['tipo'],true)); ?>>
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
                  <input type="checkbox" name="subs[]" value="<?php echo esc_attr($v); ?>" <?php checked(in_array($v,$filters['subs'],true)); ?>>
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
                  <input type="checkbox" name="marca[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(in_array($t->slug,$filters['marca'],true)); ?>>
                  <span><?php echo esc_html($t->name); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </details>
        </aside>
        <?php
    }

    protected function render_toolbar($show_search, $filters){
        ?>
        <form class="fvph-toolbar" method="get">
          <div class="fvph-search" style="<?php echo $show_search==='yes'?'':'display:none'; ?>">
            <input type="search" name="q" value="<?php echo esc_attr($filters['q']); ?>" placeholder="Pesquisar produtos">
            <button type="submit" class="fvph-btn fvph-btn--dark">Buscar</button>
          </div>
          <?php
            foreach ($filters['marca'] as $m) echo '<input type="hidden" name="marca[]" value="'.esc_attr($m).'">';
            foreach ($filters['tipo']  as $t) echo '<input type="hidden" name="tipo[]"  value="'.esc_attr($t).'">';
            foreach ($filters['subs']  as $s) echo '<input type="hidden" name="subs[]"  value="'.esc_attr($s).'">';
          ?>
        </form>
        <?php
    }

    protected function render_grid($q){
        ?>
        <div class="fvph-grid">
          <?php if ($q->have_posts()): while($q->have_posts()): $q->the_post();
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
                <?php if ($buy): ?><a class="fvph-btn fvph-btn--dark" href="<?php echo esc_url($buy); ?>" target="_blank" rel="noopener">Comprar</a><?php endif; ?>
              </div>
            </article>
          <?php endwhile; else: ?>
            <p class="fvph-muted">Nenhum produto encontrado.</p>
          <?php endif; wp_reset_postdata(); ?>
        </div>
        <?php
    }

    protected function render_pagination($q, $current){
        $total_pages = max(1, (int) $q->max_num_pages);
        if ($total_pages <= 1) return;

        $base_args = $_GET; unset($base_args['pag']);
        $make_url = function($page) use ($base_args){
            $args = array_merge($base_args, ['pag'=>$page]);
            return esc_url( add_query_arg($args, remove_query_arg('preview', home_url( add_query_arg([]) ))) );
        };
        ?>
        <nav class="fvph-pagination" aria-label="Navegação de páginas">
          <a class="fvph-page <?php echo $current<=1?'is-disabled':''; ?>" href="<?php echo $current>1 ? $make_url($current-1) : '#'; ?>">&#10094;</a>
          <?php
            $window = 2; $pages = [];
            for ($i=1;$i<=$total_pages;$i++){
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
        <?php
    }

    protected function render() {
        [$tax_marca, $tax_tipo] = self::get_tax_slugs();
        $settings = $this->get_settings_for_display();
        $per_page = max(1, (int)($settings['per_page'] ?? 15));

        $filters = self::get_filters_from_request();
        $query   = $this->query_products($per_page, $filters, $tax_marca, $tax_tipo);
        [$terms_marca, $terms_tipo, $subs_values] = $this->get_filter_data($tax_marca, $tax_tipo);

        echo '<div class="fvph-catalog" data-widget="fvph_catalog_widget">';

        // Coluna de filtros
        $this->render_filters($settings['show_filters'] ?? 'yes', $query->found_posts, $terms_marca, $terms_tipo, $subs_values, $filters);

        // Conteúdo (busca + grid + paginação)
        echo '<section class="fvph-content">';
        $this->render_toolbar($settings['show_search'] ?? 'yes', $filters);
        $this->render_grid($query);
        $this->render_pagination($query, $filters['paged']);
        echo '</section>';

        echo '</div>';
        ?>
        <script>
        // Submete filtros ao marcar/desmarcar
        (function(){
          var wrap = document.querySelector('[data-widget="fvph_catalog_widget"]');
          if(!wrap) return;
          var aside = wrap.querySelector('.fvph-filters');
          if(!aside) return;
          aside.addEventListener('change', function(ev){
            var input = ev.target;
            if (!input || (['marca[]','tipo[]','subs[]'].indexOf(input.name) === -1)) return;

            var form = document.createElement('form');
            form.method = 'GET';

            var params = new URLSearchParams(window.location.search);
            var q = params.get('q');
            if (q) { var i=document.createElement('input'); i.type='hidden'; i.name='q'; i.value=q; form.appendChild(i); }

            wrap.querySelectorAll('input[name="marca[]"]:checked').forEach(function(ch){
              var i=document.createElement('input'); i.type='hidden'; i.name='marca[]'; i.value=ch.value; form.appendChild(i);
            });
            wrap.querySelectorAll('input[name="tipo[]"]:checked').forEach(function(ch){
              var i=document.createElement('input'); i.type='hidden'; i.name='tipo[]'; i.value=ch.value; form.appendChild(i);
            });
            wrap.querySelectorAll('input[name="subs[]"]:checked').forEach(function(ch){
              var i=document.createElement('input'); i.type='hidden'; i.name='subs[]'; i.value=ch.value; form.appendChild(i);
            });

            var iPag=document.createElement('input'); iPag.type='hidden'; iPag.name='pag'; iPag.value='1'; form.appendChild(iPag);

            document.body.appendChild(form);
            form.submit();
          });
        })();
        </script>
        <?php
    }

    /** Registro estático chamado pelo hook do Elementor */
    public static function register_widget($widgets_manager){
        $widgets_manager->register(new self());
    }
}
