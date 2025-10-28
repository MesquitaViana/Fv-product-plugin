<?php
if (!defined('ABSPATH')) exit;

class FVPH_CPT {
    public static function register_types(){
        register_post_type('equipamento',[
            'labels'=>[
                'name'                  => 'Equipamentos',
                'singular_name'         => 'Equipamento',
                'menu_name'             => 'Equipamentos',
                'name_admin_bar'        => 'Equipamento',
                'add_new'               => 'Adicionar novo',
                'add_new_item'          => 'Adicionar equipamento',
                'new_item'              => 'Novo equipamento',
                'edit_item'             => 'Editar equipamento',
                'view_item'             => 'Ver equipamento',
                'all_items'             => 'Todos os equipamentos',
                'search_items'          => 'Pesquisar equipamentos',
                'parent_item_colon'     => 'Equipamento pai:',
                'not_found'             => 'Nenhum equipamento encontrado',
                'not_found_in_trash'    => 'Nenhum equipamento na lixeira'
            ],
            'public'=>true,
            'has_archive'=>true,
            'rewrite'=>['slug'=>'equipamentos'],
            'menu_icon'=>'dashicons-hammer',
            'supports'=>['title','editor','thumbnail','excerpt','custom-fields']
        ]);
        register_taxonomy('categoria_equip','equipamento',[
            'labels'=>[
                'name'              => 'Categorias de Equipamento',
                'singular_name'     => 'Categoria de Equipamento',
                'search_items'      => 'Pesquisar categorias',
                'all_items'         => 'Todas as categorias',
                'parent_item'       => 'Categoria pai',
                'parent_item_colon' => 'Categoria pai:',
                'edit_item'         => 'Editar categoria',
                'update_item'       => 'Atualizar categoria',
                'add_new_item'      => 'Adicionar categoria',
                'new_item_name'     => 'Nova categoria',
                'menu_name'         => 'Categorias de Equipamento',
            ],
            'hierarchical'=>true,
            'rewrite'=>['slug'=>'categoria-equipamento']
        ]);
        register_taxonomy('marca_equip','equipamento',[
            'labels'=>[
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
            'hierarchical'=>false,
            'rewrite'=>['slug'=>'marca']
        ]);
    }
}
