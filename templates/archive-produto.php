<?php
if (!defined('ABSPATH')) exit;
get_header(); ?>
<main id="primary" class="site-main fvph-archive">
  <header class="fvph-archive-hero">
    <h1>Produtos</h1>
    <p>Explore os produtos destacados pela comunidade do Fórum do Vapor.</p>
  </header>
  <div class="fv-grid">
    <?php if(have_posts()): while(have_posts()): the_post();
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
        <a class="fv-btn fv-btn-outline" href="<?php the_permalink(); ?>">Ver mais</a>
        <?php if($buy){ ?><a class="fv-btn" href="<?php echo esc_url($buy); ?>" target="_blank" rel="nofollow sponsored noopener">Comprar</a><?php } ?>
      </div>
    </article>
    <?php endwhile; endif; ?>
  </div>
  <?php the_posts_navigation(); ?>
</main>
<?php get_footer(); ?>
