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

    wp_enqueue_script(
        'lccc-admin-script',
        LCCC_PLUGIN_URL . 'assets/admin.js',
        array(),
        filemtime(LCCC_PLUGIN_DIR . 'assets/admin.js'),
        true
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
 * Try to extract an Open Graph / Twitter Card image from a remote article URL.
 *
 * Results are cached to avoid repeated external requests on every dashboard load.
 */
function lccc_extract_remote_page_image_url($url) {
    if (empty($url)) {
        return '';
    }

    $cache_key = 'lccc_news_image_v2_' . md5($url);
    $cached_image = get_transient($cache_key);

    if (false !== $cached_image) {
        return $cached_image;
    }

    $response = wp_safe_remote_get($url, array(
        'timeout' => 4,
        'redirection' => 3,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 WooCommerce Command Center News Bot',
        ),
    ));

    if (is_wp_error($response)) {
        set_transient($cache_key, '', 6 * HOUR_IN_SECONDS);
        return '';
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 400) {
        set_transient($cache_key, '', 6 * HOUR_IN_SECONDS);
        return '';
    }

    $html = wp_remote_retrieve_body($response);

    if (empty($html)) {
        set_transient($cache_key, '', 6 * HOUR_IN_SECONDS);
        return '';
    }

    $patterns = array(
        '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i',
        '/<meta[^>]+property=["\']og:image:secure_url["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image:secure_url["\']/i',
        '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i',
        '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\']/i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $image_url = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $parsed_url = wp_parse_url($url);

            if (0 === strpos($image_url, '//')) {
                $scheme = !empty($parsed_url['scheme']) ? $parsed_url['scheme'] : 'https';
                $image_url = $scheme . ':' . $image_url;
            }

            if (0 === strpos($image_url, '/') && !empty($parsed_url['scheme']) && !empty($parsed_url['host'])) {
                $image_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $image_url;
            }

            $image_url = esc_url_raw($image_url);
            if (false !== strpos($image_url, 'googleusercontent.com') || false !== strpos($image_url, 'gstatic.com')) {
                set_transient($cache_key, '', 6 * HOUR_IN_SECONDS);
                return '';
            }
    }

    set_transient($cache_key, '', 6 * HOUR_IN_SECONDS);

    return '';
}
}

/**
 * Try to extract a representative image URL from a feed item.
 *
 * Supports common RSS patterns:
 * - media:content / media:thumbnail
 * - enclosure images
 * - first image found in description/content HTML
 * - remote Open Graph / Twitter Card image
 */
function lccc_extract_feed_item_image_url($feed_item) {
    if (!$feed_item) {
        return '';
    }

    $media_content = $feed_item->get_item_tags('http://search.yahoo.com/mrss/', 'content');

    if (!empty($media_content[0]['attribs']['']['url'])) {
        return esc_url_raw($media_content[0]['attribs']['']['url']);
    }

    $media_thumbnail = $feed_item->get_item_tags('http://search.yahoo.com/mrss/', 'thumbnail');

    if (!empty($media_thumbnail[0]['attribs']['']['url'])) {
        return esc_url_raw($media_thumbnail[0]['attribs']['']['url']);
    }

    $enclosure = $feed_item->get_enclosure();

    if ($enclosure) {
        $enclosure_link = $enclosure->get_link();
        $enclosure_type = $enclosure->get_type();

        if (!empty($enclosure_link) && !empty($enclosure_type) && 0 === strpos($enclosure_type, 'image/')) {
            return esc_url_raw($enclosure_link);
        }
    }

    $description = $feed_item->get_description();

    if (!empty($description) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $description, $matches)) {
        return esc_url_raw($matches[1]);
    }

    $content = $feed_item->get_content();

    if (!empty($content) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
        return esc_url_raw($matches[1]);
    }

    $remote_image_url = lccc_extract_remote_page_image_url($feed_item->get_permalink());

    if (!empty($remote_image_url)) {
        return $remote_image_url;
    }

    return '';
}

/**
 * Get Trends & News widget data.
 *
 * Uses WordPress native RSS handling with a safe fallback.
 * Results are lightweight and intended for dashboard-level operational awareness.
 */
function lccc_get_trends_news_widget_data() {
    $fallback_items = array(
        array(
            'title' => 'Holistic wellness updates pending.',
            'source' => 'WooCommerce Command Center',
            'date' => '',
            'url' => 'https://news.google.com/',
            'image_url' => '',
        ),
        array(
            'title' => 'Connect custom RSS sources to show industry-specific updates.',
            'source' => 'Dashboard Feed',
            'date' => '',
            'url' => 'https://news.google.com/',
            'image_url' => '',
        ),
    );

    if (!function_exists('fetch_feed')) {
        include_once ABSPATH . WPINC . '/feed.php';
    }

    if (!function_exists('fetch_feed')) {
        return array(
            'label' => 'Market Signals',
            'items' => $fallback_items,
            'meta' => 'RSS unavailable in this environment.',
        );
    }

    $feed_urls = array(
        'https://news.google.com/rss/search?q=(yoga%20OR%20mindfulness%20OR%20holistic%20wellness%20OR%20natural%20health%20OR%20integrative%20health%20OR%20psychology%20OR%20mental%20health%20OR%20beauty%20wellness)%20when:14d&hl=es-419&gl=AR&ceid=AR:es-419',
        'https://news.google.com/rss/search?q=(terapias%20hol%C3%ADsticas%20OR%20bienestar%20integral%20OR%20yoga%20OR%20meditaci%C3%B3n%20OR%20salud%20natural%20OR%20psicolog%C3%ADa%20OR%20salud%20mental)%20when:14d&hl=es-419&gl=AR&ceid=AR:es-419',
    );

    $items = array();

    foreach ($feed_urls as $feed_url) {
        $feed = fetch_feed($feed_url);

        if (is_wp_error($feed)) {
            continue;
        }

        $max_items = $feed->get_item_quantity(4);
        $feed_items = $feed->get_items(0, $max_items);

        foreach ($feed_items as $feed_item) {
            $items[] = array(
                'title' => wp_strip_all_tags($feed_item->get_title()),
                'source' => wp_strip_all_tags($feed->get_title()),
                'date' => $feed_item->get_date('d/m/Y'),
                'url' => esc_url_raw($feed_item->get_permalink()),
                'image_url' => lccc_extract_feed_item_image_url($feed_item),
            );
        }
    }

    $image_items = array_values(array_filter($items, function ($item) {
        return !empty($item['image_url']);
    }));

    if (!empty($image_items)) {
        $items = $image_items;
    }

    if (empty($items)) {
        $items = $fallback_items;
    }

    return array(
        'label' => 'Market Signals',
        'items' => array_slice($items, 0, 8),
        'meta' => !empty($image_items) ? 'Latest visual RSS updates.' : 'Latest RSS updates.',
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
    $trends_news_widget = lccc_get_trends_news_widget_data();

    $active_products = $stats['active_products'];
    $total_orders = $stats['total_orders'];
    $monthly_revenue_ars = $stats['monthly_revenue_ars'];
    $monthly_revenue_usd = $stats['monthly_revenue_usd'];
    $pending_followups = $stats['pending_followups'];

    require LCCC_PLUGIN_DIR . 'templates/admin-dashboard.php';
}
