# FV Product Hub – Documentação do Desenvolvedor (v0.5)

> **Resumo**: Plugin WordPress para o **Fórum do Vapor** que cria um *hub* de produtos com **CPT**, **taxonomias**, **sync via WooCommerce REST**, **cards/grades** (shortcode + **widget Elementor**), **galeria com carrossel (autoplay)**, **rating/destaque**, **tabela de especificações** e **Product Schema (JSON‑LD)** no single.

---

## 1) Estrutura do projeto

```
fv-product-hub/
├─ fv-product-hub.php                # bootstrap/loader
├─ readme.txt
├─ assets/
│  ├─ css/style.css                  # styles de grid, single e carrossel
│  └─ js/carousel.js                 # carrossel vanilla (autoplay, thumbs)
├─ includes/
│  ├─ Admin.php                      # página de configurações e sync manual
│  ├─ CPT.php                        # registro de CPT e taxonomias
│  ├─ Metabox.php                    # rating, destaque e SKU
│  ├─ Shortcodes.php                 # [fv_products]
│  ├─ Synchronizer.php               # sync via Woo REST + sideload de imagens
│  ├─ Template.php                   # template loader (single/archive do CPT)
│  ├─ ElementorWidget.php            # registrador de widgets
│  └─ Elementor_Widget_Products.php  # widget "FV: Grade de Produtos"
└─ templates/
   ├─ archive-equipamento.php
   └─ single-equipamento.php
```

---

## 2) Conceitos principais

### 2.1 CPT e taxonomias

* **CPT**: `equipamento` (slug de arquivo: `/equipamentos`)
* **Taxonomias**:

  * `categoria_equip` (hierárquica)
  * `marca_equip` (não hierárquica)

### 2.2 Metas usadas (post meta)

| Meta key            | Tipo    | Origem/uso                                                                                  |
| ------------------- | ------- | ------------------------------------------------------------------------------------------- |
| `_fvph_price`       | string  | Preço de referência (Woo → products.price)                                                  |
| `_fvph_buy_url`     | string  | Link para comprar na Tech Market Brasil (Woo → products.permalink)                          |
| `_fvph_gallery_ids` | int[]   | IDs da mídia enviada via **sideload** (todas as imagens do produto)                         |
| `_fvph_attr_*`      | string  | Atributos do produto (ex.: `_fvph_attr_puffs`, `_fvph_attr_nicotina`, `_fvph_attr_bateria`) |
| `_fvph_rating`      | float   | Nota editorial (0–5) – metabox                                                              |
| `_fvph_sticky`      | '0'/'1' | Destaque editorial – metabox                                                                |
| `_fvph_sku`         | string  | SKU do WooCommerce                                                                          |

> **Observação**: atributos adicionais do Woo são mapeados em `_fvph_attr_<nome-sanitizado>` e aparecem automaticamente na **tabela de especificações**.

---

## 3) Sincronização com WooCommerce

* **Agendamento**: *cron* `fvph_sync_run` (2x/dia) e botão **Sincronizar agora** em *Configurações → FV Product Hub*.
* **Credenciais**: informar `Woo URL`, `Consumer Key` e `Consumer Secret` (perfis com acesso a `read`).
* **REST chamado**: `GET {woo}/wp-json/wc/v3/products?status=publish&per_page=50&page={n}` com **Basic Auth**.
* **Comportamento**:

  * Cria/atualiza `equipamento` por `slug/name`.
  * Faz **sideload** de **todas** as imagens para a biblioteca de mídia (1ª vira *thumbnail*). Salva IDs em `_fvph_gallery_ids`.
  * Copia **price**, **permalink**, **sku** e **attributes** (puffs, nicotina, bateria, brand/marca, etc.).
  * Tax **marca_equip** é preenchida automaticamente a partir do atributo `brand`/`marca`.

> **Permalinks**: após primeira ativação, recomenda‑se visitar **Configurações → Links Permanentes** e salvar para *flush* das regras.

---

## 4) UI e exibição

### 4.1 Shortcode

```
[fv_products category="pods-descartaveis" brand="vaporesso" limit="12" order_by="sticky_first" order="DESC" view_label="Ver mais"]
```

**Parâmetros**:

* `category` (slug da `categoria_equip`)
* `brand` (slug da `marca_equip`)
* `limit` (default 12)
* `order_by`: `date` | `title` | `rating` | `sticky_first`
* `order`: `DESC` (default) ou `ASC`
* `view_label`: texto do botão (default "Ver mais")

### 4.2 Widget Elementor

* Nome: **FV: Grade de Produtos**
* Controles: `category`, `brand`, `limit`, `order_by`, `order`, `view_label`
* Renderização interna reutiliza o mesmo motor do shortcode.

### 4.3 Single do CPT

* **Hero** com **carrossel** (thumbs + autoplay; pausa ao hover).
* **Chips** automáticas para puffs / mg / mAh / rating.
* **Preço** (referência) e **botão** “Comprar na Tech Market Brasil”.
* **Marca** e **SKU** (se existir).
* **Tabela de especificações** gerada a partir das metas.
* **JSON‑LD Product Schema** (name, image, description, brand, sku, offers, aggregateRating).

---

## 5) Carrossel

