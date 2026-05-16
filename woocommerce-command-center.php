<?php
/**
 * Plugin Name: WooCommerce Command Center
 * Description: Custom admin dashboard for managing WooCommerce orders, revenue and customer follow-up workflows.
 * Version: 0.1.0
 * Author: Hernán Luis Gobulin
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LCCC_PLUGIN_VERSION', '0.1.0');
define('LCCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LCCC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Register admin assets only for the WooCommerce Command Center page.
 */
add_action('admin_enqueue_scripts', 'lccc_enqueue_admin_assets');

function lccc_enqueue_admin_assets($hook_suffix) {
    if ($hook_suffix !== 'toplevel_page_woocommerce-command-center') {
        return;
    }

    wp_enqueue_style(
        'lccc-admin-font',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'lccc-admin-style',
        LCCC_PLUGIN_URL . 'assets/admin.css',
        array(),
        filemtime(LCCC_PLUGIN_DIR . 'assets/admin.css')
    );
}

/**
 * Register the plugin admin page.
 */
add_action('admin_menu', 'lccc_register_admin_page');

function lccc_register_admin_page() {
    add_menu_page(
    'WooCommerce Command Center',
    'WooCommerce Center',
    'manage_options',
    'woocommerce-command-center',
    'lccc_render_admin_page',
    'dashicons-cart',
    56
);
}

/**
 * Get high-level dashboard statistics from WordPress and WooCommerce.
 */
function lccc_get_dashboard_stats() {
    $product_counts = wp_count_posts('product');

    $active_products = 0;
    $total_orders = 0;
    $monthly_revenue_by_currency = array();
    $pending_followups = 0;

    if ($product_counts && isset($product_counts->publish)) {
        $active_products = (int) $product_counts->publish;
    }

    if (function_exists('wc_get_orders')) {
        $completed_orders = wc_get_orders(array(
            'status' => array('processing', 'completed'),
            'limit' => -1,
            'return' => 'ids',
        ));

        $pending_orders = wc_get_orders(array(
            'status' => array('pending', 'on-hold'),
            'limit' => -1,
            'return' => 'ids',
        ));

        $monthly_orders = wc_get_orders(array(
            'status' => array('processing', 'completed'),
            'limit' => -1,
            'date_created' => date('Y-m-01') . '...' . date('Y-m-t'),
        ));

        foreach ($monthly_orders as $order) {
            $currency = $order->get_currency();

            if (empty($currency)) {
                $currency = get_woocommerce_currency();
            }

            if (!isset($monthly_revenue_by_currency[$currency])) {
                $monthly_revenue_by_currency[$currency] = 0;
            }

            $monthly_revenue_by_currency[$currency] += (float) $order->get_total();
        }

        $total_orders = count($completed_orders);
        $pending_followups = count($pending_orders);
    }

    return array(
        'active_products' => $active_products,
        'total_orders' => $total_orders,
        'monthly_revenue_by_currency' => $monthly_revenue_by_currency,
        'monthly_revenue_ars' => isset($monthly_revenue_by_currency['ARS']) ? $monthly_revenue_by_currency['ARS'] : 0,
        'monthly_revenue_usd' => isset($monthly_revenue_by_currency['USD']) ? $monthly_revenue_by_currency['USD'] : 0,
        'pending_followups' => $pending_followups,
    );
}

/**
 * Get the latest WooCommerce orders used in the recent orders table.
 */
