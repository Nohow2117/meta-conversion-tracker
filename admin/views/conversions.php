<?php
/**
 * Conversions List View
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap mct-admin">
    <h1>Conversions</h1>
    
    <div class="mct-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="mct-conversions">
            
            <select name="utm_campaign">
                <option value="">All Campaigns</option>
                <?php
                global $wpdb;
                $campaigns = $wpdb->get_col("SELECT DISTINCT utm_campaign FROM " . MCT_Database::get_table_name() . " WHERE utm_campaign IS NOT NULL ORDER BY utm_campaign");
                foreach ($campaigns as $campaign) {
                    $selected = (isset($_GET['utm_campaign']) && $_GET['utm_campaign'] === $campaign) ? 'selected' : '';
                    echo '<option value="' . esc_attr($campaign) . '" ' . $selected . '>' . esc_html($campaign) . '</option>';
                }
                ?>
            </select>
            
            <select name="platform">
                <option value="">All Platforms</option>
                <option value="discord" <?php selected(isset($_GET['platform']) && $_GET['platform'] === 'discord'); ?>>Discord</option>
                <option value="telegram" <?php selected(isset($_GET['platform']) && $_GET['platform'] === 'telegram'); ?>>Telegram</option>
            </select>
            
            <select name="meta_sent">
                <option value="">All Status</option>
                <option value="1" <?php selected(isset($_GET['meta_sent']) && $_GET['meta_sent'] === '1'); ?>>Sent to Meta</option>
                <option value="0" <?php selected(isset($_GET['meta_sent']) && $_GET['meta_sent'] === '0'); ?>>Not Sent</option>
            </select>
            
            <button type="submit" class="button">Filter</button>
            <a href="<?php echo admin_url('admin.php?page=mct-conversions'); ?>" class="button">Reset</a>
        </form>
    </div>
    
    <div class="mct-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Campaign</th>
                    <th>Source</th>
                    <th>Platform</th>
                    <th>FBCLID</th>
                    <th>IP Address</th>
                    <th>Meta</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($conversions)): ?>
                    <?php foreach ($conversions as $conversion): ?>
                        <tr>
                            <td><?php echo esc_html($conversion['id']); ?></td>
                            <td>
                                <strong><?php echo esc_html($conversion['utm_campaign'] ?: '-'); ?></strong>
                                <?php if ($conversion['utm_content']): ?>
                                    <br><small><?php echo esc_html($conversion['utm_content']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($conversion['utm_source'] ?: '-'); ?></td>
                            <td>
                                <?php if ($conversion['platform'] === 'discord'): ?>
                                    <span class="mct-badge mct-badge-discord">Discord</span>
                                <?php elseif ($conversion['platform'] === 'telegram'): ?>
                                    <span class="mct-badge mct-badge-telegram">Telegram</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($conversion['fbclid']): ?>
                                    <code style="font-size: 10px;"><?php echo esc_html(substr($conversion['fbclid'], 0, 20)); ?>...</code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($conversion['ip_address'] ?: '-'); ?></td>
                            <td>
                                <?php if ($conversion['meta_sent']): ?>
                                    <span class="mct-badge mct-badge-success" title="Sent at <?php echo esc_attr($conversion['meta_sent_at']); ?>">
                                        ✓ Sent
                                    </span>
                                <?php else: ?>
                                    <span class="mct-badge mct-badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($conversion['created_at']))); ?></td>
                            <td>
                                <button type="button" 
                                        class="button button-small mct-view-details" 
                                        data-id="<?php echo esc_attr($conversion['id']); ?>">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            No conversions found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $base_url = add_query_arg(array(
                        'page' => 'mct-conversions',
                        'utm_campaign' => isset($_GET['utm_campaign']) ? $_GET['utm_campaign'] : '',
                        'platform' => isset($_GET['platform']) ? $_GET['platform'] : '',
                        'meta_sent' => isset($_GET['meta_sent']) ? $_GET['meta_sent'] : '',
                    ), admin_url('admin.php'));
                    
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for viewing details -->
<div id="mct-details-modal" style="display: none;">
    <div class="mct-modal-overlay"></div>
    <div class="mct-modal-content">
        <span class="mct-modal-close">&times;</span>
        <div id="mct-details-content">
            Loading...
        </div>
    </div>
</div>
