<?php
if (!defined('ABSPATH')) exit;

class FVPH_Metabox {
    public static function register(){
        add_meta_box('fvph_meta','Dados do Produto (FV)','\\FVPH_Metabox::render','produto','side','default');
    }

    public static function render($post){
        $rating       = get_post_meta($post->ID,'_fvph_rating',true);
        $sticky       = get_post_meta($post->ID,'_fvph_sticky',true);
        $sku          = get_post_meta($post->ID,'_fvph_sku',true);
        $price        = get_post_meta($post->ID,'_fvph_price',true);
        $buy_url      = get_post_meta($post->ID,'_fvph_buy_url',true);

        // Parceria / manual
        $manual       = get_post_meta($post->ID,'_fvph_manual',true); // '1' = não sobrescrever pela sync
        $partner      = get_post_meta($post->ID,'_fvph_partner_name',true);
        $partner_url  = get_post_meta($post->ID,'_fvph_partner_url',true);
        $buy_label    = get_post_meta($post->ID,'_fvph_buy_label',true);
        $partner_logo = get_post_meta($post->ID,'_fvph_partner_logo',true); // attachment ID

        wp_nonce_field('fvph_meta_save','fvph_meta_nonce');
        ?>
        <p><label><input type="checkbox" name="fvph_manual" value="1" <?php checked($manual,'1'); ?>> Produto manual (não sobrescrever pela sincronização)</label></p>

        <hr>
        <p><strong>Parceria</strong></p>
        <p><label>Nome do parceiro:<br>
            <input type="text" name="fvph_partner_name" value="<?php echo esc_attr($partner); ?>" style="width:100%">
        </label></p>
        <p><label>URL do parceiro (home):<br>
            <input type="url" name="fvph_partner_url" value="<?php echo esc_url($partner_url); ?>" style="width:100%" placeholder="https://loja-parceira.com">
        </label></p>
        <p><label>Label do botão de compra:<br>
            <input type="text" name="fvph_buy_label" value="<?php echo esc_attr($buy_label); ?>" style="width:100%" placeholder="Comprar na Loja X">
        </label></p>
        <p><label>URL de compra (produto no parceiro):<br>
            <input type="url" name="fvph_buy_url" value="<?php echo esc_url($buy_url); ?>" style="width:100%" placeholder="https://loja-parceira.com/produto/x">
        </label></p>
        <p><label>Logo do parceiro (ID de mídia, opcional):<br>
            <input type="number" name="fvph_partner_logo" value="<?php echo esc_attr($partner_logo); ?>" style="width:100%" placeholder="ID do anexo">
        </label></p>

        <hr>
        <p><strong>Comercial</strong></p>
        <p><label>Preço (R$):<br>
            <input type="text" name="fvph_price" value="<?php echo esc_attr($price); ?>" style="width:100%" placeholder="618.31">
        </label></p>
        <p><label>SKU:<br>
            <input type="text" name="fvph_sku" value="<?php echo esc_attr($sku); ?>" style="width:100%">
        </label></p>

        <hr>
        <p><strong>Editorial</strong></p>
        <p><label>Rating (0–5): <input type="number" step="0.1" min="0" max="5" name="fvph_rating" value="<?php echo esc_attr($rating); ?>" style="width:80px"></label></p>
        <p><label><input type="checkbox" name="fvph_sticky" value="1" <?php checked($sticky,'1'); ?>> Destaque (aparece primeiro)</label></p>
        <?php
    }

    public static function save($post_id){
        if(!isset($_POST['fvph_meta_nonce']) || !wp_verify_nonce($_POST['fvph_meta_nonce'],'fvph_meta_save')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!current_user_can('edit_post',$post_id)) return;

        $get = function($key,$cb){
            return isset($_POST[$key]) ? call_user_func($cb, $_POST[$key]) : '';
        };

        // Flags
        update_post_meta($post_id,'_fvph_manual', isset($_POST['fvph_manual']) ? '1' : '0');
        update_post_meta($post_id,'_fvph_sticky', isset($_POST['fvph_sticky']) ? '1' : '0');

        // Parceria
        update_post_meta($post_id,'_fvph_partner_name', $get('fvph_partner_name','sanitize_text_field'));
        update_post_meta($post_id,'_fvph_partner_url',  $get('fvph_partner_url','esc_url_raw'));
        update_post_meta($post_id,'_fvph_buy_label',    $get('fvph_buy_label','sanitize_text_field'));
        update_post_meta($post_id,'_fvph_buy_url',      $get('fvph_buy_url','esc_url_raw'));
        update_post_meta($post_id,'_fvph_partner_logo', $get('fvph_partner_logo','sanitize_text_field'));

        // Comercial
        $price = $get('fvph_price','sanitize_text_field');
        if($price===''){ delete_post_meta($post_id,'_fvph_price'); } else { update_post_meta($post_id,'_fvph_price',$price); }

        $sku = $get('fvph_sku','sanitize_text_field');
        if($sku===''){ delete_post_meta($post_id,'_fvph_sku'); } else { update_post_meta($post_id,'_fvph_sku',$sku); }

        // Editorial
        $rating = isset($_POST['fvph_rating']) && $_POST['fvph_rating']!=='' ? floatval($_POST['fvph_rating']) : '';
        if($rating===''){ delete_post_meta($post_id,'_fvph_rating'); } else { update_post_meta($post_id,'_fvph_rating',$rating); }
    }
}
