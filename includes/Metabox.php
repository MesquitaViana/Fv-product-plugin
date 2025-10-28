<?php
if (!defined('ABSPATH')) exit;

class FVPH_Metabox {
    public static function register(){
        add_meta_box('fvph_meta','Dados do Hub (FV)','\FVPH_Metabox::render','equipamento','side','default');
    }
    public static function render($post){
        $rating = get_post_meta($post->ID,'_fvph_rating',true);
        $sticky = get_post_meta($post->ID,'_fvph_sticky',true);
        $sku = get_post_meta($post->ID,'_fvph_sku',true);
        wp_nonce_field('fvph_meta_save','fvph_meta_nonce');
        ?>
        <p><label>Rating (0–5): <input type="number" step="0.1" min="0" max="5" name="fvph_rating" value="<?php echo esc_attr($rating); ?>" style="width:80px"></label></p>
        <p><label><input type="checkbox" name="fvph_sticky" value="1" <?php checked($sticky, '1'); ?>> Destacar (aparece primeiro)</label></p>
        <p><label>SKU (opcional): <input type="text" name="fvph_sku" value="<?php echo esc_attr($sku); ?>" style="width:100%"></label></p>
        <p style="opacity:.8">Preço: <?php echo esc_html(get_post_meta($post->ID,'_fvph_price',true)); ?></p>
        <?php
    }
    public static function save($post_id){
        if(!isset($_POST['fvph_meta_nonce']) || !wp_verify_nonce($_POST['fvph_meta_nonce'],'fvph_meta_save')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;
        $rating = isset($_POST['fvph_rating']) ? floatval($_POST['fvph_rating']) : '';
        $sticky = isset($_POST['fvph_sticky']) ? '1' : '0';
        $sku    = isset($_POST['fvph_sku']) ? sanitize_text_field($_POST['fvph_sku']) : '';
        if($rating!=='') update_post_meta($post_id,'_fvph_rating',$rating); else delete_post_meta($post_id,'_fvph_rating');
        update_post_meta($post_id,'_fvph_sticky',$sticky);
        if($sku!=='') update_post_meta($post_id,'_fvph_sku',$sku); else delete_post_meta($post_id,'_fvph_sku');
    }
}
