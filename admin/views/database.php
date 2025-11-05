<?php
/**
 * Database Access View
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap mct-admin">
    <h1>Database Access</h1>
    
    <div class="mct-card">
        <h2>Direct Database Connection</h2>
        <p>Use these credentials to connect your external systems directly to the conversions database.</p>
        
        <div class="notice notice-warning">
            <p><strong>⚠️ Security Notice:</strong> This user has READ-ONLY access to the conversions table only.</p>
        </div>
        
        <table class="form-table">
            <tr>
                <th>Host</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['host']); ?></code>
                    <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['host']); ?>">Copy</button>
                </td>
            </tr>
            <tr>
                <th>Port</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['port']); ?></code>
                    <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['port']); ?>">Copy</button>
                </td>
            </tr>
            <tr>
                <th>Database</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['database']); ?></code>
                    <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['database']); ?>">Copy</button>
                </td>
            </tr>
            <tr>
                <th>Username</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['username'] ?: 'Not created'); ?></code>
                    <?php if ($db_info['username']): ?>
                        <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['username']); ?>">Copy</button>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Password</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['password'] ?: 'Not created'); ?></code>
                    <?php if ($db_info['password']): ?>
                        <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['password']); ?>">Copy</button>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Table Name</th>
                <td>
                    <code class="mct-db-info"><?php echo esc_html($db_info['table']); ?></code>
                    <button class="button button-small mct-copy-btn" data-copy="<?php echo esc_attr($db_info['table']); ?>">Copy</button>
                </td>
            </tr>
        </table>
        
        <?php if (!$db_info['username'] || !$db_info['password']): ?>
            <div class="notice notice-info">
                <p><strong>Note:</strong> Database user creation requires GRANT privileges. If automatic creation failed, create the user manually using the SQL below.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="mct-card">
        <h2>Connection Examples</h2>
        
        <h3>Python (mysql-connector)</h3>
        <pre class="mct-code-block">import mysql.connector

# Connect to database
db = mysql.connector.connect(
    host="<?php echo esc_html($db_info['host']); ?>",
    port=<?php echo esc_html($db_info['port']); ?>,
    user="<?php echo esc_html($db_info['username']); ?>",
    password="<?php echo esc_html($db_info['password']); ?>",
    database="<?php echo esc_html($db_info['database']); ?>"
)

cursor = db.cursor(dictionary=True)

# Query conversions
cursor.execute("""
    SELECT * FROM <?php echo esc_html($db_info['table']); ?> 
    WHERE utm_campaign = %s 
    ORDER BY created_at DESC 
    LIMIT 100
""", ('warcry_launch',))

conversions = cursor.fetchall()

for conversion in conversions:
    print(f"ID: {conversion['id']}, Platform: {conversion['platform']}")

cursor.close()
db.close()</pre>
        
        <h3>PHP (PDO)</h3>
        <pre class="mct-code-block">$dsn = "mysql:host=<?php echo esc_html($db_info['host']); ?>;port=<?php echo esc_html($db_info['port']); ?>;dbname=<?php echo esc_html($db_info['database']); ?>";
$username = "<?php echo esc_html($db_info['username']); ?>";
$password = "<?php echo esc_html($db_info['password']); ?>";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM <?php echo esc_html($db_info['table']); ?> WHERE utm_campaign = ? LIMIT 100");
    $stmt->execute(['warcry_launch']);
    
    $conversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($conversions as $conversion) {
        echo "ID: {$conversion['id']}, Platform: {$conversion['platform']}\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}</pre>
        
        <h3>Node.js (mysql2)</h3>
        <pre class="mct-code-block">const mysql = require('mysql2/promise');

async function getConversions() {
    const connection = await mysql.createConnection({
        host: '<?php echo esc_html($db_info['host']); ?>',
        port: <?php echo esc_html($db_info['port']); ?>,
        user: '<?php echo esc_html($db_info['username']); ?>',
        password: '<?php echo esc_html($db_info['password']); ?>',
        database: '<?php echo esc_html($db_info['database']); ?>'
    });
    
    const [rows] = await connection.execute(
        'SELECT * FROM <?php echo esc_html($db_info['table']); ?> WHERE utm_campaign = ? LIMIT 100',
        ['warcry_launch']
    );
    
    console.log(rows);
    await connection.end();
}

getConversions();</pre>
    </div>
    
    <div class="mct-card">
        <h2>Common SQL Queries</h2>
        
        <h3>Get all conversions from today</h3>
        <pre class="mct-code-block">SELECT * FROM <?php echo esc_html($db_info['table']); ?> 
WHERE DATE(created_at) = CURDATE();</pre>
        
        <h3>Count conversions by campaign</h3>
        <pre class="mct-code-block">SELECT utm_campaign, COUNT(*) as total 
FROM <?php echo esc_html($db_info['table']); ?> 
GROUP BY utm_campaign 
ORDER BY total DESC;</pre>
        
        <h3>Get conversions by FBCLID</h3>
        <pre class="mct-code-block">SELECT * FROM <?php echo esc_html($db_info['table']); ?> 
WHERE fbclid = 'YOUR_FBCLID';</pre>
        
        <h3>Get conversions not sent to Meta</h3>
        <pre class="mct-code-block">SELECT * FROM <?php echo esc_html($db_info['table']); ?> 
WHERE meta_sent = 0;</pre>
        
        <h3>Get conversion rate by platform</h3>
        <pre class="mct-code-block">SELECT 
    platform,
    COUNT(*) as total_conversions,
    COUNT(DISTINCT fbclid) as unique_clicks
FROM <?php echo esc_html($db_info['table']); ?> 
GROUP BY platform;</pre>
    </div>
    
    <div class="mct-card">
        <h2>Manual Database User Creation</h2>
        <p>If automatic user creation failed, run these SQL commands as database administrator:</p>
        
        <pre class="mct-code-block">-- Create user
CREATE USER '<?php echo esc_html($db_info['username']); ?>'@'%' IDENTIFIED BY '<?php echo esc_html($db_info['password']); ?>';

-- Grant SELECT permission on conversions table only
GRANT SELECT ON <?php echo esc_html($db_info['database']); ?>.<?php echo esc_html($db_info['table']); ?> TO '<?php echo esc_html($db_info['username']); ?>'@'%';

-- Apply changes
FLUSH PRIVILEGES;</pre>
    </div>
</div>
