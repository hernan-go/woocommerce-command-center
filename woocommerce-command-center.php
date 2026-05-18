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
add_action('admin_init', 'lccc_handle_operational_task_actions');

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
 * Handle Operational Tasks form submissions.
 */
function lccc_handle_operational_task_actions() {
    if (!is_admin()) {
        return;
    }

    if (empty($_POST['lccc_task_action'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    check_admin_referer('lccc_operational_tasks_action', 'lccc_operational_tasks_nonce');

    $action = sanitize_text_field(wp_unslash($_POST['lccc_task_action']));

    if ('add_task' === $action) {
        $task_title = isset($_POST['lccc_task_title'])
            ? sanitize_text_field(wp_unslash($_POST['lccc_task_title']))
            : '';

        $task_priority = isset($_POST['lccc_task_priority'])
            ? sanitize_text_field(wp_unslash($_POST['lccc_task_priority']))
            : 'Medium';

        if (!empty($task_title)) {
            lccc_add_operational_task($task_title, $task_priority);
        }
    }

    if ('complete_task' === $action && !empty($_POST['lccc_task_id'])) {
        $task_id = sanitize_text_field(wp_unslash($_POST['lccc_task_id']));
        lccc_complete_operational_task($task_id);
    }

    wp_safe_redirect(admin_url('admin.php?page=woocommerce-command-center'));
    exit;
}

/**
 * Get stored Operational Tasks.
 */
function lccc_get_operational_tasks() {
    $tasks = get_option('lccc_operational_tasks', array());

    if (!is_array($tasks)) {
        return array();
    }

    return $tasks;
}

/**
 * Save Operational Tasks.
 */
function lccc_save_operational_tasks($tasks) {
    update_option('lccc_operational_tasks', array_values($tasks), false);
}

/**
 * Add a new Operational Task.
 */
function lccc_add_operational_task($title, $priority = 'Medium') {
    $allowed_priorities = array('Low', 'Medium', 'High');

    if (!in_array($priority, $allowed_priorities, true)) {
        $priority = 'Medium';
    }

    $tasks = lccc_get_operational_tasks();

    $tasks[] = array(
        'id' => uniqid('task_', true),
        'title' => $title,
        'priority' => $priority,
        'status' => 'Pending',
        'created_at' => current_time('mysql'),
    );

    lccc_save_operational_tasks($tasks);
}

/**
 * Mark an Operational Task as completed.
 */
function lccc_complete_operational_task($task_id) {
    $tasks = lccc_get_operational_tasks();

    foreach ($tasks as $index => $task) {
        if (!empty($task['id']) && $task['id'] === $task_id) {
            unset($tasks[$index]);
            break;
        }
    }

    lccc_save_operational_tasks($tasks);
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
 * Get configured iCal calendar feeds.
 *
 * Private calendar URLs are loaded from config/calendar-feeds.php,
 * which is ignored by Git.
 */
function lccc_get_calendar_feeds() {
    $config_file = LCCC_PLUGIN_DIR . 'config/calendar-feeds.php';

    if (!file_exists($config_file)) {
        return array();
    }

    $feeds = require $config_file;

    if (!is_array($feeds)) {
        return array();
    }

    return array_values(array_filter($feeds, function ($feed) {
        return (
            is_array($feed)
            && !empty($feed['name'])
            && !empty($feed['url'])
        );
    }));
}

/**
 * Parse an iCal date value into a Unix timestamp.
 */
function lccc_parse_ical_datetime($value) {
    if (empty($value)) {
        return 0;
    }

    $value = trim($value);

    if (preg_match('/^\d{8}$/', $value)) {
        $datetime = DateTime::createFromFormat('Ymd', $value, wp_timezone());
    } else {
        $datetime = DateTime::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));

        if (!$datetime) {
            $datetime = DateTime::createFromFormat('Ymd\THis', $value, wp_timezone());
        }
    }

    if (!$datetime) {
        return 0;
    }

    $datetime->setTimezone(wp_timezone());

    return $datetime->getTimestamp();
}

/**
 * Decode basic iCal escaped text.
 */
function lccc_decode_ical_text($value) {
    $value = str_replace(array('\n', '\N'), ' ', $value);
    $value = str_replace(array('\,', '\;'), array(',', ';'), $value);

    return trim(wp_strip_all_tags($value));
}

/**
 * Read events from a single iCal feed URL.
 */
function lccc_get_events_from_ical_feed($feed_name, $feed_url) {
    if (empty($feed_url)) {
        return array();
    }

    $response = wp_safe_remote_get($feed_url, array(
        'timeout' => 8,
        'redirection' => 3,
    ));

    if (is_wp_error($response)) {
        return array();
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 400) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);

    if (empty($body)) {
        return array();
    }

    $body = preg_replace("/\r\n[ \t]/", '', $body);
    $body = preg_replace("/\n[ \t]/", '', $body);

    preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $body, $matches);

    if (empty($matches[1])) {
        return array();
    }

    $events = array();

    foreach ($matches[1] as $raw_event) {
        $summary = '';
        $start_timestamp = 0;

        if (preg_match('/SUMMARY(?:;[^:]*)?:(.+)/', $raw_event, $summary_match)) {
            $summary = lccc_decode_ical_text($summary_match[1]);
        }

        if (preg_match('/DTSTART(?:;[^:]*)?:(.+)/', $raw_event, $start_match)) {
            $start_timestamp = lccc_parse_ical_datetime($start_match[1]);
        }

        if (empty($summary) || empty($start_timestamp)) {
            continue;
        }

        if ($start_timestamp < current_time('timestamp')) {
            continue;
        }

        $events[] = array(
            'title' => $summary,
            'calendar' => $feed_name,
            'timestamp' => $start_timestamp,
            'date' => wp_date('d/m/Y', $start_timestamp),
            'time' => wp_date('H:i', $start_timestamp),
        );
    }

    return $events;
}

