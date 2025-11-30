<?php

use Elementor\Controls_Manager;
use Elementor\Repeater; 
use Elementor\Widget_Base; // FIX: Added use statement for Widget_Base to resolve compatibility error

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DPDFG_Source_Manual extends DPDFG_Abstract_Source {
    
    public function get_name() {
        return 'manual';
    }

    public function get_label() {
        return esc_html__( 'Manual Selection', 'dynamic-pdf-gallery' );
    }

    public function is_active() {
        return true; // Always active
    }

    public function register_controls( Widget_Base $widget ) {
        $repeater = new Repeater();

        // Repeater Field: Admin Title (for display in editor)
        $repeater->add_control(
            'item_admin_title',
            [
                'label' => esc_html__( 'Item Title (Admin Only)', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__( 'This title is for managing items in the editor only. The front-end title comes from the PDF attachment.', 'dynamic-pdf-gallery' ),
                'separator' => 'after',
            ]
        );

        // Repeater Field: Media Control for the PDF file
        $repeater->add_control(
            'pdf_file_item',
            [
                'label' => esc_html__( 'Select PDF File', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::MEDIA,
                'media_type' => 'application/pdf',
                'description' => esc_html__( 'Upload or select a PDF file from the media library.', 'dynamic-pdf-gallery' ),
                'default' => [
                    'url' => '',
                ],
            ]
        );
        
        // Repeater Field: Expiry Date
        $repeater->add_control(
            'expiry_date',
            [
                'label' => esc_html__( 'Expiry Date (Optional)', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::DATE_TIME,
                'picker_options' => [
                    'dateFormat' => 'Y-m-d',
                    'enableTime' => false,
                ],
                'description' => esc_html__( 'If set, the document will automatically disappear after this date.', 'dynamic-pdf-gallery' ),
            ]
        );
        
        // The Repeater Control itself
        $widget->add_control(
            'pdf_list',
            [
                'label' => esc_html__( 'PDF Documents', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [],
                'title_field' => '{{{ item_admin_title || "' . esc_html__( 'PDF Document', 'dynamic-pdf-gallery' ) . '" }}}',
                'conditions' => [
                    'relation' => 'and',
                    'terms' => [
                        [
                            'name' => 'source_type',
                            'operator' => '===',
                            'value' => $this->get_name(),
                        ],
                    ],
                ],
                // Advanced JS to set the item_admin_title when a media file is selected
                'fields_options' => [
                    'item_admin_title' => [
                        'js' => '
                            var adminTitleControl = view.model.get( "item_admin_title" );
                            if ( ! adminTitleControl ) return;

                            var mediaData = view.model.get( "pdf_file_item" );
                            var newTitle = mediaData.title || mediaData.filename;

                            if ( newTitle && ( ! adminTitleControl.get("default") || adminTitleControl.get("default") === view.model.get( "item_admin_title" ) ) ) {
                                view.model.set( "item_admin_title", newTitle );
                            }
                        ',
                    ],
                ],
            ]
        );
    }
    
    public function fetch_pdfs() {
        $pdf_list = $this->settings['pdf_list'];
        $items_to_render = [];
        $absolute_fallback_text = esc_html__( 'View Document', 'dynamic-pdf-gallery' );
        
        if ( is_array( $pdf_list ) ) {
            foreach ( $pdf_list as $item ) {
                $pdf_url = ! empty( $item['pdf_file_item']['url'] ) ? $item['pdf_file_item']['url'] : '';
                $expiry_date_str = ! empty( $item['expiry_date'] ) ? $item['expiry_date'] : null;

                // 1. Check Expiry Date
                if ( $expiry_date_str ) {
                    $current_date = new DateTime( current_time( 'mysql' ) );
                    $expiry_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $expiry_date_str . ' 23:59:59' ); 

                    if ( $current_date > $expiry_date ) {
                        continue; // Skip rendering expired item
                    }
                }

                if ( empty( $pdf_url ) || ! str_ends_with( strtolower( $pdf_url ), '.pdf' ) ) {
                    continue; 
                }
                
                $attachment_id = attachment_url_to_postid( $pdf_url );
                $link_text = get_the_title( $attachment_id );
                
                if ( empty( $link_text ) ) {
                    $link_text = $absolute_fallback_text;
                }
                
                $items_to_render[] = [
                    'pdf_url' => $pdf_url,
                    'link_text' => $link_text,
                ];
            }
        }
        return $items_to_render;
    }
}
