<?php
/**
 * Beacon Log View
 */
if (!defined('ABSPATH')) exit;

// Get filter parameters
$platform_filter = isset($_GET['platform']) ? sanitize_text_field($_GET['platform']) : '';
$action_filter = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-7 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Build query
global $wpdb;
$beacon_table = $wpdb->prefix . 'mct_beacon_log';
$conversions_table = $wpdb->prefix . 'meta_conversions';

$where_clauses = array("1=1");
$where_values = array();

if (!empty($platform_filter)) {
    $where_clauses[] = "platform = %s";
    $where_values[] = $platform_filter;
}

if (!empty($action_filter)) {
    $where_clauses[] = "action = %s";
    $where_values[] = $action_filter;
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(created_at) >= %s";
    $where_values[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(created_at) <= %s";
    $where_values[] = $date_to;
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
if (!empty($where_values)) {
    $total_items = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$beacon_table} WHERE {$where_sql}",
        $where_values
    ));
} else {
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$beacon_table} WHERE {$where_sql}");
}

$total_pages = ceil($total_items / $per_page);

// Get beacons
$prepare_values = array_merge($where_values, array($per_page, $offset));
$beacons = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$beacon_table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $prepare_values
));

// Get statistics
$stats_where_values = $where_values;
if (!empty($stats_where_values)) {
    $stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_beacons,
            COUNT(DISTINCT ip_address) as unique_ips,
            COUNT(DISTINCT fingerprint) as unique_fingerprints,
            COUNT(DISTINCT platform) as platforms_count
        FROM {$beacon_table} 
        WHERE {$where_sql}",
        $stats_where_values
    ));
} else {
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_beacons,
            COUNT(DISTINCT ip_address) as unique_ips,
            COUNT(DISTINCT fingerprint) as unique_fingerprints,
            COUNT(DISTINCT platform) as platforms_count
        FROM {$beacon_table} 
        WHERE {$where_sql}"
    );
}

// Get success rate (beacon vs conversions)
$success_rate_query = $wpdb->prepare(
    "SELECT 
        COUNT(DISTINCT b.id) as beacon_count,
        COUNT(DISTINCT c.id) as conversion_count
    FROM {$beacon_table} b
    LEFT JOIN {$conversions_table} c 
        ON DATE(b.created_at) = DATE(c.created_at)
        AND b.platform = c.platform
    WHERE b.created_at >= %s 
        AND b.created_at <= %s
        AND b.action = 'wc_captcha_completed'",
    $date_from . ' 00:00:00',
    $date_to . ' 23:59:59'
);
$success_data = $wpdb->get_row($success_rate_query);
$success_rate = $success_data->beacon_count > 0 
    ? round(($success_data->conversion_count / $success_data->beacon_count * 100), 2) 
    : 0;

// Get platforms for filter
$platforms = $wpdb->get_col("SELECT DISTINCT platform FROM {$beacon_table} ORDER BY platform");
$actions = $wpdb->get_col("SELECT DISTINCT action FROM {$beacon_table} ORDER BY action");
?>