/**
 * Get Calendar widget data.
 *
 * Reads private iCal feeds and returns the next upcoming events.
 */
function lccc_get_calendar_widget_data() {
    $calendar_feeds = lccc_get_calendar_feeds();

    $gmail_config = function_exists('lccc_get_gmail_api_config') ? lccc_get_gmail_api_config() : array();
    $calendar_url = 'https://calendar.google.com/calendar/u/0/r';

    if (!empty($gmail_config['account_email'])) {
        $calendar_url = add_query_arg(
            array(
                'Email' => $gmail_config['account_email'],
                'continue' => 'https://calendar.google.com/calendar/u/0/r',
            ),
            'https://accounts.google.com/AccountChooser'
        );
    }

    if (empty($calendar_feeds)) {
        return array(
            'title' => 'No calendar connected yet.',
            'datetime' => '',
            'meta' => 'Add private iCal URLs to config/calendar-feeds.php.',
            'url' => $calendar_url,
            'events' => array(),
        );
    }

    $events = array();

    foreach ($calendar_feeds as $feed) {
        $feed_events = lccc_get_events_from_ical_feed($feed['name'], $feed['url']);

        if (!empty($feed_events)) {
            $events = array_merge($events, $feed_events);
        }
    }

    usort($events, function ($a, $b) {
        return $a['timestamp'] <=> $b['timestamp'];
    });

    $events = array_slice($events, 0, 5);

    if (empty($events)) {
        return array(
            'title' => 'No upcoming event found.',
            'datetime' => '',
            'meta' => count($calendar_feeds) . ' calendar feed(s) connected.',
            'url' => $calendar_url,
            'events' => array(),
        );
    }

    $next_event = $events[0];

    return array(
        'title' => $next_event['title'],
        'datetime' => $next_event['date'] . ' · ' . $next_event['time'],
        'meta' => $next_event['calendar'],
        'url' => $calendar_url,
        'events' => $events,
    );
}

/**
 * Get configured Gmail API credentials.
 *
 * Private Gmail credentials are loaded from config/gmail-api.php,
 * which is ignored by Git.
 */
function lccc_get_gmail_api_config() {
    $config_file = LCCC_PLUGIN_DIR . 'config/gmail-api.php';

    if (!file_exists($config_file)) {
        return array();
    }

    $config = require $config_file;

    if (!is_array($config)) {
        return array();
    }

    return array(
    'client_id' => isset($config['client_id']) ? trim($config['client_id']) : '',
    'client_secret' => isset($config['client_secret']) ? trim($config['client_secret']) : '',
    'redirect_uri' => isset($config['redirect_uri']) ? trim($config['redirect_uri']) : '',
    'refresh_token' => isset($config['refresh_token']) ? trim($config['refresh_token']) : '',
    'account_email' => isset($config['account_email']) ? trim($config['account_email']) : '',
  );
}