* Implementado em `assets/js/carousel.js` (vanilla, leve).
* No **single** o contêiner tem atributos:

  * `data-autoplay="1"` (liga autoplay)
  * `data-interval="4000"` (milissegundos)
* Miniaturas com `[data-fv-thumb]` sincronizam o slide ativo.

---

## 6) Templates

* `templates/single-equipamento.php`
* `templates/archive-equipamento.php`

> **Override por tema**: da forma atual, o plugin **força** seus templates via `template_include`. Para usar templates do tema, você pode (a) desativar o filtro do plugin (removendo a linha do add_filter no bootstrap), **ou** (b) adicionar um filtro com prioridade mais alta no tema para retornar um template personalizado:

```php
// functions.php do tema
add_filter('template_include', function($tpl){
  if (is_singular('equipamento')) {
    return get_stylesheet_directory() . '/single-equipamento.php';
  }
  if (is_post_type_archive('equipamento')) {
    return get_stylesheet_directory() . '/archive-equipamento.php';
  }
  return $tpl;
}, 5); // prioridade menor número = executa antes
```

---

## 7) Metabox (Editor)

* **Rating** (0–5): define `_fvph_rating` (exibido nos chips e usado na ordenação `rating`).
* **Destaque**: define `_fvph_sticky` = '1' (usado em `sticky_first`).
* **SKU**: manual/override do SKU sincronizado.

---

## 8) Extensões comuns (snippets)

### 8.1 Adicionar mapeamento de atributo customizado como chip

Ex.: exibir **airflow** se existir `_fvph_attr_airflow`.

```php
add_filter('the_content', function($html){
  if (!is_singular('equipamento')) return $html;
  $air = get_post_meta(get_the_ID(), '_fvph_attr_airflow', true);
  if ($air) {
    $chip = '<span class="fv-chip">' . esc_html($air) . '</span>';
    $html = preg_replace('/(<div class="fvph-chips">)/', '$1'.$chip, $html, 1);
  }
  return $html;
});
```

### 8.2 Forçar autoplay do carrossel para 6s

```php
add_action('wp_enqueue_scripts', function(){
  if (is_singular('equipamento')) {
    wp_add_inline_script('fvph-carousel', 'document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("[data-fv-carousel]").forEach(function(el){el.setAttribute("data-interval","6000");});});');
  }
}, 20);
```

### 8.3 Ordenar grade por **rating** via shortcode

```html
[fv_products brand="vaporesso" order_by="rating" order="DESC" limit="8"]
```

### 8.4 Query programática dos equipamentos em um template do tema

```php
$Q = new WP_Query([
  'post_type' => 'equipamento',
  'tax_query' => [[
    'taxonomy' => 'marca_equip', 'field' => 'slug', 'terms' => ['vaporesso']
  ]],
  'meta_key' => '_fvph_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC',
  'posts_per_page' => 10
]);
```

### 8.5 Customizar JSON‑LD antes de imprimir (hook rápido)

> Como o schema é renderizado no próprio template, uma abordagem simples é **sobrescrever o template** via tema e ajustar o array `$schema` diretamente. Alternativamente, adicione uma *action* no final do single e injete seu script após `the_content`.

---

## 9) Boas práticas e performance

* **Cache**: se a grade for muito acessada, envolva a saída do shortcode/widget em **transient** (*page cache* ou fragment cache_) – expirar em 5–10 min.
* **Imagens**: usar **thumb/sizes** adequadas (o plugin já usa `medium/large`), e **WebP** se possível (CDN/optmizer).
* **Cron**: se o host não executa WP‑Cron, configure um CRON real chamando `wp-cron.php` periodicamente.

---

## 10) Segurança e sanitização

* Entradas de settings: `esc_url_raw`, `sanitize_text_field`.
* Saídas: `esc_html`, `esc_url`, `wp_kses_post` onde houver HTML do Woo.
* Links de compra com `rel="nofollow sponsored noopener"`.

---

## 11) Troubleshooting

* **401/403 na sync**: verifique **Consumer Key/Secret** e permissões; teste o endpoint no Postman.
* **Imagens não baixam**: checar `allow_url_fopen`, SSL válido na origem e permissões de upload.
* **URLs 404 em /equipamentos**: salve novamente os **Links Permanentes**.
* **Widget não aparece no Elementor**: garantir que o Elementor está ativo e sem *fatal errors* no site (ver **Ferramentas → Site Health** e `debug.log`).

---

## 12) Roadmap sugerido

* Filtro de **faixa de preço** no shortcode/widget.
* **Reviews** editoriais (CPT filho) + `Review` Schema; média agregada real.
* Opções de **carrossel** no painel (autoplay, intervalo, setas, dots, loop).
* **Hooks/filters** públicos para customização do schema, chips e specs sem tocar em templates.
* Integração opcional com **ACF** para campos extras visuais (ex.: “Tipo de coil”, “Porta de carregamento”, etc.).

---

## 13) Versionamento

* **v0.1–0.2.1**: base + labels PT‑BR
* **v0.3**: galeria completa, `marca_equip`, widget Elementor
* **v0.4**: carrossel, rating/destaque, ordenação avançada
* **v0.5**: autoplay, schema JSON‑LD, specs dinâmicas, SKU

> Qualquer dúvida, manda o trecho da tua IDE que eu reviso e devolvo o patch pronto. 😉
