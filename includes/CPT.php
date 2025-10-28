<?php
if (!defined('ABSPATH')) exit;

class FVPH_CPT {
    public static function register_types(){

        // ===== CPT: produto =====
        register_post_type('produto', [
            'labels' => [
                'name'                  => 'Produtos',
                'singular_name'         => 'Produto',
                'menu_name'             => 'Produtos',
                'name_admin_bar'        => 'Produto',
                'add_new'               => 'Adicionar novo',
                'add_new_item'          => 'Adicionar produto',
                'new_item'              => 'Novo produto',
                'edit_item'             => 'Editar produto',
                'view_item'             => 'Ver produto',
                'all_items'             => 'Todos os produtos',
                'search_items'          => 'Pesquisar produtos',
                'parent_item_colon'     => 'Produto pai:',
                'not_found'             => 'Nenhum produto encontrado',
                'not_found_in_trash'    => 'Nenhum produto na lixeira',
            ],
            'public'            => true,
            'has_archive'       => true,
            'rewrite'           => ['slug' => 'produtos'],
            'menu_icon'         => 'dashicons-products',
            'supports'          => ['title','editor','thumbnail','excerpt','custom-fields'],
            'show_in_rest'      => true, // Gutenberg/Elementor
        ]);

        // ===== Taxonomia: categoria_prod (hierárquica) =====
        register_taxonomy('categoria_prod', 'produto', [
            'labels' => [
                'name'              => 'Categorias de Produto',
                'singular_name'     => 'Categoria de Produto',
                'search_items'      => 'Pesquisar categorias',
                'all_items'         => 'Todas as categorias',
                'parent_item'       => 'Categoria pai',
                'parent_item_colon' => 'Categoria pai:',
                'edit_item'         => 'Editar categoria',
                'update_item'       => 'Atualizar categoria',
                'add_new_item'      => 'Adicionar categoria',
                'new_item_name'     => 'Nova categoria',
                'menu_name'         => 'Categorias de Produto',
            ],
            'hierarchical'  => true,
            'rewrite'       => ['slug' => 'categoria-produto'],
            'show_in_rest'  => true,
        ]);

        // ===== Taxonomia: marca_prod (não hierárquica) =====
        register_taxonomy('marca_prod', 'produto', [
            'labels' => [
                'name'              => 'Marcas',
                'singular_name'     => 'Marca',
                'search_items'      => 'Pesquisar marcas',
                'all_items'         => 'Todas as marcas',
                'edit_item'         => 'Editar marca',
                'update_item'       => 'Atualizar marca',
                'add_new_item'      => 'Adicionar marca',
                'new_item_name'     => 'Nova marca',
                'menu_name'         => 'Marcas',
            ],
            'hierarchical'  => false,
            'rewrite'       => ['slug' => 'marca'],
            'show_in_rest'  => true,
        ]);

        /**
         * (Opcional) Back-compat rápido:
         * Se você já tinha posts/tax com os slugs antigos, pode criar aliases simples de rewrite
         * ou tratar migração num script separado. Aqui mantemos apenas o novo registro limpo.
         */
    }
}
