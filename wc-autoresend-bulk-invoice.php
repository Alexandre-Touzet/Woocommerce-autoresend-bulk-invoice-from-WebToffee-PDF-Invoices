<?php
/*
Plugin Name: Woocommerce autoresend bulk invoice from WebToffee PDF Invoices
Description: A plugin to periodically resend bulk invoice from an order status.
Version: 1.0
Author: Alexandre Touzet
Author URI: https://alexandretouzet.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) {
    exit;
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function wc_auto_resend_invoices_admin_menu() {
        add_submenu_page('woocommerce', 'Auto Resend Invoices', 'Auto Resend Invoices', 'manage_woocommerce', 'wc-auto-resend-invoices', 'wc_auto_resend_invoices_settings_page');
    }
    add_action('admin_menu', 'wc_auto_resend_invoices_admin_menu');

    function wc_auto_resend_invoices_register_settings() {
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_day');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_hour');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_customer_email');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_for_all');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_sender_name');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_sender_email');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_email_subject');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_email_body');
        register_setting('wc_auto_resend_invoices', 'wc_auto_resend_invoices_order_status');
    }
    add_action('admin_init', 'wc_auto_resend_invoices_register_settings');

    function wc_auto_resend_invoices_settings_page() {
        ?>
        <div class="wrap">
            <h1>Auto Resend Invoices</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_auto_resend_invoices');
                do_settings_sections('wc_auto_resend_invoices');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Day of the Week</th>
                        <td>
                            <select name="wc_auto_resend_invoices_day">
                                <?php
                                $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
                                $selected_day = get_option('wc_auto_resend_invoices_day', 'Saturday');
                                foreach ($days as $day) {
                                    echo '<option value="' . $day . '"' . selected($selected_day, $day, false) . '>' . $day . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Hour of the Day (24-hour format)</th>
                        <td>
                            <input type="number" name="wc_auto_resend_invoices_hour" min="0" max="23" value="<?php echo esc_attr(get_option('wc_auto_resend_invoices_hour', 0)); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Activate for Specific Customer</th>
                        <td>
                            <input type="email" name="wc_auto_resend_invoices_customer_email" value="<?php echo esc_attr(get_option('wc_auto_resend_invoices_customer_email', '')); ?>">
                            <p class="description">Leave empty to apply for all customers</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sender Name</th>
                        <td>
                            <input type="text" name="wc_auto_resend_invoices_sender_name" value="<?php echo esc_attr(get_option('wc_auto_resend_invoices_sender_name', '')); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sender Email</th>
                        <td>
                            <input type="email" name="wc_auto_resend_invoices_sender_email" value="<?php echo esc_attr(get_option('wc_auto_resend_invoices_sender_email', '')); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Email Subject</th>
                        <td>
                            <input type="text" name="wc_auto_resend_invoices_email_subject" value="<?php echo esc_attr(get_option('wc_auto_resend_invoices_email_subject', 'Your Invoices')); ?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Email Body</th>
                        <td>
                            <textarea name="wc_auto_resend_invoices_email_body"><?php echo esc_textarea(get_option('wc_auto_resend_invoices_email_body', 'Please find attached your invoices.')); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Order Status</th>
                        <td>
                            <select name="wc_auto_resend_invoices_order_status">
                                <?php
                                $order_statuses = wc_get_order_statuses();
                                $selected_order_status = get_option('wc_auto_resend_invoices_order_status', 'completed');
                                foreach ($order_statuses as $status => $label) {
                                    echo '<option value="' . $status . '"' . selected($selected_order_status, $status, false) . '>' . $label . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    function wc_auto_resend_invoices_schedule() {
        if (!wp_next_scheduled('wc_auto_resend_invoices_cron')) {
            $selected_day = get_option('wc_auto_resend_invoices_day', 'Saturday');
            $selected_hour = get_option('wc_auto_resend_invoices_hour', 0);

            $current_time = current_time('timestamp');
            $scheduled_time = strtotime("next {$selected_day} {$selected_hour}:00:00");
            if ($scheduled_time < $current_time) {
                $scheduled_time += WEEK_IN_SECONDS;
            }

            wp_schedule_event($scheduled_time, 'weekly', 'wc_auto_resend_invoices_cron');
        }
    }
    add_action('wp', 'wc_auto_resend_invoices_schedule');

    function wc_auto_resend_invoices_clear_schedule() {
        wp_clear_scheduled_hook('wc_auto_resend_invoices_cron');
        wc_auto_resend_invoices_schedule();
    }
    add_action('update_option_wc_auto_resend_invoices_day', 'wc_auto_resend_invoices_clear_schedule');
    add_action('update_option_wc_auto_resend_invoices_hour', 'wc_auto_resend_invoices_clear_schedule');

    function wc_auto_resend_invoices() {
        $specific_customer_email = sanitize_email(get_option('wc_auto_resend_invoices_customer_email', ''));
        $sender_name = sanitize_text_field(get_option('wc_auto_resend_invoices_sender_name', ''));
        $sender_email = sanitize_email(get_option('wc_auto_resend_invoices_sender_email', ''));
        $email_subject = sanitize_text_field(get_option('wc_auto_resend_invoices_email_subject', 'Your Invoices'));
        $email_body = wp_kses_post(get_option('wc_auto_resend_invoices_email_body', 'Please find attached your invoices.'));
        $order_status = sanitize_text_field(get_option('wc_auto_resend_invoices_order_status', 'completed'));

        $args = array(
            'status' => $order_status,
            'limit' => -1,
            'return' => 'ids',
        );
        $orders = wc_get_orders($args);

        if (!empty($orders)) {
            $invoice_numbers = array();

            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                $customer_email = sanitize_email($order->get_billing_email());
                $invoice_number = get_post_meta($order_id, 'wf_invoice_number', true);

                if (empty($specific_customer_email) || $customer_email === $specific_customer_email) {
                    $invoice_numbers[] = $invoice_number; // Save the invoice number
                }
            }

            if (!empty($invoice_numbers)) {
                // Generate and merge the PDF files for the relevant invoice numbers
                $merged_invoice_path = generate_bulk_invoice_pdf($invoice_numbers);

                // Prepare and send the email
                $attachments = array($merged_invoice_path);
                $headers = array(
                    'From: ' . $sender_name . ' <' . $sender_email . '>',
                    'Content-Type: text/html; charset=UTF-8',
                );

                wp_mail($specific_customer_email, $email_subject, $email_body, $headers, $attachments);

                // Delete the merged invoice file
                if (file_exists($merged_invoice_path)) {
                    unlink($merged_invoice_path);
                }
            }
        }
    }
    add_action('wc_auto_resend_invoices_cron', 'wc_auto_resend_invoices');

    require_once __DIR__ . '/vendor/autoload.php';

    // Avoid direct calls to this file
    if (!defined('ABSPATH')) {
        exit;
    }

    // Function to generate the bulk invoice PDF
    function generate_bulk_invoice_pdf($invoice_numbers) {
        // Define the directory path where individual invoice files are stored
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $base_url = $upload_dir['baseurl'];

        // Create the directory if it doesn't exist
        $bulk_invoice_dir = $base_dir . '/wc-autoresend-bulk-invoice';
        if (!file_exists($bulk_invoice_dir)) {
            mkdir($bulk_invoice_dir, 0755, true);
        }

        // Initialize FPDI
        $pdf = new \setasign\Fpdi\Fpdi();

        foreach ($invoice_numbers as $invoice_number) {
            // Construct the path to the individual invoice file
            $invoice_path = $base_dir . "/wf-woocommerce-packing-list/invoice/Facture_{$invoice_number}.pdf";

            // Logging the invoice file path
            error_log('Looking for Invoice PDF at Path: ' . $invoice_path);

            // Check if the invoice file exists and is readable
            if (is_readable($invoice_path)) {
                $pageCount = $pdf->setSourceFile($invoice_path);
                // Iterate through all pages
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // Import a page
                    $templateId = $pdf->importPage($pageNo);
                    // Get the size of the imported page
                    $size = $pdf->getTemplateSize($templateId);

                    // Create a page (landscape or portrait depending on the imported page size)
                    if ($size['width'] > $size['height']) {
                        $pdf->AddPage('L', array($size['width'], $size['height']));
                    } else {
                        $pdf->AddPage('P', array($size['width'], $size['height']));
                    }

                    // Use the imported page
                    $pdf->useTemplate($templateId);
                }
            }
        }

        // Specify where to save the PDF file
        $file_to_save = $bulk_invoice_dir . '/Factures_' . implode('-', $invoice_numbers) . '.pdf';

        // Save the PDF file
        $pdf->Output($file_to_save, 'F');

        // Logging the generated PDF invoice path
        error_log('Generated PDF Invoice: ' . $file_to_save);

        // Return the path of the merged invoice
        return $file_to_save;
    }
}
