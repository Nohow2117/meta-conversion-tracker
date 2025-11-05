<?php
/**
 * Dashboard View
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap mct-admin">
    <h1>Meta Conversion Tracker - Dashboard</h1>
    
    <div class="mct-stats-grid">
        <div class="mct-stat-box">
            <div class="mct-stat-icon">📊</div>
            <div class="mct-stat-content">
                <div class="mct-stat-value"><?php echo number_format($total_conversions); ?></div>
                <div class="mct-stat-label">Total Conversions</div>
            </div>
        </div>
        
        <div class="mct-stat-box">
            <div class="mct-stat-icon">🎯</div>
            <div class="mct-stat-content">
                <div class="mct-stat-value"><?php echo number_format($today_conversions); ?></div>
                <div class="mct-stat-label">Today's Conversions</div>
            </div>
        </div>
        
        <div class="mct-stat-box">
            <div class="mct-stat-icon">✅</div>
            <div class="mct-stat-content">
                <div class="mct-stat-value"><?php echo number_format($meta_sent); ?></div>
                <div class="mct-stat-label">Sent to Meta</div>
            </div>
        </div>
        
        <div class="mct-stat-box">
            <div class="mct-stat-icon">🚀</div>
            <div class="mct-stat-content">
                <div class="mct-stat-value"><?php echo number_format($unique_campaigns); ?></div>
                <div class="mct-stat-label">Active Campaigns</div>
            </div>
        </div>
    </div>
    
    <div class="mct-row">
        <div class="mct-col-8">
            <div class="mct-card">
                <h2>Recent Conversions</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign</th>
                            <th>Platform</th>
                            <th>IP Address</th>
                            <th>Meta Sent</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_conversions)): ?>
                            <?php foreach ($recent_conversions as $conversion): ?>
                                <tr>
                                    <td><?php echo esc_html($conversion['id']); ?></td>
                                    <td><?php echo esc_html($conversion['utm_campaign'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($conversion['platform'] === 'discord'): ?>
                                            <span class="mct-badge mct-badge-discord">Discord</span>
                                        <?php elseif ($conversion['platform'] === 'telegram'): ?>
                                            <span class="mct-badge mct-badge-telegram">Telegram</span>
                                        <?php else: ?>
                                            <span class="mct-badge">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($conversion['ip_address'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($conversion['meta_sent']): ?>
                                            <span class="mct-badge mct-badge-success">✓ Sent</span>
                                        <?php else: ?>
                                            <span class="mct-badge mct-badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($conversion['created_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No conversions yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo admin_url('admin.php?page=mct-conversions'); ?>" class="button">View All Conversions</a>
                </p>
            </div>
        </div>
        
        <div class="mct-col-4">
            <div class="mct-card">
                <h2>Top Campaigns</h2>
                <?php if (!empty($top_campaigns)): ?>
                    <ul class="mct-campaign-list">
                        <?php foreach ($top_campaigns as $campaign): ?>
                            <li>
                                <span class="mct-campaign-name"><?php echo esc_html($campaign['utm_campaign']); ?></span>
                                <span class="mct-campaign-count"><?php echo number_format($campaign['count']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No campaign data available</p>
                <?php endif; ?>
            </div>
            
            <div class="mct-card" style="margin-top: 20px;">
                <h2>Quick Actions</h2>
                <p>
                    <button type="button" class="button button-primary" id="mct-retry-failed" style="width: 100%;">
                        Retry Failed Conversions
                    </button>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mct-settings'); ?>" class="button" style="width: 100%; text-align: center; display: block;">
                        Settings
                    </a>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mct-api-docs'); ?>" class="button" style="width: 100%; text-align: center; display: block;">
                        API Documentation
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
