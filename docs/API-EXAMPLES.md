# API Examples

Esempi pratici di utilizzo delle API in diversi linguaggi.

## Configurazione Base

```
API URL: https://yoursite.com/wp-json/mct/v1
API Key: your-api-key-here
```

---

## JavaScript / Node.js

### Track Conversion (Browser)

```javascript
const tracker = new MCTTracker({
    apiUrl: 'https://yoursite.com/wp-json/mct/v1/track',
    apiKey: 'your-api-key'
});

// Semplice
tracker.track({ platform: 'discord' });

// Con dati custom
tracker.track({
    platform: 'discord',
    event_name: 'Lead',
    custom_data: {
        email: 'user@example.com',
        source: 'landing_page_v2'
    }
});

// Track e redirect
tracker.trackAndRedirect('https://discord.gg/yourserver', {
    platform: 'discord'
});
```

### Get Conversions (Node.js)

```javascript
const fetch = require('node-fetch');

async function getConversions() {
    const response = await fetch(
        'https://yoursite.com/wp-json/mct/v1/conversions?utm_campaign=warcry&limit=50',
        {
            headers: {
                'X-API-Key': 'your-api-key'
            }
        }
    );
    
    const data = await response.json();
    console.log(`Total conversions: ${data.total}`);
    
    data.data.forEach(conv => {
        console.log(`${conv.id}: ${conv.platform} - ${conv.created_at}`);
    });
}

getConversions();
```

### Get Statistics

```javascript
async function getStats() {
    const response = await fetch(
        'https://yoursite.com/wp-json/mct/v1/stats?group_by=campaign&start_date=2025-11-01',
        {
            headers: {
                'X-API-Key': 'your-api-key'
            }
        }
    );
    
    const data = await response.json();
    
    console.log('Campaign Performance:');
    data.stats.forEach(stat => {
        console.log(`${stat.group_key}: ${stat.total_conversions} conversions`);
    });
}
```

---

## Python

### Track Conversion

```python
import requests

def track_conversion(platform, campaign):
    url = 'https://yoursite.com/wp-json/mct/v1/track'
    headers = {
        'Content-Type': 'application/json',
        'X-API-Key': 'your-api-key'
    }
    data = {
        'utm_campaign': campaign,
        'platform': platform,
        'landing_page': 'https://landing.com',
        'event_name': 'Lead'
    }
    
    response = requests.post(url, json=data, headers=headers)
    result = response.json()
    
    if result['success']:
        print(f"Tracked! Conversion ID: {result['conversion_id']}")
    else:
        print(f"Error: {result['message']}")

track_conversion('discord', 'warcry_launch')
```

### Get Conversions

```python
import requests
import pandas as pd

def get_conversions(campaign=None, start_date=None, limit=100):
    url = 'https://yoursite.com/wp-json/mct/v1/conversions'
    headers = {'X-API-Key': 'your-api-key'}
    
    params = {'limit': limit}
    if campaign:
        params['utm_campaign'] = campaign
    if start_date:
        params['start_date'] = start_date
    
    response = requests.get(url, headers=headers, params=params)
    data = response.json()
    
    # Convert to DataFrame
    df = pd.DataFrame(data['data'])
    return df

# Usage
df = get_conversions(campaign='warcry_launch', start_date='2025-11-01')
print(df[['id', 'platform', 'utm_campaign', 'created_at']])

# Analytics
print(f"\nTotal conversions: {len(df)}")
print(f"\nBy platform:")
print(df['platform'].value_counts())
```

### Database Direct Access

```python
import mysql.connector
import pandas as pd

def query_database():
    conn = mysql.connector.connect(
        host='your-host',
        port=3306,
        user='mct_readonly_xxx',
        password='your-password',
        database='your-database'
    )
    
    query = """
        SELECT 
            utm_campaign,
            platform,
            COUNT(*) as conversions,
            COUNT(DISTINCT fbclid) as unique_clicks
        FROM wp_meta_conversions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY utm_campaign, platform
        ORDER BY conversions DESC
    """
    
    df = pd.read_sql(query, conn)
    conn.close()
    
    return df

# Usage
df = query_database()
print(df)
```

### Export to CSV