/**
 * Request a temporary Gmail API access token using the stored refresh token.
 */
function lccc_get_gmail_access_token($gmail_config) {
    if (
        empty($gmail_config['client_id'])
        || empty($gmail_config['client_secret'])
        || empty($gmail_config['refresh_token'])
    ) {
        return '';
    }

    $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'timeout' => 10,
        'body' => array(
            'client_id' => $gmail_config['client_id'],
            'client_secret' => $gmail_config['client_secret'],
            'refresh_token' => $gmail_config['refresh_token'],
            'grant_type' => 'refresh_token',
        ),
    ));

    if (is_wp_error($response)) {
        return '';
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300) {
        return '';
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['access_token'])) {
        return '';
    }

    return sanitize_text_field($body['access_token']);
}

/**
 * Perform a Gmail API GET request.
 */
function lccc_gmail_api_get($access_token, $endpoint, $query_args = array()) {
    if (empty($access_token) || empty($endpoint)) {
        return array();
    }

    $url = add_query_arg(
        $query_args,
        'https://gmail.googleapis.com/gmail/v1/users/me/' . ltrim($endpoint, '/')
    );

    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
        ),
    ));

    if (is_wp_error($response)) {
        return array();
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300) {
        return array();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return is_array($body) ? $body : array();
}

/**
 * Count unread Gmail messages matching a Gmail search query.
 *
 * Counts real returned messages instead of relying on resultSizeEstimate,
 * because Gmail estimates can be inaccurate for dashboard counters.
 */
function lccc_get_gmail_unread_count_by_query($access_token, $query, $limit = 500) {
    $count = 0;
    $page_token = '';

    do {
        $query_args = array(
            'q' => $query,
            'maxResults' => 100,
        );

        if (!empty($page_token)) {
            $query_args['pageToken'] = $page_token;
        }

        $response = lccc_gmail_api_get($access_token, 'messages', $query_args);

        if (!empty($response['messages']) && is_array($response['messages'])) {
            $count += count($response['messages']);
        }

        $page_token = isset($response['nextPageToken']) ? $response['nextPageToken'] : '';

        if ($count >= $limit) {
            return $limit;
        }
    } while (!empty($page_token));

    return $count;
}

/**
 * Get latest unread Gmail messages matching one or more Gmail search queries.
 */
function lccc_get_latest_gmail_unread_items($access_token, $queries, $limit = 5) {
    $items = array();

    foreach ($queries as $query) {
        $response = lccc_gmail_api_get($access_token, 'messages', array(
            'q' => $query,
            'maxResults' => $limit,
        ));

        if (empty($response['messages']) || !is_array($response['messages'])) {
            continue;
        }

        foreach ($response['messages'] as $message) {
            if (empty($message['id'])) {
                continue;
            }

            $message_detail = lccc_gmail_api_get($access_token, 'messages/' . $message['id'], array(
                'format' => 'metadata',
                'metadataHeaders' => array('From', 'Subject'),
            ));

            if (empty($message_detail['payload']['headers'])) {
                continue;
            }

            $sender = '';
            $subject = '';

            foreach ($message_detail['payload']['headers'] as $header) {
                if (empty($header['name']) || !isset($header['value'])) {
                    continue;
                }

                if ('from' === strtolower($header['name'])) {
                    $sender = $header['value'];
                }

                if ('subject' === strtolower($header['name'])) {
                    $subject = $header['value'];
                }
            }

            if (empty($sender)) {
                $sender = 'Unknown sender';
            }

            $items[$message['id']] = array(
                'sender' => lccc_format_gmail_sender($sender),
                'subject' => wp_strip_all_tags($subject),
                'internal_date' => isset($message_detail['internalDate']) ? (int) $message_detail['internalDate'] : 0,
            );
        }
    }

    usort($items, function ($a, $b) {
        return $b['internal_date'] <=> $a['internal_date'];
    });

    return array_slice(array_values($items), 0, $limit);
}

/**
 * Format Gmail sender for compact dashboard display.
 */
