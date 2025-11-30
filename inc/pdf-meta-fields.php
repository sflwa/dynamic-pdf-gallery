<?php
/**
 * Dynamic PDF Gallery: Attachment Meta Fields
 * Handles adding and saving the PDF Expiry Date (Date and Time fields) in the WordPress Media Library.
 * Stores date and time as separate local strings (site's timezone).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// --- Constants and Utility Functions ---

// FIX: New constants for separate date and time local meta keys.
define( 'DPDFG_EXPIRY_DATE_KEY', '_dpdfg_expiration_date_local' );
define( 'DPDFG_EXPIRY_TIME_KEY', '_dpdfg_expiration_time_local' );


/**
 * Helper function to check if a PDF attachment has expired based on its meta fields.
 * The calculation (combining local time and converting to GMT) is done here.
 *
 * @param int $attachment_id The ID of the PDF attachment.
 * @return bool True if expired, false otherwise.
 */
function dpdfg_is_pdf_expired( $attachment_id ) {
    // Retrieve raw local date and time strings
    $date_local = get_post_meta( $attachment_id, DPDFG_EXPIRY_DATE_KEY, true );
    $time_local = get_post_meta( $attachment_id, DPDFG_EXPIRY_TIME_KEY, true );

    if ( empty( $date_local ) ) {
        return false;
    }
    
    // Combine to local datetime string, defaulting time to 00:00:00 if missing.
    $time_local = empty($time_local) ? '00:00:00' : $time_local;
    $local_datetime_string = trim( $date_local . ' ' . $time_local );

    // Convert local time string to a Unix timestamp.
    $timestamp_local = strtotime( $local_datetime_string );
    if ($timestamp_local === false) {
        error_log('DPDFG Expiry Check Error: Could not parse local date/time: ' . $local_datetime_string);
        return false;
    }
    
    try {
        // 1. Get site timezone object
        $site_timezone = wp_timezone();
        
        // 2. Create DateTime object from local timestamp in the site's local timezone
        $local_datetime = new DateTime("@$timestamp_local", $site_timezone);
        
        // 3. Convert this local time to UTC (GMT) for universal comparison
        $expiry_datetime_utc = $local_datetime->setTimezone(new DateTimeZone('UTC'));

        // 4. Get the current time in UTC
        $current_datetime_utc = new DateTime('now', new DateTimeZone('UTC'));

        // If the current time is greater than or equal to the expiration time, it's expired.
        return $current_datetime_utc >= $expiry_datetime_utc;

    } catch ( Exception $e ) {
        error_log( 'DPDFG Expiry Check Error for attachment ' . $attachment_id . ': ' . $e->getMessage() );
        return false; 
    }
}


// --- Attachment Meta Field Display ---

/**
 * Adds the Expiration Date and Time fields to the PDF attachment editor.
 *
 * @param array $fields Existing fields.
 * @param WP_Post $post Attachment object.
 * @return array Modified fields.
 */
function dpdfg_add_expiration_fields( $fields, $post ) {
    // Only target PDF files
    if ( 'application/pdf' !== $post->post_mime_type ) {
        return $fields;
    }

    // FIX: Read raw local values directly from meta (no conversion needed for display)
    $date_value = get_post_meta( $post->ID, DPDFG_EXPIRY_DATE_KEY, true );
    $time_value = get_post_meta( $post->ID, DPDFG_EXPIRY_TIME_KEY, true );
    
    // Set default time for display if date is set but time isn't explicitly saved
    if ( empty($time_value) && !empty($date_value) ) {
        $time_value = '00:00:00';
    }


    // --- 1. PDF Expiration Date Field ---
    $fields['dpdfg_expiration_date_field'] = array(
        'input' => 'text', 
        'label' => esc_html__( 'PDF Expiration Date (YYYY-MM-DD)', 'dynamic-pdf-gallery' ),
        'value' => esc_attr( $date_value ),
        'helps' => esc_html__( 'Enter the date in YYYY-MM-DD format (e.g., 2025-12-31).', 'dynamic-pdf-gallery' ),
    );

    // --- 2. PDF Expiration Time Field ---
    $fields['dpdfg_expiration_time_field'] = array(
        'input' => 'text', 
        'label' => esc_html__( 'PDF Expiration Time (HH:MM:SS 24h)', 'dynamic-pdf-gallery' ),
        'value' => esc_attr( $time_value ),
        'helps' => esc_html__( 'Enter the time in 24-hour format (e.g., 14:30:00).', 'dynamic-pdf-gallery' ),
    );

    return $fields;
}
add_filter( 'attachment_fields_to_edit', 'dpdfg_add_expiration_fields', 10, 2 );


// --- Attachment Meta Field Saving ---

/**
 * Saves the custom date and time fields directly as local strings.
 *
 * @param int $attachment_id The attachment ID.
 * @return void
 */
function dpdfg_save_expiration_fields( $attachment_id ) {
    $date_field_key = 'dpdfg_expiration_date_field';
    $time_field_key = 'dpdfg_expiration_time_field';

    // Check if the date field was submitted.
    if ( isset( $_REQUEST['attachments'][ $attachment_id ][ $date_field_key ] ) ) {
        
        $date_input = sanitize_text_field( $_REQUEST['attachments'][ $attachment_id ][ $date_field_key ] );
        $time_input = isset( $_REQUEST['attachments'][ $attachment_id ][ $time_field_key ] ) ? 
                      sanitize_text_field( $_REQUEST['attachments'][ $attachment_id ][ $time_field_key ] ) : '';

        
        // --- Save Date ---
        if ( ! empty( $date_input ) ) {
            // Check if the date format is valid before saving
            if ( strtotime($date_input) === false ) {
                // If invalid date, delete both keys and exit.
                delete_post_meta( $attachment_id, DPDFG_EXPIRY_DATE_KEY );
                delete_post_meta( $attachment_id, DPDFG_EXPIRY_TIME_KEY );
                error_log('DPDFG Save Error: Invalid date format submitted: ' . $date_input);
                return;
            }
            update_post_meta( $attachment_id, DPDFG_EXPIRY_DATE_KEY, $date_input );
        } else {
            // If date is empty, delete both keys
            delete_post_meta( $attachment_id, DPDFG_EXPIRY_DATE_KEY );
            delete_post_meta( $attachment_id, DPDFG_EXPIRY_TIME_KEY );
            return;
        }
        
        // --- Save Time ---
        // Save time only if it's non-empty and the date is present.
        if ( ! empty( $time_input ) ) {
            update_post_meta( $attachment_id, DPDFG_EXPIRY_TIME_KEY, $time_input );
        } else {
            delete_post_meta( $attachment_id, DPDFG_EXPIRY_TIME_KEY );
        }
    }
}
add_action( 'edit_attachment', 'dpdfg_save_expiration_fields' );