```python
import requests
import csv
from datetime import datetime

def export_conversions_to_csv(filename='conversions.csv'):
    url = 'https://yoursite.com/wp-json/mct/v1/conversions'
    headers = {'X-API-Key': 'your-api-key'}
    
    all_conversions = []
    offset = 0
    limit = 1000
    
    while True:
        params = {'limit': limit, 'offset': offset}
        response = requests.get(url, headers=headers, params=params)
        data = response.json()
        
        if not data['data']:
            break
        
        all_conversions.extend(data['data'])
        offset += limit
        
        if len(data['data']) < limit:
            break
    
    # Write to CSV
    if all_conversions:
        keys = all_conversions[0].keys()
        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=keys)
            writer.writeheader()
            writer.writerows(all_conversions)
        
        print(f"Exported {len(all_conversions)} conversions to {filename}")

export_conversions_to_csv()
```

---

## PHP

### Track Conversion

```php
<?php
function trackConversion($platform, $campaign) {
    $url = 'https://yoursite.com/wp-json/mct/v1/track';
    $apiKey = 'your-api-key';
    
    $data = [
        'utm_campaign' => $campaign,
        'platform' => $platform,
        'landing_page' => 'https://landing.com',
        'event_name' => 'Lead'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);
    
    if ($result['success']) {
        echo "Tracked! Conversion ID: " . $result['conversion_id'];
    } else {
        echo "Error: " . $result['message'];
    }
}

trackConversion('discord', 'warcry_launch');
```

### Get Conversions

```php
<?php
function getConversions($campaign = null, $limit = 100) {
    $url = 'https://yoursite.com/wp-json/mct/v1/conversions';
    $apiKey = 'your-api-key';
    
    $params = ['limit' => $limit];
    if ($campaign) {
        $params['utm_campaign'] = $campaign;
    }
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Key: ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    return $data['data'];
}

$conversions = getConversions('warcry_launch');
foreach ($conversions as $conv) {
    echo "{$conv['id']}: {$conv['platform']} - {$conv['created_at']}\n";
}
```

### Database Query

```php
<?php
$pdo = new PDO(
    'mysql:host=your-host;port=3306;dbname=your-database',
    'mct_readonly_xxx',
    'your-password'
);

$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as conversions
    FROM wp_meta_conversions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "{$row['date']}: {$row['conversions']} conversions\n";
}
```

---

## cURL (Command Line)

### Track Conversion

```bash
curl -X POST "https://yoursite.com/wp-json/mct/v1/track" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{
    "utm_campaign": "warcry_launch",
    "utm_source": "facebook",
    "platform": "discord",
    "landing_page": "https://landing.com"
  }'
```

### Get Conversions

```bash
curl "https://yoursite.com/wp-json/mct/v1/conversions?utm_campaign=warcry_launch&limit=10" \
  -H "X-API-Key: your-api-key"
```

### Get Statistics

```bash
curl "https://yoursite.com/wp-json/mct/v1/stats?group_by=campaign" \
  -H "X-API-Key: your-api-key" \
  | jq '.'
```

### Test Endpoint

```bash
curl "https://yoursite.com/wp-json/mct/v1/test"
```

---

## Google Apps Script

### Sync to Google Sheets

```javascript
function syncConversionsToSheet() {
  const API_URL = 'https://yoursite.com/wp-json/mct/v1/conversions';
  const API_KEY = 'your-api-key';
  const SHEET_NAME = 'Conversions';
  
  // Get conversions
  const response = UrlFetchApp.fetch(API_URL + '?limit=1000', {
    headers: {
      'X-API-Key': API_KEY
    }
  });
  
  const data = JSON.parse(response.getContentText());
  const conversions = data.data;
  
  // Get sheet
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET_NAME);
  if (!sheet) {
    SpreadsheetApp.getActiveSpreadsheet().insertSheet(SHEET_NAME);
  }
  
  // Clear existing data
  sheet.clear();
  
  // Headers
  const headers = ['ID', 'Campaign', 'Source', 'Platform', 'FBCLID', 'Created At'];
  sheet.appendRow(headers);
  
  // Data
  conversions.forEach(conv => {
    sheet.appendRow([
      conv.id,
      conv.utm_campaign || '',
      conv.utm_source || '',
      conv.platform || '',
      conv.fbclid || '',
      conv.created_at
    ]);
  });
  
  Logger.log('Synced ' + conversions.length + ' conversions');
}

// Run every hour
function createTrigger() {
  ScriptApp.newTrigger('syncConversionsToSheet')
    .timeBased()
    .everyHours(1)
    .create();
}
```

---

## Power BI / Excel

### Power Query M

