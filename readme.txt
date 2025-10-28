=== FV Product Hub ===
Contributors: Mesquita & Arthur
Tags: products, woo, elementor, cards, gallery, brand, rating, sticky, schema
Requires at least: 5.6
Tested up to: 6.6
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hub de produtos para blog, com conexão a e-commerce wordpress. **v0.5**: carrossel com autoplay, Product Schema (JSON-LD) e tabela de especificações.

== Novidades ==
- Carrossel com **autoplay** (data-autoplay/data-interval).
- **Product Schema** (JSON-LD) no single (name, image, description, brand, SKU, price, rating, offer).
- **Tabela de Especificações** (montada de metas conhecidas e qualquer `_fvph_attr_*`).

== Shortcode ==
[fv_products category="{especificar categoria}" brand="{especificar marca}" limit="12" order_by="sticky_first" order="DESC" view_label="Ver mais"]

== Changelog ==
= 0.5.0 =
* Autoplay no carrossel + pausa no hover.
* JSON-LD Product Schema no single.
* Tabela de especificações dinâmica.
* Sync passa a armazenar **SKU** em `_fvph_sku`.

## Documentação do Desenvolvedor
Veja a documentação completa de desenvolvimento e arquitetura em  
👉 [docs/DEVELOPER_GUIDE.md](docs/DEVELOPER_GUIDE.md)