function lccc_get_recent_orders() {
    if (!function_exists('wc_get_orders')) {
        return array();
    }

    $orders = wc_get_orders(array(
        'status' => array('processing', 'completed'),
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    $recent_orders = array();

    foreach ($orders as $order) {
        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $customer_dni = $order->get_meta('user_identity_number');
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();
        $order_status = $order->get_status();
        $date_created = $order->get_date_created();

        $item_names = lccc_get_order_item_names($order);
        $items_text = implode(', ', $item_names);

        $whatsapp_url = lccc_build_whatsapp_url(
            $customer_phone,
            $customer_name,
        );

        $recent_orders[] = array(
            'customer_name' => $customer_name,
            'customer_dni' => $customer_dni,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'items' => $items_text,
            'whatsapp_url' => $whatsapp_url,
            'date' => $date_created ? $date_created->date_i18n('d/m/Y') : '',
            'status' => $order_status,
        );
    }

    return $recent_orders;
}

/**
 * Return clean product names from a WooCommerce order.
 *
 * If the purchased item is a variation, the parent product name is used
 * to avoid showing pricing/variation labels in the dashboard.
 */
function lccc_get_order_item_names($order) {
    $item_names = array();

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        if (!$product) {
            $item_names[] = $item->get_name();
            continue;
        }

        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());

            if ($parent_product) {
                $item_names[] = $parent_product->get_name();
                continue;
            }
        }

        $item_names[] = $product->get_name();
    }

    return array_unique($item_names);
}

/**
 * Format phone numbers for WhatsApp links.
 */
function lccc_format_phone_for_whatsapp($phone) {
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);

    if (empty($clean_phone)) {
        return '';
    }

    if (strpos($clean_phone, '54') !== 0) {
        $clean_phone = '54' . $clean_phone;
    }

    return $clean_phone;
}

/**
 * Build a prefilled WhatsApp URL for customer follow-up.
 */
function lccc_build_whatsapp_url($phone, $customer_name) {
$whatsapp_phone = lccc_format_phone_for_whatsapp($phone);
    if (empty($whatsapp_phone)) {
      return '';
    }

    $site_name = get_bloginfo('name');

    $message = sprintf(
      'Hola %s, nos comunicamos desde %s.',
      $customer_name,
      $site_name
    );

    return 'https://wa.me/' . $whatsapp_phone . '?text=' . rawurlencode($message);
}

/**
 * Get Calendar widget data.
 *
 * This first version keeps the widget safe and lightweight:
 * no OAuth, no external requests, and no sensitive data.
 * Future versions can replace this data source with Google Calendar API.
 */
function lccc_get_calendar_widget_data() {
    return array(
        'title' => 'No event connected yet.',
        'datetime' => '',
        'meta' => 'Google Calendar integration pending.',
        'url' => 'https://calendar.google.com/',
    );
}

/**
 * Get Gmail Signals widget data.
 *
 * This first version keeps the widget safe and non-invasive:
 * no OAuth, no external requests, and no email content access.
 * Future versions can replace this placeholder with Gmail API signals.
 */
function lccc_get_gmail_signals_widget_data() {
    return array(
        'unread_emails' => null,
        'pending_replies' => null,
        'updates' => null,
        'url' => 'https://mail.google.com/',
    );
}

/**
 * Get Operational Tasks widget data.
 *
 * This first version keeps tasks as a non-persistent placeholder.
 * Future versions can replace this with stored WordPress options,
 * a custom database table, or an internal task post type.
 */
function lccc_get_operational_tasks_widget_data() {
    return array(
        'items' => array(
            array(
                'label' => 'Follow-up',
                'priority' => 'Priority',
                'status' => 'Status',
            ),
        ),
        'meta' => 'Task module pending.',
    );
}

/**
 * Format revenue safely, even if WooCommerce is inactive.
 */
function lccc_format_revenue($amount) {
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }

    return '$ ' . number_format((float) $amount, 2, ',', '.');
}

/**
 * Render the admin dashboard template.
 */
function lccc_render_admin_page() {
    $stats = lccc_get_dashboard_stats();
    $recent_orders = lccc_get_recent_orders();
    $calendar_widget = lccc_get_calendar_widget_data();
    $gmail_signals_widget = lccc_get_gmail_signals_widget_data();
    $operational_tasks_widget = lccc_get_operational_tasks_widget_data();

    $active_products = $stats['active_products'];
    $total_orders = $stats['total_orders'];
    $monthly_revenue_ars = $stats['monthly_revenue_ars'];
    $monthly_revenue_usd = $stats['monthly_revenue_usd'];
    $pending_followups = $stats['pending_followups'];

    require LCCC_PLUGIN_DIR . 'templates/admin-dashboard.php';
}