function lccc_format_gmail_sender($sender) {
    $sender = wp_strip_all_tags($sender);

    if (preg_match('/^"?([^"<]+)"?\s*</', $sender, $matches)) {
        return trim($matches[1]);
    }

    if (false !== strpos($sender, '@')) {
        return trim(substr($sender, 0, strpos($sender, '@')));
    }

    return trim($sender);
}

/**
 * Get Gmail Signals widget data.
 *
 * Reads unread Gmail signals using Gmail API with readonly access.
 */
function lccc_get_gmail_signals_widget_data() {
    $gmail_config = lccc_get_gmail_api_config();
    $gmail_url = 'https://mail.google.com/mail/';

    if (!empty($gmail_config['account_email'])) {
        $gmail_url = add_query_arg(
            array(
                'Email' => $gmail_config['account_email'],
                'continue' => 'https://mail.google.com/mail/u/0/#inbox',
            ),
            'https://accounts.google.com/AccountChooser'
        );
    }

    if (
        empty($gmail_config['client_id'])
        || empty($gmail_config['client_secret'])
        || empty($gmail_config['refresh_token'])
    ) {
        return array(
            'unread_inbox' => null,
            'unread_notifications' => null,
            'unread_emails' => null,
            'items' => array(),
            'meta' => 'Gmail API not connected.',
            'url' => $gmail_url,
        );
    }

    $access_token = lccc_get_gmail_access_token($gmail_config);

    if (empty($access_token)) {
        return array(
            'unread_inbox' => null,
            'unread_notifications' => null,
            'unread_emails' => null,
            'items' => array(),
            'meta' => 'Could not connect to Gmail API.',
            'url' => $gmail_url,
        );
    }

    $inbox_query = 'in:inbox category:primary is:unread';
    $notifications_query = 'in:inbox category:updates is:unread';

    $unread_inbox = lccc_get_gmail_unread_count_by_query($access_token, $inbox_query);
    $unread_notifications = lccc_get_gmail_unread_count_by_query($access_token, $notifications_query);

    $items = lccc_get_latest_gmail_unread_items(
        $access_token,
        array($inbox_query, $notifications_query),
        5
    );

    return array(
        'unread_inbox' => $unread_inbox,
        'unread_notifications' => $unread_notifications,
        'unread_emails' => $unread_inbox + $unread_notifications,
        'items' => $items,
        'meta' => empty($items) ? 'No unread Gmail signals found.' : 'Latest unread Gmail signals.',
        'url' => $gmail_url,
    );
}

/**
 * Get Operational Tasks widget data.
 */
function lccc_get_operational_tasks_widget_data() {
    $tasks = lccc_get_operational_tasks();

    return array(
        'items' => array_slice($tasks, 0, 5),
        'meta' => empty($tasks) ? 'No active operational tasks.' : 'Active operational tasks.',
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
        'https://www.wellandgood.com/feed/',
        'https://www.mindbodygreen.com/rss.xml',
        'https://news.google.com/rss/search?q=(terapias%20hol%C3%ADsticas%20OR%20bienestar%20integral%20OR%20yoga%20OR%20meditaci%C3%B3n%20OR%20salud%20natural%20OR%20psicolog%C3%ADa%20OR%20salud%20mental)%20when:14d&hl=es-419&gl=AR&ceid=AR:es-419',
        'https://news.google.com/rss/search?q=(yoga%20OR%20mindfulness%20OR%20holistic%20wellness%20OR%20natural%20health%20OR%20integrative%20health%20OR%20psychology%20OR%20mental%20health%20OR%20beauty%20wellness)%20when:14d&hl=es-419&gl=AR&ceid=AR:es-419',
        'https://news.google.com/rss/search?q=(wellness%20OR%20self-care%20OR%20beauty%20trends%20OR%20healthy%20lifestyle%20OR%20emotional%20wellbeing)%20when:14d&hl=es-419&gl=AR&ceid=AR:es-419',
    );

    $items = array();

    foreach ($feed_urls as $feed_url) {
        $feed = fetch_feed($feed_url);

        if (is_wp_error($feed)) {
            continue;
        }

        $max_items = $feed->get_item_quantity(8);
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

    if (empty($items)) {
    $items = $fallback_items;
    }

    return array(
        'label' => 'Market Signals',
        'items' => array_slice($items, 0, 15),
        'meta' => 'Latest RSS updates.',
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
