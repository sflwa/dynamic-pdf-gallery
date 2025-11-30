<?php

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DPDFG_Source_FileBird extends DPDFG_Abstract_Source {
    
    public function get_name() {
        return 'filebird_folder';
    }

    public function get_label() {
        return esc_html__( 'FileBird Folder', 'dynamic-pdf-gallery' );
    }

    public function is_active() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
        }
        return is_plugin_active( 'filebird/filebird.php' );
    }
    
    private function get_filebird_folders() {
        global $wpdb;
        $folders = [ '0' => esc_html__( 'Uncategorized', 'dynamic-pdf-gallery' ) ];
        $table_name = $wpdb->prefix . 'fbv'; 
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            $results = $wpdb->get_results("SELECT id, name FROM {$table_name} WHERE id > 0 ORDER BY name ASC");
            if ($results) {
                foreach ($results as $row) {
                    $folders[$row->id] = $row->name;
                }
            }
        }
        return $folders;
    }

    public function register_controls( \Elementor\Widget_Base $widget ) {
        $folders = $this->get_filebird_folders();
        
        $widget->add_control(
            'filebird_folder_id',
            [
                'label' => esc_html__( 'Select FileBird Folder', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::SELECT,
                'options' => $folders,
                'default' => '0',
                'condition' => [
                    'source_type' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'sort_by_filebird',
            [
                'label' => esc_html__( 'Sort Order', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'date_desc',
                'options' => [
                    'date_desc' => esc_html__( 'Date Uploaded (Newest First)', 'dynamic-pdf-gallery' ),
                    'date_asc' => esc_html__( 'Date Uploaded (Oldest First)', 'dynamic-pdf-gallery' ),
                    'title_asc' => esc_html__( 'Title (A-Z)', 'dynamic-pdf-gallery' ),
                    'title_desc' => esc_html__( 'Title (Z-A)', 'dynamic-pdf-gallery' ),
                ],
                'condition' => [
                    'source_type' => $this->get_name(),
                ],
            ]
        );
    }
    
    public function fetch_pdfs() {
        global $wpdb;
        $items_to_render = [];
        $folder_id = absint( $this->settings['filebird_folder_id'] );
        $sort_by = sanitize_key( $this->settings['sort_by_filebird'] );
        
        // Build ORDER BY clause
        $order_by = 'p.post_date DESC';
        switch ( $sort_by ) {
            case 'date_asc': $order_by = 'p.post_date ASC'; break;
            case 'title_asc': $order_by = 'p.post_title ASC'; break;
            case 'title_desc': $order_by = 'p.post_title DESC'; break;
        }

        $attachment_table = $wpdb->prefix . 'posts';
        $filebird_table = $wpdb->prefix . 'fbv_attachment_folder';

        $query = $wpdb->prepare(
            "SELECT 
                p.ID, p.post_title, p.guid 
            FROM 
                {$attachment_table} AS p
            INNER JOIN 
                {$filebird_table} AS f 
            ON 
                p.ID = f.attachment_id
            WHERE 
                p.post_type = 'attachment' 
            AND 
                p.post_mime_type = 'application/pdf'
            AND 
                f.folder_id = %d
            ORDER BY 
                {$order_by}",
            $folder_id
        );

        $results = $wpdb->get_results( $query );
        
        // FIX: Check for expiration before rendering
        if ( $results && function_exists('dpdfg_is_pdf_expired') ) {
            foreach ( $results as $post ) {
                
                if ( dpdfg_is_pdf_expired( $post->ID ) ) {
                    continue; // Skip expired item
                }
                
                $items_to_render[] = [
                    'pdf_url' => $post->guid,
                    'link_text' => get_the_title( $post->ID ),
                    'attachment_id' => $post->ID, // Add ID for other potential uses
                ];
            }
        }
        return $items_to_render;
    }
}
