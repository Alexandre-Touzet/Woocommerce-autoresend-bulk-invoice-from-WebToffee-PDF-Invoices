<?php
/*
Plugin Name: Woocommerce autoresend bulk invoice from WebToffee PDF Invoices
Description: A plugin to periodically resend bulk invoice from an order status.
Version: 1.2
Author: Alexandre Touzet
Author URI: https://alexandretouzet.com
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/
require_once __DIR__ . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    exit;
}



function set_html_content_type() {
    return 'text/html';
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
        // Check if the button was clicked
        if (isset($_POST['wc_auto_resend_invoices_execute'])) {
            // Verify the nonce
            if (!wp_verify_nonce($_POST['_wpnonce'], 'wc_auto_resend_invoices_execute')) {
                die('Invalid request.');
        }
        // Execute the function
        wc_auto_resend_invoices();
    }
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
                        <th scope="row">Activate for Specific Accounting email</th>
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
                        <?php
                        $email_body = get_option('wc_auto_resend_invoices_email_body', 'Please find attached your invoices.');
                        $editor_id = 'wc_auto_resend_invoices_email_body';
                        $settings = array(
                            'textarea_name' => 'wc_auto_resend_invoices_email_body',
                            'textarea_rows' => 5,
                            'teeny'         => true,
                            'media_buttons' => false,
                        );
                        wp_editor($email_body, $editor_id, $settings);
                        ?>
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
            <form method="post">
            <?php wp_nonce_field('wc_auto_resend_invoices_execute'); ?>
            <input type="hidden" name="wc_auto_resend_invoices_execute" value="1">
            <?php submit_button('Execute Now'); ?>
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
            $grouped_invoices = array();
    
            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                $customer_id = $order->get_customer_id();
                $accounting_email = get_user_meta($customer_id, 'accounting_email', true);
                $invoice_number = get_post_meta($order_id, 'wf_invoice_number', true);
    
                error_log('Order ID: ' . $order_id . ' - Accounting Email: ' . $accounting_email);
    
                if (!empty($accounting_email)) {
                    if (empty($specific_customer_email) || $accounting_email === $specific_customer_email) {
                        if (!isset($grouped_invoices[$accounting_email])) {
                            $grouped_invoices[$accounting_email] = array();
                        }
                        $grouped_invoices[$accounting_email][] = $invoice_number;
                    }
                }
            }
    
            foreach ($grouped_invoices as $accounting_email => $invoice_numbers) {
                if (!empty($invoice_numbers)) {
                    $merged_invoice_path = generate_bulk_invoice_pdf($invoice_numbers);
    
                    $attachments = array($merged_invoice_path);
                    $headers = array(
                        'From: ' . $sender_name . ' <' . $sender_email . '>',
                        'Content-Type: text/html; charset=UTF-8',
                    );
    
                    add_filter('wp_mail_content_type', 'set_html_content_type');
                    $mail_sent = wp_mail($accounting_email, $email_subject, $email_body, $headers, $attachments);
                    if ($mail_sent) {
                        error_log('Email successfully sent to: ' . $accounting_email);
                    } else {
                        error_log('Failed to send email to: ' . $accounting_email);
                    }
                    wp_mail('compta@kera-catering.com', $email_subject, $email_body, $headers, $attachments);
                    remove_filter('wp_mail_content_type', 'set_html_content_type');
                    /*
    
                    if (file_exists($merged_invoice_path)) {
                        unlink($merged_invoice_path);
                        error_log('Merged PDF deleted after emailing: ' . $merged_invoice_path);
                    }*/
                }
            }
    
            if (empty($grouped_invoices)) {
                error_log('No invoice numbers collected for sending.');
            }
        } else {
            error_log('No orders found with the specified status: ' . $order_status);
        }
    }
    
    add_action('wc_auto_resend_invoices_cron', 'wc_auto_resend_invoices');

 

function generate_bulk_invoice_pdf($invoice_numbers) {
    // Define the directory path where individual invoice files are stored
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    // Create the directory if it doesn't exist
    $bulk_invoice_dir = $base_dir . '/wc-autoresend-bulk-invoice';
    if (!file_exists($bulk_invoice_dir)) {
        mkdir($bulk_invoice_dir, 0755, true);
    }

    // Initialize mPDF
    $mpdf = new \Mpdf\Mpdf();

    foreach ($invoice_numbers as $invoice_number) {
        // Construct the path to the individual invoice HTML file
        $invoice_path = $base_dir . "/print-invoices-packing-slip-labels-for-woocommerce/invoice/Facture_{$invoice_number}.html";

        // Logging the invoice file path
        error_log('Looking for Invoice HTML at Path: ' . $invoice_path);

        // Check if the invoice file exists and is readable
        if (is_readable($invoice_path)) {
            // Read the HTML content
            $html_content = file_get_contents($invoice_path);
            
            // Add a page break before each new invoice (except the first one)
            if ($mpdf->page != 0) {
                $mpdf->AddPage();
            }

            // Write the HTML content to the PDF
            $mpdf->WriteHTML($html_content);
        } else {
            error_log('Invoice HTML not found or not readable: ' . $invoice_path);
        }
    }

    // Specify where to save the PDF file
    $timestamp = time();
    $file_to_save = "$bulk_invoice_dir/Factures_$timestamp.pdf";

    // Save the PDF file
    $mpdf->Output($file_to_save, 'F');

    // Logging the generated PDF invoice path
    error_log('Generated PDF Invoice: ' . $file_to_save);

    // Return the path of the merged invoice
    return $file_to_save;
}
}