<?php
/**
 * Dynamic PDF Gallery: Attachment Meta Fields
 * Handles adding and saving the PDF Expiry Date field in the WordPress Media Library.
 * Also contains the global utility function for checking a PDF's expiration status.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// --- Attachment Meta Field for Expiration Date ---

/**
 * Adds the 'Expiration Date' field to the PDF attachment editor in the Media Library.
 *
 * @param array $fields Existing fields.
 * @param WP_Post $post Attachment object.
 * @return array Modified fields.
 */
function dpdfg_add_expiration_field( $fields, $post ) {
    if ( 'application/pdf' !== $post->post_mime_type ) {
        return $fields;
    }

    $expiration_date_gmt = get_post_meta( $post->ID, '_dpdfg_expiration_date', true );
    
    // We use wp_date to format the GMT date into the required HTML datetime-local format.
    // This allows the browser to display the date in the user's local time (though the value is not timezone-aware).
    $input_value = '';
    if ( $expiration_date_gmt ) {
        // Convert GMT timestamp to the format required by datetime-local input (Y-m-d\TH:i)
        // Ensure that strtotime handles the GMT date string correctly.
        $input_value = wp_date( 'Y-m-d\TH:i', strtotime( $expiration_date_gmt ) );
    }

    $fields['_dpdfg_expiration_date'] = array(
        'label' => esc_html__( 'PDF Expiry Date', 'dynamic-pdf-gallery' ),
        'input' => 'html',
        'html'  => sprintf(
            '<input type="datetime-local" class="text" name="attachments[%d][_dpdfg_expiration_date]" value="%s" style="width: 100%%;">',
            $post->ID,
            esc_attr( $input_value )
        ),
        'value' => $input_value,
        'description' => esc_html__( 'The PDF will be hidden from the gallery after this date/time (based on your WordPress general settings timezone). Leave empty to never expire.', 'dynamic-pdf-gallery' ),
    );

    return $fields;
}
add_filter( 'attachment_fields_to_edit', 'dpdfg_add_expiration_field', 10, 2 );

/**
 * Saves the 'Expiration Date' field value, converting it to GMT for storage.
 *
 * @param int $attachment_id The attachment ID.
 * @return void
 */
function dpdfg_save_expiration_field( $attachment_id ) {
    // Only proceed if the field was submitted for this attachment.
    if ( ! isset( $_REQUEST['attachments'][ $attachment_id ]['_dpdfg_expiration_date'] ) ) {
        return;
    }

    // Sanitize the input value (local time string).
    $date_local = sanitize_text_field( $_REQUEST['attachments'][ $attachment_id ]['_dpdfg_expiration_date'] );

    if ( empty( $date_local ) ) {
        delete_post_meta( $attachment_id, '_dpdfg_expiration_date' );
        return;
    }

    // Convert local time string to a UNIX timestamp
    // WordPress functions handle timezone conversion based on site settings.
    $timestamp_local = strtotime( $date_local );
    
    // Convert UNIX timestamp to GMT MySQL format (Y-m-d H:i:s) for consistent database storage.
    $date_gmt = gmdate( 'Y-m-d H:i:s', $timestamp_local );

    update_post_meta( $attachment_id, '_dpdfg_expiration_date', $date_gmt );
}
add_action( 'edit_attachment', 'dpdfg_save_expiration_field' );


/**
 * Helper function to check if a PDF attachment has expired based on its meta field.
 *
 * @param int $attachment_id The ID of the PDF attachment.
 * @return bool True if expired, false otherwise.
 */
function dpdfg_is_pdf_expired( $attachment_id ) {
    $expiration_date_gmt = get_post_meta( $attachment_id, '_dpdfg_expiration_date', true );

    if ( empty( $expiration_date_gmt ) ) {
        return false;
    }

    try {
        // The stored date is GMT, so we create a DateTime object with UTC timezone.
        $expiry_datetime = new DateTime( $expiration_date_gmt, new DateTimeZone( 'UTC' ) );
        
        // Get the current time in UTC for comparison.
        $current_datetime = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

        // If the current time is greater than or equal to the expiration time, it's expired.
        return $current_datetime >= $expiry_datetime;

    } catch ( Exception $e ) {
        // If date parsing fails, default to not expired.
        error_log( 'DPDFG Expiry Check Error for attachment ' . $attachment_id . ': ' . $e->getMessage() );
        return false; 
    }
}