<div class="wrap mct-admin">
    <h1>
        Beacon Log
        <span class="mct-badge" style="background: #00a32a; color: white; padding: 3px 10px; font-size: 12px; border-radius: 3px; margin-left: 10px;">
            <?php echo number_format($total_items); ?> Total
        </span>
    </h1>
    
    <p class="description">
        Tracking garantito di tutti i completamenti captcha, anche se il tracker principale fallisce.
    </p>
    
    <!-- Statistics Cards -->
    <div class="mct-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="mct-stat-card" style="background: white; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Total Beacons</div>
            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo number_format($stats->total_beacons); ?></div>
        </div>
        
        <div class="mct-stat-card" style="background: white; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Unique IPs</div>
            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo number_format($stats->unique_ips); ?></div>
        </div>
        
        <div class="mct-stat-card" style="background: white; padding: 20px; border-left: 4px solid #9b51e0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Unique Fingerprints</div>
            <div style="font-size: 28px; font-weight: 600; color: #1d2327;"><?php echo number_format($stats->unique_fingerprints); ?></div>
        </div>
        
        <div class="mct-stat-card" style="background: white; padding: 20px; border-left: 4px solid <?php echo $success_rate >= 80 ? '#00a32a' : '#d63638'; ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 13px; color: #646970; margin-bottom: 5px;">Success Rate</div>
            <div style="font-size: 28px; font-weight: 600; color: <?php echo $success_rate >= 80 ? '#00a32a' : '#d63638'; ?>;">
                <?php echo $success_rate; ?>%
            </div>
            <div style="font-size: 11px; color: #646970; margin-top: 5px;">
                <?php echo number_format($success_data->conversion_count); ?> / <?php echo number_format($success_data->beacon_count); ?> conversions
            </div>
        </div>
    </div>
    
    <?php if ($success_rate < 80 && $success_data->beacon_count > 10): ?>
        <div class="notice notice-warning">
            <p>
                <strong>⚠️ Warning:</strong> Success rate is below 80%. 
                This means some beacons are not being converted to tracked conversions. 
                Check your main tracker implementation.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="mct-filters" style="background: white; padding: 15px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <form method="get" action="">
            <input type="hidden" name="page" value="mct-beacon-log">
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: end;">
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">Platform</label>
                    <select name="platform" style="min-width: 150px;">
                        <option value="">All Platforms</option>
                        <?php foreach ($platforms as $platform): ?>
                            <option value="<?php echo esc_attr($platform); ?>" <?php selected($platform_filter, $platform); ?>>
                                <?php echo esc_html(ucfirst($platform)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">Action</label>
                    <select name="action_type" style="min-width: 200px;">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?php echo esc_attr($action); ?>" <?php selected($action_filter, $action); ?>>
                                <?php echo esc_html($action); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="min-width: 150px;">
                </div>
                
                <div>
                    <label style="display: block; font-size: 12px; margin-bottom: 5px;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="min-width: 150px;">
                </div>
                
                <div>
                    <button type="submit" class="button button-primary">Filter</button>
                    <a href="<?php echo admin_url('admin.php?page=mct-beacon-log'); ?>" class="button">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Beacon Table -->
    <div class="mct-card" style="background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 120px;">Date/Time</th>
                    <th style="width: 100px;">Platform</th>
                    <th style="width: 150px;">Action</th>
                    <th>Page URL</th>
                    <th style="width: 120px;">IP Address</th>
                    <th>Referrer</th>
                    <th style="width: 100px;">Fingerprint</th>
                    <th style="width: 80px;">Custom</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($beacons)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #646970;">
                                <span class="dashicons dashicons-info" style="font-size: 48px; opacity: 0.3;"></span>
                                <p style="margin-top: 10px;">No beacons found for the selected filters.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($beacons as $beacon): ?>
                        <tr>
                            <td><strong><?php echo $beacon->id; ?></strong></td>
                            <td>
                                <?php echo date('Y-m-d', strtotime($beacon->created_at)); ?><br>
                                <small style="color: #646970;"><?php echo date('H:i:s', strtotime($beacon->created_at)); ?></small>
                            </td>
                            <td>
                                <span class="mct-badge mct-badge-<?php echo esc_attr($beacon->platform); ?>" style="padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html(ucfirst($beacon->platform)); ?>
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 11px; padding: 2px 6px; background: #f0f0f1; border-radius: 3px;">
                                    <?php echo esc_html($beacon->action); ?>
                                </code>
                            </td>
                            <td>
                                <?php if (!empty($beacon->page_url)): ?>
                                    <a href="<?php echo esc_url($beacon->page_url); ?>" target="_blank" style="font-size: 12px;">
                                        <?php echo esc_html(substr($beacon->page_url, 0, 40)) . (strlen($beacon->page_url) > 40 ? '...' : ''); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #dcdcde;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo esc_html($beacon->ip_address); ?></code>
                            </td>
                            <td>
                                <?php if ($beacon->referrer && $beacon->referrer !== 'direct'): ?>
                                    <a href="<?php echo esc_url($beacon->referrer); ?>" target="_blank" style="font-size: 12px;">
                                        <?php echo esc_html(substr($beacon->referrer, 0, 50)) . (strlen($beacon->referrer) > 50 ? '...' : ''); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #646970; font-size: 12px;">Direct</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($beacon->fingerprint): ?>
                                    <code style="font-size: 10px;"><?php echo esc_html(substr($beacon->fingerprint, 0, 20)); ?></code>
                                <?php else: ?>
                                    <span style="color: #dcdcde;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($beacon->custom_data): ?>
                                    <button type="button" class="button button-small view-custom-data" data-custom="<?php echo esc_attr($beacon->custom_data); ?>">
                                        View
                                    </button>
                                <?php else: ?>
                                    <span style="color: #dcdcde;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom" style="padding: 15px;">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total_items); ?> items</span>
                    <span class="pagination-links">
                        <?php
                        $base_url = add_query_arg(array(
                            'page' => 'mct-beacon-log',
                            'platform' => $platform_filter,
                            'action_type' => $action_filter,
                            'date_from' => $date_from,
                            'date_to' => $date_to,
                        ), admin_url('admin.php'));
                        
                        if ($current_page > 1):
                        ?>
                            <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>">
                                <span class="screen-reader-text">Previous page</span>
                                <span aria-hidden="true">‹</span>
                            </a>
                        <?php else: ?>
                            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                        <?php endif; ?>
                        
                        <span class="screen-reader-text">Current Page</span>
                        <span class="paging-input">
                            <span class="tablenav-paging-text">
                                <?php echo $current_page; ?> of 
                                <span class="total-pages"><?php echo $total_pages; ?></span>
                            </span>
                        </span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>">
                                <span class="screen-reader-text">Next page</span>
                                <span aria-hidden="true">›</span>
                            </a>
                        <?php else: ?>
                            <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Custom Data Modal -->
<div id="custom-data-modal" style="display: none;">
    <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000; display: flex; align-items: center; justify-content: center;" onclick="this.parentElement.style.display='none'">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; width: 90%; max-height: 80vh; overflow: auto;" onclick="event.stopPropagation()">
            <h2 style="margin-top: 0;">Custom Data</h2>
            <pre id="custom-data-content" style="background: #f0f0f1; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px;"></pre>
            <button type="button" class="button" onclick="document.getElementById('custom-data-modal').style.display='none'">Close</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // View custom data
    $('.view-custom-data').on('click', function() {
        var customData = $(this).data('custom');
        try {
            var formatted = JSON.stringify(JSON.parse(customData), null, 2);
            $('#custom-data-content').text(formatted);
        } catch(e) {
            $('#custom-data-content').text(customData);
        }
        $('#custom-data-modal').show();
    });
});
</script>

<style>
.mct-badge-discord { background: #5865F2; color: white; }
.mct-badge-telegram { background: #0088cc; color: white; }
.mct-badge-web { background: #00a32a; color: white; }
.mct-badge-other { background: #646970; color: white; }
</style>
