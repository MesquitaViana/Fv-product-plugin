<?php
if (!defined('ABSPATH')) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class FVPH_Elementor_Widget_Products extends Widget_Base {

    public function get_name(){ return 'fvph_products'; }
    public function get_title(){ return 'FV: Grade de Produtos'; }
    public function get_icon(){ return 'eicon-products'; }
    public function get_categories(){ return ['general']; }

    protected function register_controls(){
        $this->start_controls_section('section_content',['label'=>'Conteúdo']);
        $this->add_control('category',[ 'label'=>'Categoria (slug)','type'=>Controls_Manager::TEXT,'default'=>'' ]);
        $this->add_control('brand',[ 'label'=>'Marca (slug)','type'=>Controls_Manager::TEXT,'default'=>'' ]);
        $this->add_control('limit',[ 'label'=>'Limite','type'=>Controls_Manager::NUMBER,'default'=>12,'min'=>1,'max'=>48 ]);
        $this->add_control('order_by',[ 'label'=>'Ordenar por','type'=>Controls_Manager::SELECT,'options'=>['date'=>'Data','title'=>'Título','rating'=>'Rating','sticky_first'=>'Destaque primeiro'],'default'=>'date' ]);
        $this->add_control('order',[ 'label'=>'Ordem','type'=>Controls_Manager::SELECT,'options'=>['DESC'=>'DESC','ASC'=>'ASC'],'default'=>'DESC' ]);
        $this->add_control('view_label',[ 'label'=>'Rótulo do botão Ver mais','type'=>Controls_Manager::TEXT,'default'=>'Ver mais' ]);
        $this->end_controls_section();
    }

    protected function render(){
        $s = $this->get_settings_for_display();
        echo do_shortcode( sprintf(
            '[fv_products category="%s" brand="%s" limit="%d" order="%s" order_by="%s" view_label="%s"]',
            esc_attr($s['category'] ?? ''),
            esc_attr($s['brand'] ?? ''),
            intval($s['limit'] ?? 12),
            esc_attr($s['order'] ?? 'DESC'),
            esc_attr($s['order_by'] ?? 'date'),
            esc_attr($s['view_label'] ?? 'Ver mais')
        ));
    }
}
