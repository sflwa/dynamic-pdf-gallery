=== Dynamic PDF Gallery === 
Contributors: sflwa
Tags: elementor, pdf, gallery, media folder
Requires at least: 5.0 
Tested up to: 6.8 
Requires PHP: 8.2
Stable tag: 2.1.0 
License: GPLv2 or later 
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An Elementor widget for dynamic PDF galleries, supporting FileBird/WPMF folders and a Media Library expiration date/time field.

== Description ==

The Dynamic PDF Gallery plugin provides a powerful Elementor widget designed to showcase collections of PDF documents from your WordPress Media Library.

**Key Features:**

-   **Elementor Integration:** Seamlessly add and configure PDF galleries within the Elementor editor.

-   **Dynamic Source Options:** Pull PDFs dynamically from:

    -   Manual selection.

    -   FileBird folders (supports Free and Pro).

    -   WP Media Folder folders.

-   **PDF Expiration Control:** Add custom date and time fields directly to PDF attachments in the Media Library. The gallery automatically hides expired documents based on the site's configured timezone.

-   **Layout Customization:** Control the number of columns, border radius, and typography of the PDF thumbnails and titles.

-   **Real-time Filtering:** Filters PDF results by MIME type (`application/pdf`) and automatically excludes any expired documents.

== Installation ==

1.  Upload the `dynamic-pdf-gallery` directory to the `/wp-content/plugins/` directory.

2.  Activate the plugin through the 'Plugins' menu in WordPress.

3.  In Elementor, search for the **Dynamic PDF Gallery** widget and drag it onto your page.

4.  Configure the **PDF Source** and **Layout** settings.

== Frequently Asked Questions ==

= How do I set an expiration date for a PDF? =

1.  Go to **Media > Library**.

2.  Click on the PDF file you wish to edit.

3.  In the attachment details sidebar (or edit screen), look for the fields **PDF Expiration Date (YYYY-MM-DD)** and **PDF Expiration Time (HH:MM:SS 24h)**.

4.  Enter the local site date and time when the PDF should be hidden. Leave both fields blank for the PDF to never expire.

= Which FileBird versions are supported? = The plugin supports both the free version (`filebird/filebird.php`) and the Pro version (`filebird-pro/filebird-pro.php`).

= The expiration date seems off by a few hours. Why? = The plugin stores the expiration date/time based on your WordPress site's configured timezone. When checking for expiration, it accurately compares the stored local date/time against the current time in the same timezone to determine visibility. Ensure your WordPress general settings have the correct timezone set.

== Screenshots ==

1.  Elementor widget controls for PDF Source selection.

2.  Media Library attachment edit screen showing the two Expiration Date/Time fields.

3.  Example of a multi-column PDF gallery layout on the front end.

== Changelog ==

= 2.1.0 =

-   **Feature:** Implemented full PDF Expiration system using custom text fields in the WordPress Media Library.

-   **Fix:** Added compatibility check for FileBird Free and Pro versions.

-   **Enhancement:** Refactored source classes to use the `dpdfg_is_pdf_expired()` utility function for filtering expired documents.

-   **Fix:** Resolved Elementor initialization conflict (`Class "Elementor\Widget_Base" not found`) by adjusting class loading order.

= 2.0.0 =

-   Initial release supporting Elementor, Manual, FileBird, and WP Media Folder sources.
