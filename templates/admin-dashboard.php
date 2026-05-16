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

    <section class="lccc-operations-section">
    <div class="lccc-section-header">
        <h2 class="lccc-section-title">Google Tools</h2>
        <p class="lccc-section-subtitle">Operational signals and quick access tools for daily management.</p>
    </div>

    <div class="lccc-operations-grid">
        <article class="lccc-tool-card lccc-tool-card--calendar">
            <div class="lccc-tool-card-header">
                <h3 class="lccc-tool-title">Calendar</h3>
                <a
                    class="lccc-tool-link"
                    href="<?php echo esc_url($calendar_widget['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Go to Calendar
                </a>
            </div>

            <p class="lccc-tool-label">Next Event</p>
            <p class="lccc-tool-value">
                <?php echo esc_html($calendar_widget['title']); ?>
            </p>
            <p class="lccc-tool-meta">
                <?php echo esc_html($calendar_widget['meta']); ?>
            </p>
        </article>

        <article class="lccc-tool-card lccc-tool-card--gmail">
            <div class="lccc-tool-card-header">
                <h3 class="lccc-tool-title">Gmail Signals</h3>
                <a
                    class="lccc-tool-link"
                    href="<?php echo esc_url($gmail_signals_widget['url']); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Go to Inbox
                </a>
            </div>

            <ul class="lccc-signal-list">
              <li>
                  Unread emails:
                  <strong>
                      <?php echo is_null($gmail_signals_widget['unread_emails']) ? '—' : esc_html($gmail_signals_widget['unread_emails']); ?>
                  </strong>
              </li>
              <li>
                  Pending replies:
                  <strong>
                      <?php echo is_null($gmail_signals_widget['pending_replies']) ? '—' : esc_html($gmail_signals_widget['pending_replies']); ?>
                  </strong>
              </li>
              <li>
                  Updates:
                  <strong>
                      <?php echo is_null($gmail_signals_widget['updates']) ? '—' : esc_html($gmail_signals_widget['updates']); ?>
                  </strong>
              </li>
          </ul>
        </article>

        <article class="lccc-tool-card lccc-tool-card--tasks">
            <div class="lccc-tool-card-header">
                <h3 class="lccc-tool-title">Operational Tasks</h3>
                <span class="lccc-tool-badge">Internal</span>
            </div>

            <div class="lccc-task-preview">
                <span>Follow-up</span>
                <span>Priority</span>
                <span>Status</span>
            </div>

            <p class="lccc-tool-meta">Task module pending.</p>
        </article>

        <article class="lccc-tool-card lccc-tool-card--trends">
            <div class="lccc-tool-card-header">
                <h3 class="lccc-tool-title">Trends & News</h3>
                <span class="lccc-tool-badge">Soon</span>
            </div>

            <p class="lccc-tool-label">Market Signals</p>
            <p class="lccc-tool-value">No feed connected yet.</p>
            <p class="lccc-tool-meta">RSS or curated source pending.</p>
        </article>
    </div>
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
