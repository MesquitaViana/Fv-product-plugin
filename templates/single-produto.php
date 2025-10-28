<?php
if (!defined('ABSPATH')) exit;
get_header(); ?>
<main id="primary" class="site-main fvph-single">
  <?php while(have_posts()): the_post();
    $price    = get_post_meta(get_the_ID(), '_fvph_price', true);
    $buy_url  = get_post_meta(get_the_ID(), '_fvph_buy_url', true);
    $puffs    = get_post_meta(get_the_ID(), '_fvph_attr_puffs', true);
    $nic      = get_post_meta(get_the_ID(), '_fvph_attr_nicotina', true);
    $bat      = get_post_meta(get_the_ID(), '_fvph_attr_bateria', true);
    $rating   = get_post_meta(get_the_ID(), '_fvph_rating', true);
    $sku      = get_post_meta(get_the_ID(), '_fvph_sku', true);
    $gallery  = get_post_meta(get_the_ID(), '_fvph_gallery_ids', true);
    if(!is_array($gallery)) $gallery = array_filter((array)$gallery);

    // Marca (nova taxonomia)
    $brand_terms = wp_get_post_terms(get_the_ID(), 'marca_prod', ['fields'=>'names']);
    $brand = (!is_wp_error($brand_terms) && !empty($brand_terms)) ? $brand_terms[0] : '';

    // Parceria
    $partner   = get_post_meta(get_the_ID(), '_fvph_partner_name', true);
    $buy_label = get_post_meta(get_the_ID(), '_fvph_buy_label', true);
    $logo_id   = get_post_meta(get_the_ID(), '_fvph_partner_logo', true);
    $cta_text  = $buy_label ?: ($partner ? 'Comprar na '.$partner : 'Comprar');

    $utm = 'utm_source=forumdovapor&utm_medium=referral&utm_campaign=produto';
    $buy_href = $buy_url ? esc_url($buy_url . (strpos($buy_url,'?')===false ? '?' : '&') . $utm) : '';
  ?>
  <article <?php post_class('fvph-product'); ?>>

    <header class="fvph-hero">
      <div class="fvph-hero-media">
        <?php if($gallery && count($gallery)>0): ?>
          <div class="fvph-carousel" data-fv-carousel data-autoplay="1" data-interval="4000">
            <button class="fvph-nav prev" type="button" aria-label="Anterior">&#10094;</button>
            <div class="fvph-track">
              <?php foreach($gallery as $gid): ?>
                <div class="fvph-slide"><?php echo wp_get_attachment_image($gid, 'large', false, ['loading'=>'lazy']); ?></div>
              <?php endforeach; ?>
            </div>
            <button class="fvph-nav next" type="button" aria-label="Próximo">&#10095;</button>
          </div>
          <div class="fvph-thumbs">
            <?php foreach($gallery as $gid): ?>
              <button class="fvph-thumb" data-fv-thumb><?php echo wp_get_attachment_image($gid, 'thumbnail', false); ?></button>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <?php if(has_post_thumbnail()) the_post_thumbnail('large'); ?>
        <?php endif; ?>
      </div>

      <div class="fvph-hero-meta">
        <h1 class="fvph-title"><?php the_title(); ?></h1>
        <div class="fvph-chips">
          <?php if($puffs) echo '<span class="fv-chip">'.esc_html($puffs).' puffs</span>'; ?>
          <?php if($nic)   echo '<span class="fv-chip">'.esc_html($nic).' mg</span>'; ?>
          <?php if($bat)   echo '<span class="fv-chip">'.esc_html($bat).' mAh</span>'; ?>
          <?php if($rating!=='') echo '<span class="fv-chip">★ '.esc_html($rating).'</span>'; ?>
        </div>
        <?php if($price): ?><div class="fvph-price">Preço de referência: <strong>R$ <?php echo esc_html($price); ?></strong></div><?php endif; ?>

        <div class="fvph-cta">
          <?php if($buy_href): ?>
            <a class="fv-btn" href="<?php echo $buy_href; ?>" target="_blank" rel="nofollow sponsored noopener">
              <?php if($logo_id) echo wp_get_attachment_image($logo_id, 'thumbnail', false, ['style'=>'height:20px;width:auto;margin-right:8px;vertical-align:middle;border-radius:4px']); ?>
              <?php echo esc_html($cta_text); ?>
            </a>
          <?php endif; ?>
        </div>

        <div class="fvph-meta-inline">
          <?php the_terms(get_the_ID(), 'marca_prod', '<span class="fv-meta-term">Marca: ', ', ', '</span>'); ?>
          <?php if($sku) echo '<span class="fv-meta-term"> • SKU: '.esc_html($sku).'</span>'; ?>
        </div>
      </div>
    </header>

    <section class="fvph-specs">
      <h2>Especificações</h2>
      <table class="fvph-specs-table"><tbody>
        <?php
          $rows = [];
          if($puffs) $rows['Puffs'] = $puffs;
          if($nic)   $rows['Nicotina'] = $nic.' mg';
          if($bat)   $rows['Bateria'] = $bat.' mAh';
          foreach(get_post_meta(get_the_ID()) as $k=>$vals){
              if(strpos($k,'_fvph_attr_')===0 && !in_array($k, ['_fvph_attr_puffs','_fvph_attr_nicotina','_fvph_attr_bateria'])){
                  $label = ucwords(str_replace(['_fvph_attr_','_','-'],' ', $k));
                  $value = is_array($vals) ? implode(', ', $vals) : $vals;
                  echo '<tr><th>'.esc_html($label).'</th><td>'.esc_html($value).'</td></tr>';
              }
          }
          foreach($rows as $kk=>$vv){
              echo '<tr><th>'.esc_html($kk).'</th><td>'.esc_html($vv).'</td></tr>';
          }
        ?>
      </tbody></table>
    </section>

    <div class="fvph-content"><?php the_content(); ?></div>

    <footer class="fvph-footer">
      <div class="fvph-meta">
        <?php the_terms(get_the_ID(), 'categoria_prod', '<span class="fv-meta-term">Categorias: ', ', ', '</span>'); ?>
      </div>
      <nav class="fvph-nav">
        <a href="<?php echo esc_url(get_post_type_archive_link('produto')); ?>" class="fv-btn fv-btn-outline">Voltar para produtos</a>
      </nav>
    </footer>
  </article>

  <?php
    // === JSON-LD Product Schema ===
    $img = '';
    if($gallery && count($gallery)>0){
        $img = wp_get_attachment_image_url($gallery[0],'full');
    } elseif(has_post_thumbnail()){
        $img = wp_get_attachment_image_url(get_post_thumbnail_id(),'full');
    }
    $schema = [
      '@context' => 'https://schema.org',
      '@type'    => 'Product',
      'name'     => get_the_title(),
      'image'    => $img ? [$img] : [],
      'description' => wp_strip_all_tags(get_the_excerpt() ?: get_the_content(null,false)),
      'brand'    => $brand ? ['@type'=>'Brand','name'=>$brand] : null,
      'sku'      => $sku ?: null,
      'offers'   => [
        '@type'         => 'Offer',
        'priceCurrency' => 'BRL',
        'price'         => $price ?: '0',
        'availability'  => 'https://schema.org/InStock',
        'url'           => get_permalink()
      ]
    ];
    if($rating!==''){
      $schema['aggregateRating'] = ['@type'=>'AggregateRating','ratingValue'=> $rating, 'reviewCount'=> 1];
    }
    echo '<script type="application/ld+json">'.wp_json_encode($schema).'</script>';
  ?>

  <?php endwhile; ?>
</main>
<?php get_footer(); ?>
