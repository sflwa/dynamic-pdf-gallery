<?php

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract class for defining a PDF source, ensuring all sources have required methods.
 */
abstract class DPDFG_Abstract_Source {
    
    protected $widget;
    protected $settings;

    public function __construct( $widget, $settings ) {
        $this->widget = $widget;
        $this->settings = $settings;
    }

    /**
     * Get the unique name/key for this source type (e.g., 'manual', 'filebird_folder').
     * @return string
     */
    abstract public function get_name();

    /**
     * Get the display label for this source type (e.g., 'Manual Selection').
     * @return string
     */
    abstract public function get_label();

    /**
     * Check if the required plugin/functionality is active.
     * @return bool
     */
    abstract public function is_active();

    /**
     * Register specific Elementor controls for this source.
     * @param Widget_Base $widget The Elementor widget instance.
     */
    abstract public function register_controls( Widget_Base $widget );

    /**
     * Fetch the array of PDF items based on the current settings.
     * Each item must be: ['pdf_url' => string, 'link_text' => string]
     * @return array
     */
    abstract public function fetch_pdfs();
}
