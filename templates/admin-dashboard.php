<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="lccc-dashboard">
    <header class="lccc-header">
        <h1 class="lccc-title">Operations Hub</h1>
        <p class="lccc-subtitle">
          Custom dashboard for operational data, signals and tools.
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

            <p class="lccc-tool-label">Next Events</p>

            <?php if (!empty($calendar_widget['events'])) : ?>
                <div class="lccc-calendar-list">
                    <?php foreach ($calendar_widget['events'] as $event) : ?>
                        <div class="lccc-calendar-item">
                            <div class="lccc-calendar-time">
                                <?php echo esc_html($event['time']); ?>
                            </div>

                            <div class="lccc-calendar-content">
                                <span class="lccc-calendar-title">
                                    <?php echo esc_html($event['title']); ?>
                                </span>
                                <span class="lccc-calendar-meta">
                                    <?php echo esc_html($event['date']); ?> · <?php echo esc_html($event['calendar']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="lccc-tool-value">
                    <?php echo esc_html($calendar_widget['title']); ?>
                </p>
                <p class="lccc-tool-meta">
                    <?php echo esc_html($calendar_widget['meta']); ?>
                </p>
            <?php endif; ?>
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
                            Unread Inbox:
                            <strong>
                                <?php echo is_null($gmail_signals_widget['unread_inbox']) ? '—' : esc_html($gmail_signals_widget['unread_inbox']); ?>
                            </strong>
                        </li>
                        <li>
                            Unread Notifications:
                            <strong>
                                <?php echo is_null($gmail_signals_widget['unread_notifications']) ? '—' : esc_html($gmail_signals_widget['unread_notifications']); ?>
                            </strong>
                        </li>
                    </ul>

                    <?php if (!empty($gmail_signals_widget['items'])) : ?>
                        <ul class="lccc-gmail-list">
                            <?php foreach ($gmail_signals_widget['items'] as $gmail_item) : ?>
                                <li>
                                    <span class="lccc-gmail-sender">
                                        <?php echo esc_html($gmail_item['sender']); ?>
                                    </span>
                                    <?php if (!empty($gmail_item['subject'])) : ?>
                                        <span class="lccc-gmail-subject">
                                            <?php echo esc_html($gmail_item['subject']); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="lccc-tool-meta">
                            <?php echo esc_html($gmail_signals_widget['meta']); ?>
                        </p>
                    <?php endif; ?>
                </article>

                <article class="lccc-tool-card lccc-tool-card--tasks">
            <div class="lccc-tool-card-header">
                <h3 class="lccc-tool-title">Operational Tasks</h3>
                <span class="lccc-tool-badge">Internal</span>
            </div>

            <form class="lccc-task-form" method="post">
                <?php wp_nonce_field('lccc_operational_tasks_action', 'lccc_operational_tasks_nonce'); ?>

                <input type="hidden" name="lccc_task_action" value="add_task">

                <label class="lccc-task-field">
                    <span>Task</span>
                    <input
                        type="text"
                        name="lccc_task_title"
                        placeholder="Add a task..."
                        required
                    >
                </label>

                <div class="lccc-task-form-row">
                    <label class="lccc-task-field">
                        <span>Priority</span>
                        <select name="lccc_task_priority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </label>

                    <button type="submit" class="lccc-task-submit">Add</button>
                </div>
            </form>

            <?php if (!empty($operational_tasks_widget['items'])) : ?>
                <div class="lccc-task-list">
                    <?php foreach ($operational_tasks_widget['items'] as $task) : ?>
                        <div class="lccc-task-item">
                            <div class="lccc-task-main">
                                <span class="lccc-task-title"><?php echo esc_html($task['title']); ?></span>
                                <span class="lccc-task-priority lccc-task-priority--<?php echo esc_attr(strtolower($task['priority'])); ?>">
                                    <?php echo esc_html($task['priority']); ?>
                                </span>
                            </div>

                            <form method="post" class="lccc-task-complete-form">
                                <?php wp_nonce_field('lccc_operational_tasks_action', 'lccc_operational_tasks_nonce'); ?>

                                <input type="hidden" name="lccc_task_action" value="complete_task">
                                <input type="hidden" name="lccc_task_id" value="<?php echo esc_attr($task['id']); ?>">

                                <button type="submit" class="lccc-task-done">Done</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="lccc-tool-meta">No active operational tasks.</p>
            <?php endif; ?>
        </article>

        <article class="lccc-tool-card lccc-tool-card--trends">
          <div class="lccc-tool-card-header">
              <h3 class="lccc-tool-title">Trends & News</h3>
              <span class="lccc-tool-badge">RSS</span>
          </div>

          <p class="lccc-tool-label">
              <?php echo esc_html($trends_news_widget['label']); ?>
          </p>

          <?php if (!empty($trends_news_widget['items'])) : ?>
              <div class="lccc-news-slider" data-lccc-news-slider>
                  <div class="lccc-news-track">
                      <?php foreach ($trends_news_widget['items'] as $index => $news_item) : ?>
                          <article
                              class="lccc-news-slide <?php echo 0 === $index ? 'is-active' : ''; ?>"
                              data-lccc-news-slide
                          >
                              <a
                                  class="lccc-news-card-link"
                                  href="<?php echo esc_url($news_item['url']); ?>"
                                  target="_blank"
                                  rel="noopener noreferrer"
                              >
                                  <span class="lccc-news-title">
                                      <?php echo esc_html($news_item['title']); ?>
                                  </span>
                              </a>

                              <div class="lccc-news-meta">
                          </article>
                      <?php endforeach; ?>
                  </div>

                  <div class="lccc-news-controls">
                      <button type="button" class="lccc-news-button" data-lccc-news-prev aria-label="Previous news">
                          ‹
                      </button>

                      <span class="lccc-news-counter">
                          <span data-lccc-news-current>1</span>/<span><?php echo esc_html(count($trends_news_widget['items'])); ?></span>
                      </span>

                      <button type="button" class="lccc-news-button" data-lccc-news-next aria-label="Next news">
                          ›
                      </button>
                  </div>
              </div>
          <?php else : ?>
              <p class="lccc-tool-value">No feed connected yet.</p>
          <?php endif; ?>

          <p class="lccc-tool-meta">
              <?php echo esc_html($trends_news_widget['meta']); ?>
          </p>
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
