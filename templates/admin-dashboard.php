<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="lccc-dashboard">
    <header class="lccc-header">
        <h1 class="lccc-title">WooCommerce Command Center</h1>
        <p class="lccc-subtitle">
            Custom operational dashboard for WooCommerce orders, revenue and customer follow-up.
        </p>
    </header>

    <section class="lccc-grid">
        <article class="lccc-card">
            <div class="lccc-icon">◆</div>
            <h2 class="lccc-card-label">Products</h2>
            <p class="lccc-card-number"><?php echo esc_html($active_products); ?></p>
            <p class="lccc-card-text">Active products tracked.</p>
        </article>

        <article class="lccc-card">
            <div class="lccc-icon">◆</div>
            <h2 class="lccc-card-label">Orders</h2>
            <p class="lccc-card-number"><?php echo esc_html($total_orders); ?></p>
            <p class="lccc-card-text">Total confirmed orders.</p>
        </article>

        <article class="lccc-card">
            <div class="lccc-icon">◆</div>
            <h2 class="lccc-card-label">Pending Follow-ups</h2>
            <p class="lccc-card-number"><?php echo esc_html($pending_followups); ?></p>
            <p class="lccc-card-text">Customers pending contact.</p>
        </article>

        <article class="lccc-card">
            <div class="lccc-icon">◆</div>
            <h2 class="lccc-card-label">Monthly Revenue ARS</h2>
            <p class="lccc-card-number">
                <?php echo wp_kses_post(lccc_format_revenue($monthly_revenue_ars)); ?>
            </p>
            <p class="lccc-card-text">Confirmed ARS revenue this month.</p>
        </article>

        <article class="lccc-card">
            <div class="lccc-icon">◆</div>
            <h2 class="lccc-card-label">Monthly Revenue USD</h2>
            <p class="lccc-card-number">
                <?php echo wp_kses_post(lccc_format_revenue($monthly_revenue_usd)); ?>
            </p>
            <p class="lccc-card-text">Confirmed USD revenue this month.</p>
        </article>

    </section>

    <section class="lccc-table-section">
        <div class="lccc-section-header">
            <h2 class="lccc-section-title">Recent Orders</h2>
            <p class="lccc-section-subtitle">Latest 10 confirmed or processing WooCommerce orders.</p>
        </div>

        <div class="lccc-table-wrap">
            <table class="lccc-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>DNI</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Call</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($recent_orders)) : ?>
                        <?php foreach ($recent_orders as $order) : ?>
                            <tr>
                                <td><?php echo esc_html($order['customer_name']); ?></td>
                                <td><?php echo esc_html($order['customer_dni']); ?></td>
                                <td><?php echo esc_html($order['customer_email']); ?></td>
                                <td><?php echo esc_html($order['customer_phone']); ?></td>
                                <td><?php echo esc_html($order['items']); ?></td>
                                <td><?php echo esc_html($order['date']); ?></td>
                                <td>
                                    <span class="lccc-status">
                                        <?php echo esc_html($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($order['whatsapp_url'])) : ?>
                                        <a
                                            class="lccc-whatsapp-button"
                                            href="<?php echo esc_url($order['whatsapp_url']); ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Open WhatsApp"
                                            aria-label="Open WhatsApp"
                                        >
                                            <img
                                                class="lccc-whatsapp-icon"
                                                src="<?php echo esc_url(LCCC_PLUGIN_URL . 'assets/icons/whatsapp.svg'); ?>"
                                                alt=""
                                                aria-hidden="true"
                                            >
                                        </a>
                                    <?php else : ?>
                                        <span class="lccc-muted">No phone</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
