<?php
if (!defined('ABSPATH')) exit;

class FVPH_Admin {
    public static function register_settings(){
        register_setting('fvph', 'fvph_wc_url', ['sanitize_callback'=>'esc_url_raw']);
        register_setting('fvph', 'fvph_wc_key', ['sanitize_callback'=>'sanitize_text_field']);
        register_setting('fvph', 'fvph_wc_secret', ['sanitize_callback'=>'sanitize_text_field']);
        register_setting('fvph', 'fvph_csv_url', ['sanitize_callback'=>'esc_url_raw']);
        register_setting('fvph', 'fvph_mode', ['sanitize_callback'=>'sanitize_text_field']); // cpt/dynamic
    }

    public static function register_menu(){
        add_options_page('FV Product Hub','FV Product Hub','manage_options','fvph',[__CLASS__,'render_page']);
    }

    public static function render_page(){
        if(!current_user_can('manage_options')) return;
        if(isset($_POST['fvph_sync_now']) && check_admin_referer('fvph_sync')){
            do_action('fvph_sync_run');
            echo '<div class="updated"><p>Sincronização disparada.</p></div>';
        }
        ?>
        <div class="wrap">
          <h1>FV Product Hub</h1>
          <form method="post" action="options.php">
            <?php settings_fields('fvph'); ?>
            <table class="form-table">
              <tr><th scope="row"><label>Woo URL (site Tech)</label></th>
                <td><input type="url" class="regular-text" name="fvph_wc_url" value="<?php echo esc_attr(get_option('fvph_wc_url')); ?>" placeholder="https://techmarketbrasil.com"></td></tr>
              <tr><th scope="row"><label>Consumer Key</label></th>
                <td><input type="text" class="regular-text" name="fvph_wc_key" value="<?php echo esc_attr(get_option('fvph_wc_key')); ?>"></td></tr>
              <tr><th scope="row"><label>Consumer Secret</label></th>
                <td><input type="text" class="regular-text" name="fvph_wc_secret" value="<?php echo esc_attr(get_option('fvph_wc_secret')); ?>"></td></tr>
              <tr><th scope="row"><label>CSV Overrides (opcional)</label></th>
                <td><input type="url" class="regular-text" name="fvph_csv_url" value="<?php echo esc_attr(get_option('fvph_csv_url')); ?>" placeholder="https://.../overrides.csv"></td></tr>
              <tr><th scope="row"><label>Modo</label></th>
                <td>
                  <?php $mode = get_option('fvph_mode','cpt'); ?>
                  <select name="fvph_mode">
                    <option value="cpt" <?php selected($mode,'cpt'); ?>>CPT (cria posts)</option>
                    <option value="dynamic" <?php selected($mode,'dynamic'); ?> disabled>Dinâmico (em breve)</option>
                  </select>
                </td></tr>
            </table>
            <?php submit_button(); ?>
          </form>
          <hr>
          <form method="post">
            <?php wp_nonce_field('fvph_sync'); ?>
            <input type="hidden" name="fvph_sync_now" value="1">
            <?php submit_button('Sincronizar agora', 'primary'); ?>
          </form>
        </div>
        <?php
    }
}
