<?php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography; 
use Elementor\Group_Control_Border;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DPDFG_Elementor_Widget extends Widget_Base {

    // Storage for instantiated source classes
    private $sources = [];

    /**
     * Initialize all available source classes.
     */
    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );

        // Instantiate all potential source classes
        $this->sources = [
            'manual' => new DPDFG_Source_Manual( $this, $this->get_settings() ),
            'filebird_folder' => new DPDFG_Source_FileBird( $this, $this->get_settings() ),
            'wpmf_folder' => new DPDFG_Source_WPMF( $this, $this->get_settings() ),
        ];
    }

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'canvas-dynamic-pdf-gallery'; 
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return esc_html__( 'Dynamic PDF Gallery', 'dynamic-pdf-gallery' );
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-document-file';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return [ 'general' ];
    }
    
    /**
     * Register controls for the widget.
     */
    protected function register_controls() {
        
        // --- CONTENT TAB: PDF Source Selection ---
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'PDF Source', 'dynamic-pdf-gallery' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $source_options = [
            'manual' => esc_html__( 'Manual Selection', 'dynamic-pdf-gallery' ),
        ];
        
        // Dynamically add folder sources if active
        foreach ($this->sources as $source) {
            if ($source->is_active() && $source->get_name() !== 'manual') {
                $source_options[$source->get_name()] = $source->get_label();
            }
        }

        $this->add_control(
            'source_type',
            [
                'label' => esc_html__( 'PDF Source Type', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'manual',
                'options' => $source_options,
                'description' => esc_html__( 'Choose the source for your PDF links.', 'dynamic-pdf-gallery' ),
            ]
        );
        
        $this->end_controls_section();


        // --- CONTENT TAB: Source-Specific Controls ---
        foreach ($this->sources as $source) {
            if ($source->is_active()) {
                $this->start_controls_section(
                    'source_section_' . $source->get_name(),
                    [
                        'label' => sprintf( esc_html__( '%s Settings', 'dynamic-pdf-gallery' ), $source->get_label() ),
                        'tab' => Controls_Manager::TAB_CONTENT,
                        'condition' => [
                            'source_type' => $source->get_name(),
                        ],
                    ]
                );
                // Delegate control registration to the source class
                $source->register_controls( $this ); 
                
                $this->end_controls_section();
            }
        }


        // --- STYLE TAB: Layout Settings ---
        $this->start_controls_section(
            'layout_style_section',
            [
                'label' => esc_html__( 'Layout', 'dynamic-pdf-gallery' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__( 'Columns', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => esc_html__( '1 Column', 'dynamic-pdf-gallery' ),
                    '2' => esc_html__( '2 Columns', 'dynamic-pdf-gallery' ),
                    '3' => esc_html__( '3 Columns', 'dynamic-pdf-gallery' ),
                    '4' => esc_html__( '4 Columns', 'dynamic-pdf-gallery' ),
                ],
                'selectors' => [
                    '{{WRAPPER}} .canvas-pdf-embedder-wrapper' => 'display: grid; grid-template-columns: repeat({{VALUE}}, 1fr); gap: 30px;',
                    '{{WRAPPER}} .pdf-item-container' => 'margin-bottom: 0 !important;', 
                ],
            ]
        );
        
        $this->end_controls_section();

        // --- STYLE TAB: Image/Thumbnail Style ---
        $this->start_controls_section(
            'image_style_section',
            [
                'label' => esc_html__( 'Image Thumbnail', 'dynamic-pdf-gallery' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .pdf-thumbnail-image',
            ]
        );
        
        $this->add_control(
            'image_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .pdf-thumbnail-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();

        // --- STYLE TAB: Title Style ---
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__( 'Title Text', 'dynamic-pdf-gallery' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .pdf-link-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__( 'Text Color', 'dynamic-pdf-gallery' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pdf-link-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Helper method to render a single PDF item. Reused by manual and folder modes.
     * @param string $pdf_url The URL of the PDF file.
     * @param int $index The index of the item (for unique classes).
     * @param string $link_text The text to display below the image.
     */
    private function _render_single_pdf_item( $pdf_url, $index, $link_text ) {
        if ( ! function_exists( 'attachment_url_to_postid' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }

        $thumbnail_url = ''; 
        
        $attachment_id = attachment_url_to_postid( $pdf_url );
        if ( $attachment_id ) {
            $image_src = wp_get_attachment_image_src( $attachment_id, 'medium' ); 
            if ( $image_src ) {
                $thumbnail_url = $image_src[0];
            }
        }
        
        $alt_text = esc_attr( $link_text );
        
        if ( $thumbnail_url ) {
            $link_content = sprintf(
                '<img src="%s" alt="%s" class="pdf-thumbnail-image" style="width: 100%%; max-width: 300px; height: auto; display: block; margin: 0 auto 10px;">' .
                '<span class="pdf-link-title" style="display: block; text-decoration: none; padding: 0 5px;">%s</span>',
                esc_url( $thumbnail_url ),
                $alt_text, 
                esc_html( $link_text )
            );
        } else {
            $link_content = esc_html( $link_text );
        }
        
        printf( '<div class="pdf-item-container pdf-item-%d" style="text-align: center;">', $index );

        printf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="pdf-link-anchor" style="display: inline-flex; flex-direction: column; align-items: center; text-decoration: none; color: inherit;">%s</a>',
            esc_url( $pdf_url ),
            $link_content 
        );
        
        echo '</div>';
    }


    /**
     * Render the widget output on the frontend.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $source_type = $settings['source_type'];
        $items_to_render = [];

        // Check if the current source type exists and is active
        if ( isset( $this->sources[$source_type] ) && $this->sources[$source_type]->is_active() ) {
            
            // Re-instantiate the source with current settings for fresh data pull
            $source_class = get_class($this->sources[$source_type]);
            $source = new $source_class( $this, $settings );
            $items_to_render = $source->fetch_pdfs();

        } else if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
             // Fallback message for editor if the selected plugin is not active
             echo '<div style="background-color: #ffe0e0; padding: 10px; border: 1px solid #ff4d4d;">' . esc_html__( 'Error: The selected PDF source plugin is not active or invalid.', 'dynamic-pdf-gallery' ) . '</div>';
             return;
        }

        // --- RENDER OUTPUT ---
        if ( empty( $items_to_render ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                $message = esc_html__( 'No PDFs found matching the criteria.', 'dynamic-pdf-gallery' );
                echo '<div style="background-color: #ffe0e0; padding: 10px; border: 1px solid #ff4d4d;">' . $message . '</div>';
            }
            return;
        }

        $this->add_render_attribute( 'wrapper', 'class', 'canvas-pdf-embedder-wrapper' );
        
        echo '<div ' . $this->get_render_attribute_string( 'wrapper' ) . '>';
        
        foreach ( $items_to_render as $index => $item ) {
            $this->_render_single_pdf_item( $item['pdf_url'], $index, $item['link_text'] );
        }
        
        echo '</div>';
    }

    /**
     * Render the widget output in the Elementor editor (optional, but good practice).
     */
    protected function content_template() {
        ?>
        <div class="elementor-pdf-embedder-preview">
            <#
            var sourceType = settings.source_type;
            var sourceName = '';
            var isFolderSource = false;

            // Determine source name and type for preview message
            if ( sourceType === 'manual' ) {
                sourceName = '<?php echo esc_html__( 'Manual Selection', 'dynamic-pdf-gallery' ); ?>';
            } else if ( sourceType === 'filebird_folder' ) {
                sourceName = 'FileBird Folder';
                isFolderSource = true;
            } else if ( sourceType === 'wpmf_folder' ) {
                sourceName = 'WP Media Folder';
                isFolderSource = true;
            }

            if ( isFolderSource ) {
                var sortControl = settings.sort_by_filebird || settings.sort_by_wpmf;
                var sortLabel = '<?php echo esc_html__( 'Sorted by:', 'dynamic-pdf-gallery' ); ?> ' + (sortControl || 'date_desc');
                var folderId = settings.filebird_folder_id || settings.wpmf_folder_id;
                
                if ( folderId ) {
                    #>
                    <div style="background-color: #e0f7fa; padding: 15px; border: 1px solid #00bcd4; text-align: center;">
                        <p style="font-weight: bold; margin: 0;"><?php echo esc_html__( 'Dynamic Content Source:', 'dynamic-pdf-gallery' ); ?> {{ sourceName }}</p>
                        <small>{{ sortLabel }}</small>
                        <p style="margin-top: 5px;"><?php echo esc_html__( 'PDFs will be loaded dynamically from the selected folder on the front-end.', 'dynamic-pdf-gallery' ); ?></p>
                    </div>
                    <#
                } else {
                    #>
                    <div style="background-color: #fff8e0; padding: 10px; border: 1px solid #ffc107;">
                        <?php echo esc_html__( 'Please select a folder.', 'dynamic-pdf-gallery' ); ?>
                    </div>
                    <#
                }

            } else { 
                // MANUAL SELECTION MODE
                if ( settings.pdf_list && settings.pdf_list.length ) {
                    var expiredCount = 0;
                    _.each( settings.pdf_list, function( item ) {
                        var pdfUrl = item.pdf_file_item.url;
                        var linkText = item.item_admin_title || item.pdf_file_item.filename || '<?php echo esc_html__( 'Attachment Title', 'dynamic-pdf-gallery' ); ?>';
                        var expiryDate = item.expiry_date;
                        var isExpired = false;
                        
                        if ( expiryDate ) {
                            // JS check for editor preview
                            var expiryTimestamp = Date.parse( expiryDate.replace( /-/g, '/' ) );
                            var currentTimestamp = new Date().getTime();

                            if ( currentTimestamp > expiryTimestamp ) {
                                isExpired = true;
                                expiredCount++;
                            }
                        }

                        if ( pdfUrl && pdfUrl.toLowerCase().endsWith('.pdf') ) {
                            #>
                            <div class="pdf-item-container" style="margin-bottom: 30px; text-align: center; opacity: {{ isExpired ? 0.4 : 1 }};">
                                <p class="pdf-link-container">
                                    <span style="display: block; font-weight: bold;">{{ linkText }}</span>
                                    <small>({{ isExpired ? 'EXPIRED - HIDDEN ON FRONT-END' : 'Link to PDF' }})</small>
                                </p>
                            </div>
                            <#
                        } else {
                            #>
                            <div style="background-color: #fff8e0; padding: 10px; border: 1px solid #ffc107;">
                                <?php echo esc_html__( 'PDF Document', 'dynamic-pdf-gallery' ); ?>: <?php echo esc_html__( 'No valid PDF file selected for this item.', 'dynamic-pdf-gallery' ); ?>
                            </div>
                            <#
                        }
                    } );
                    
                    if ( expiredCount > 0 ) {
                        #>
                        <div style="background-color: #ffccbc; padding: 10px; margin-top: 15px; border: 1px solid #e53935; text-align: center;">
                            <?php echo esc_html__( 'NOTE: ', 'dynamic-pdf-gallery' ); ?> {{ expiredCount }} <?php echo esc_html__( 'item(s) are expired and will not display on the front-end.', 'dynamic-pdf-gallery' ); ?>
                        </div>
                        <#
                    }
                } else {
                    #>
                    <div style="background-color: #ffe0e0; padding: 10px; border: 1px solid #ff4d4d;">
                        <?php echo esc_html__( 'Please add at least one PDF item.', 'dynamic-pdf-gallery' ); ?>
                    </div>
                    <#
                }
            }
            #>
        </div>
        <?php
    }

}