```m
let
    Source = Json.Document(Web.Contents(
        "https://yoursite.com/wp-json/mct/v1/conversions",
        [
            Headers=[
                #"X-API-Key"="your-api-key",
                #"Content-Type"="application/json"
            ],
            Query=[
                limit="1000",
                utm_campaign="warcry_launch"
            ]
        ]
    )),
    data = Source[data],
    ToTable = Table.FromList(data, Splitter.SplitByNothing(), null, null, ExtraValues.Error),
    ExpandedTable = Table.ExpandRecordColumn(ToTable, "Column1", 
        {"id", "utm_campaign", "platform", "created_at"}, 
        {"ID", "Campaign", "Platform", "Created At"}
    )
in
    ExpandedTable
```

---

## Zapier / Make.com

### Webhook Configuration

**Trigger:** Webhook (Custom Request)

**URL:** `https://yoursite.com/wp-json/mct/v1/conversions?limit=10`

**Method:** GET

**Headers:**
```
X-API-Key: your-api-key
```

**Parse Response:** JSON

**Actions:**
- Send to Google Sheets
- Create CRM contact
- Send Slack notification
- etc.

---

## SQL Queries Avanzate

### Conversion Funnel Analysis

```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_conversions,
    COUNT(DISTINCT fbclid) as unique_clicks,
    ROUND(COUNT(*) / COUNT(DISTINCT fbclid) * 100, 2) as conversion_rate,
    SUM(CASE WHEN platform = 'discord' THEN 1 ELSE 0 END) as discord,
    SUM(CASE WHEN platform = 'telegram' THEN 1 ELSE 0 END) as telegram
FROM wp_meta_conversions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Campaign Performance

```sql
SELECT 
    utm_campaign,
    utm_source,
    COUNT(*) as conversions,
    COUNT(DISTINCT ip_address) as unique_visitors,
    SUM(CASE WHEN meta_sent = 1 THEN 1 ELSE 0 END) as sent_to_meta,
    MIN(created_at) as first_conversion,
    MAX(created_at) as last_conversion
FROM wp_meta_conversions
WHERE utm_campaign IS NOT NULL
GROUP BY utm_campaign, utm_source
ORDER BY conversions DESC;
```

### Hourly Distribution

```sql
SELECT 
    HOUR(created_at) as hour,
    COUNT(*) as conversions,
    ROUND(AVG(CASE WHEN meta_sent = 1 THEN 1 ELSE 0 END) * 100, 2) as meta_success_rate
FROM wp_meta_conversions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY HOUR(created_at)
ORDER BY hour;
```

### Top Referrers

```sql
SELECT 
    referrer,
    COUNT(*) as conversions,
    COUNT(DISTINCT utm_campaign) as campaigns
FROM wp_meta_conversions
WHERE referrer IS NOT NULL
GROUP BY referrer
ORDER BY conversions DESC
LIMIT 20;
```

### Failed Meta Conversions

```sql
SELECT 
    id,
    utm_campaign,
    platform,
    created_at,
    meta_response
FROM wp_meta_conversions
WHERE meta_sent = 0
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

---

## Error Handling Examples

### JavaScript with Retry

```javascript
async function trackWithRetry(data, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': API_KEY
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`Attempt ${i + 1} failed:`, error);
            
            if (i === maxRetries - 1) {
                throw error;
            }
            
            // Wait before retry (exponential backoff)
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
        }
    }
}
```

### Python with Error Logging

```python
import requests
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def track_conversion_safe(data):
    try:
        response = requests.post(
            API_URL,
            json=data,
            headers={'X-API-Key': API_KEY},
            timeout=10
        )
        response.raise_for_status()
        
        result = response.json()
        logger.info(f"Conversion tracked: {result['conversion_id']}")
        return result
        
    except requests.exceptions.Timeout:
        logger.error("Request timeout")
        return None
    except requests.exceptions.HTTPError as e:
        logger.error(f"HTTP error: {e.response.status_code}")
        return None
    except Exception as e:
        logger.error(f"Unexpected error: {str(e)}")
        return None
```

---

## Best Practices

1. **Always use HTTPS** per API calls
2. **Store API key securely** (environment variables, secrets manager)
3. **Handle errors gracefully** (retry logic, fallbacks)
4. **Implement rate limiting** per evitare ban
5. **Cache responses** quando possibile
6. **Log failures** per debugging
7. **Validate data** prima di inviare
8. **Use pagination** per large datasets
9. **Set timeouts** per evitare hang
10. **Monitor API usage** per detect issues
