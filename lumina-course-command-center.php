<?php
/**
 * Plugin Name: WooCommerce Command Center
 * Description: Custom admin dashboard for managing WooCommerce course enrollments and student follow-up workflows.
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

    $active_courses = 0;
    $total_enrollments = 0;
    $monthly_revenue = 0;
    $pending_followups = 0;

    if ($product_counts && isset($product_counts->publish)) {
        $active_courses = (int) $product_counts->publish;
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
            $monthly_revenue += (float) $order->get_total();
        }

        $total_enrollments = count($completed_orders);
        $pending_followups = count($pending_orders);
    }

    return array(
        'active_courses' => $active_courses,
        'total_enrollments' => $total_enrollments,
        'monthly_revenue' => $monthly_revenue,
        'pending_followups' => $pending_followups,
    );
}

/**
 * Get the latest WooCommerce orders used as recent course enrollments.
 */
function lccc_get_recent_enrollments() {
    if (!function_exists('wc_get_orders')) {
        return array();
    }

    $orders = wc_get_orders(array(
        'status' => array('processing', 'completed'),
        'limit' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    $recent_enrollments = array();

    foreach ($orders as $order) {
        $student_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $student_dni = $order->get_meta('user_identity_number');
        $student_email = $order->get_billing_email();
        $student_phone = $order->get_billing_phone();
        $order_status = $order->get_status();
        $date_created = $order->get_date_created();

        $course_names = lccc_get_order_course_names($order);
        $courses_text = implode(', ', $course_names);

        $whatsapp_url = lccc_build_whatsapp_url(
            $student_phone,
            $student_name,
            $courses_text
        );

        $recent_enrollments[] = array(
            'student_name' => $student_name,
            'student_dni' => $student_dni,
            'student_email' => $student_email,
            'student_phone' => $student_phone,
            'courses' => $courses_text,
            'whatsapp_url' => $whatsapp_url,
            'date' => $date_created ? $date_created->date_i18n('d/m/Y') : '',
            'status' => $order_status,
        );
    }

    return $recent_enrollments;
}

/**
 * Return clean product names from a WooCommerce order.
 *
 * If the purchased item is a variation, the parent product name is used
 * to avoid showing pricing/variation labels in the dashboard.
 */
function lccc_get_order_course_names($order) {
    $course_names = array();

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        if (!$product) {
            $course_names[] = $item->get_name();
            continue;
        }

        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());

            if ($parent_product) {
                $course_names[] = $parent_product->get_name();
                continue;
            }
        }

        $course_names[] = $product->get_name();
    }

    return array_unique($course_names);
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
 * Build a prefilled WhatsApp URL for student follow-up.
 */
function lccc_build_whatsapp_url($phone, $student_name, $courses_text) {
    $whatsapp_phone = lccc_format_phone_for_whatsapp($phone);

    if (empty($whatsapp_phone)) {
        return '';
    }

    $message = sprintf(
        'Hola %s, te escribimos desde Lúmina Académica por tu inscripción al curso: %s.',
        $student_name,
        $courses_text
    );

    return 'https://wa.me/' . $whatsapp_phone . '?text=' . rawurlencode($message);
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
    $recent_enrollments = lccc_get_recent_enrollments();

    $active_courses = $stats['active_courses'];
    $total_enrollments = $stats['total_enrollments'];
    $monthly_revenue = $stats['monthly_revenue'];
    $pending_followups = $stats['pending_followups'];

    require LCCC_PLUGIN_DIR . 'templates/admin-dashboard.php';
}
