<?php
if (!defined('ABSPATH')) exit;

class FVPH_Admin {
    public static function register_settings(){
        register_setting('fvph', 'fvph_wc_url',    ['sanitize_callback'=>'esc_url_raw']);
        register_setting('fvph', 'fvph_wc_key',    ['sanitize_callback'=>'sanitize_text_field']);
        register_setting('fvph', 'fvph_wc_secret', ['sanitize_callback'=>'sanitize_text_field']);
        register_setting('fvph', 'fvph_csv_url',   ['sanitize_callback'=>'esc_url_raw']);
        register_setting('fvph', 'fvph_mode',      ['sanitize_callback'=>'sanitize_text_field']); // cpt/dynamic
        // opcional: armazenar timestamp da última sync (atualize no Synchronizer::run)
        register_setting('fvph', 'fvph_last_sync', ['sanitize_callback'=>'sanitize_text_field']);
    }

    public static function register_menu(){
        add_options_page('FV Product Hub','FV Product Hub','manage_options','fvph',[__CLASS__,'render_page']);
    }

    public static function render_page(){
        if(!current_user_can('manage_options')) return;

        // Processa ações POST desta página
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sincronizar agora
            if (isset($_POST['fvph_sync_now']) && check_admin_referer('fvph_sync')) {
                if (!class_exists('FVPH_Synchronizer')) {
                    self::notice('Classe FVPH_Synchronizer não encontrada.', 'error');
                } else {
                    // Evita dois cliques simultâneos
                    if (get_transient('fvph_sync_lock')) {
                        self::notice('Uma sincronização já está em andamento. Tente novamente em alguns segundos.', 'warning');
                    } else {
                        set_transient('fvph_sync_lock', 1, 60); // trava por 60s
                        try {
                            FVPH_Synchronizer::run();
                            self::notice('Sincronização concluída com sucesso.', 'success');
                        } catch (\Throwable $e) {
                            self::notice('Erro ao sincronizar: '.$e->getMessage(), 'error');
                        }
                        delete_transient('fvph_sync_lock');
                    }
                }
            }

            // Testar conexão com a API Woo
            if (isset($_POST['fvph_test_conn']) && check_admin_referer('fvph_test')) {
                self::test_connection();
            }
        }

        $mode       = get_option('fvph_mode', 'cpt');
        $last_sync  = get_option('fvph_last_sync', '');
        $url_val    = esc_attr(get_option('fvph_wc_url'));
        $ck_val     = esc_attr(get_option('fvph_wc_key'));
        $csv_val    = esc_attr(get_option('fvph_csv_url'));
        ?>
        <div class="wrap">
          <h1>FV Product Hub</h1>

          <form method="post" action="options.php" style="margin-top:20px;">
            <?php settings_fields('fvph'); ?>
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="fvph_wc_url">Woo URL (site Tech)</label></th>
                <td>
                  <input id="fvph_wc_url" type="url" class="regular-text" name="fvph_wc_url"
                         value="<?php echo $url_val; ?>" placeholder="https://techmarketbrasil.com">
                  <p class="description">Use a raiz do site. Ex.: <code>https://seusite.com</code></p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="fvph_wc_key">Consumer Key</label></th>
                <td>
                  <input id="fvph_wc_key" type="text" class="regular-text" name="fvph_wc_key"
                         value="<?php echo $ck_val; ?>">
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="fvph_wc_secret">Consumer Secret</label></th>
                <td>
                  <input id="fvph_wc_secret" type="password" class="regular-text" name="fvph_wc_secret"
                         value="<?php echo esc_attr(get_option('fvph_wc_secret')); ?>" autocomplete="new-password">
                  <p class="description">Dica: as chaves são usadas na querystring, evitando bloqueios do header <code>Authorization</code>.</p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="fvph_csv_url">CSV Overrides (opcional)</label></th>
                <td>
                  <input id="fvph_csv_url" type="url" class="regular-text" name="fvph_csv_url"
                         value="<?php echo $csv_val; ?>" placeholder="https://.../overrides.csv">
                  <p class="description">Se usado, este CSV pode sobrescrever campos (preço, URL de compra etc.).</p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="fvph_mode">Modo</label></th>
                <td>
                  <select id="fvph_mode" name="fvph_mode">
                    <option value="cpt" <?php selected($mode,'cpt'); ?>>CPT (cria posts)</option>
                    <option value="dynamic" <?php selected($mode,'dynamic'); ?> disabled>Dinâmico (em breve)</option>
                  </select>
                </td>
              </tr>
              <?php if (!empty($last_sync)) : ?>
              <tr>
                <th scope="row">Última sincronização</th>
                <td><code><?php echo esc_html($last_sync); ?></code></td>
              </tr>
              <?php endif; ?>
            </table>
            <?php submit_button(); ?>
          </form>

          <hr>

          <form method="post" style="display:inline-block; margin-right:12px;">
            <?php wp_nonce_field('fvph_sync'); ?>
            <input type="hidden" name="fvph_sync_now" value="1">
            <?php submit_button('Sincronizar agora', 'primary', 'submit', false); ?>
          </form>

          <form method="post" style="display:inline-block;">
            <?php wp_nonce_field('fvph_test'); ?>
            <input type="hidden" name="fvph_test_conn" value="1">
            <?php submit_button('Testar conexão com a loja', 'secondary', 'submit', false); ?>
          </form>
        </div>
        <?php
    }

    /** Helpers **/

    private static function notice($msg, $type = 'success'){
        $types = ['success','error','warning','info'];
        if (!in_array($type, $types, true)) $type = 'success';
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($type), wp_kses_post($msg));
    }

    private static function test_connection(){
        $base = rtrim((string) get_option('fvph_wc_url'), '/');
        $ck   = trim((string) get_option('fvph_wc_key'));
        $cs   = trim((string) get_option('fvph_wc_secret'));

        if (!$base || !$ck || !$cs) {
            self::notice('Preencha URL, Consumer Key e Secret e salve as alterações antes de testar.', 'warning');
            return;
        }

        $url = add_query_arg([
            'per_page'        => 1,
            'page'            => 1,
            'status'          => 'publish',
            'consumer_key'    => $ck,
            'consumer_secret' => $cs,
        ], $base . '/wp-json/wc/v3/products');

        $args = [
            'timeout' => 20,
            'headers' => [
                'Accept'     => 'application/json',
                'User-Agent' => 'FVProductHub/Test (' . home_url() . ')'
            ]
        ];

        $res  = wp_remote_get($url, $args);
        if (is_wp_error($res)) {
            self::notice('Falha na requisição: ' . $res->get_error_message(), 'error');
            return;
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        $body = wp_remote_retrieve_body($res);
        if ($code !== 200) {
            $excerpt = wp_html_excerpt($body, 320, '&hellip;');
            self::notice('HTTP ' . $code . ' ao acessar a API. Corpo: <code>' . esc_html($excerpt) . '</code>', 'error');
            return;
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            $excerpt = wp_html_excerpt($body, 320, '&hellip;');
            self::notice('JSON inválido retornado pela API. Corpo: <code>' . esc_html($excerpt) . '</code>', 'error');
            return;
        }
        $count = count($data);
        self::notice('Conexão OK. A API retornou ' . intval($count) . ' produto(s) na página de teste.', 'success');
    }
}
