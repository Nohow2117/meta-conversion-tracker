# Meta Conversion Tracker

WordPress plugin professionale per tracking conversioni da landing pages a Meta Ads.

## 🎯 Cosa Fa

Cattura automaticamente:
- ✅ Parametri UTM (source, medium, campaign, content, term)
- ✅ Facebook Click ID (FBCLID)
- ✅ Indirizzo IP e User Agent
- ✅ Browser fingerprint (canvas, WebGL, screen, timezone)
- ✅ Cookies Facebook (_fbc, _fbp)

Poi:
- 💾 Salva tutto in database MySQL
- 📤 Invia eventi a Meta Conversions API
- 🔌 Espone REST API per integrazioni
- 🗄️ Permette query dirette al database

## 🚀 Quick Start

### 1. Installa Plugin
```bash
# Upload to WordPress
wp-content/plugins/meta-conversion-tracker/

# Activate
WordPress Admin → Plugins → Activate
```

### 2. Configura Meta API
```
WordPress Admin → Conversion Tracker → Settings

Meta Pixel ID: [your-pixel-id]
Access Token: [your-token]
☑ Enable Meta API

Save → Test Connection ✅
```

### 3. Aggiungi a Landing Page
```html
<script src="https://yoursite.com/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js"></script>
<script>
const tracker = new MCTTracker({
    apiUrl: 'https://yoursite.com/wp-json/mct/v1/track',
    apiKey: 'your-api-key'
});

function goToDiscord() {
    tracker.trackAndRedirect('https://discord.gg/yourserver', {
        platform: 'discord'
    });
}
</script>
```

### 4. Test
Visita landing page con UTM:
```
https://yourlandingpage.com?utm_campaign=test&utm_source=facebook
```

Clicca button → Check Dashboard → Vedi conversione ✅

## 📚 Documentazione

- **[Quick Start](docs/QUICK-START.md)** - Setup in 10 minuti
- **[README Completo](docs/README.md)** - Documentazione dettagliata
- **[Architecture](docs/ARCHITECTURE.md)** - Come funziona il sistema
- **[API Examples](docs/API-EXAMPLES.md)** - Esempi codice in tutti i linguaggi

## 🔌 API Endpoints

```bash
# Track conversion
POST /wp-json/mct/v1/track

# Get conversions
GET /wp-json/mct/v1/conversions?utm_campaign=xxx

# Get statistics
GET /wp-json/mct/v1/stats?group_by=campaign

# Test API
GET /wp-json/mct/v1/test
```

## 🗄️ Database Access

Query dirette al database per analytics:

```python
import mysql.connector

db = mysql.connector.connect(
    host="your-host",
    user="mct_readonly_xxx",
    password="your-password",
    database="your-database"
)

cursor = db.cursor()
cursor.execute("SELECT * FROM wp_meta_conversions WHERE utm_campaign = %s", ('warcry',))
conversions = cursor.fetchall()
```

## 📊 Features

### Core
- Automatic UTM capture
- FBCLID tracking
- Browser fingerprinting
- IP address logging
- Meta Conversions API integration
- REST API with authentication
- Direct database access
- WordPress admin dashboard

### Advanced
- Event deduplication
- Retry failed conversions
- Custom data support
- Logging system
- CORS support
- Pagination
- Filtering & statistics
- Multi-platform tracking (Discord, Telegram, etc.)

## 🛠️ Requirements

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
- HTTPS (required for Meta API)

## 📁 Struttura Plugin

```
meta-conversion-tracker/
├── meta-conversion-tracker.php    # Main plugin file
├── includes/                      # Core classes
│   ├── class-mct-database.php
│   ├── class-mct-api.php
│   ├── class-mct-meta-api.php
│   └── class-mct-fingerprint.php
├── admin/                         # Admin interface
│   ├── class-mct-admin.php
│   └── views/
├── assets/                        # Frontend assets
│   ├── js/
│   │   ├── tracker.js            # Landing page tracker
│   │   └── admin.js
│   └── css/
│       └── admin.css
├── docs/                          # Documentation
│   ├── README.md
│   ├── QUICK-START.md
│   ├── ARCHITECTURE.md
│   └── API-EXAMPLES.md
└── examples/                      # Integration examples
    └── warcry-integration.html
```

## 🔒 Security

- ✅ API key authentication
- ✅ Read-only database user
- ✅ Input sanitization
- ✅ Output escaping
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ HTTPS required
- ✅ Prepared statements

## 📈 Use Cases

### Marketing Teams
- Track Meta Ads conversions
- Measure campaign performance
- Optimize ad spend
- A/B test landing pages

### Analytics
- Export data to BI tools
- Custom dashboards
- Attribution modeling
- Funnel analysis

### CRM Integration
- Sync conversions to CRM
- Lead scoring
- Customer journey tracking
- Automated workflows

### Data Teams
- Direct database access
- ETL pipelines
- Data warehouse integration
- Custom reporting

## 🎨 Admin Panel

### Dashboard
- Total conversions
- Today's conversions
- Meta API status
- Top campaigns
- Recent conversions

### Conversions
- Full conversion list
- Filters (campaign, platform, date)
- Pagination
- Export capabilities

### Settings
- Meta API configuration
- API key management
- Logging options
- Test connection

### API Docs
- Complete API reference
- Code examples
- Authentication guide
- Integration snippets

### Database Access
- Connection credentials
- SQL query examples
- Security information
- Language-specific examples

## 🤝 Support

Per domande o problemi:
1. Check [Documentation](docs/README.md)
2. Check [Troubleshooting](docs/README.md#troubleshooting)
3. Enable debug logging
4. Check WordPress error logs

## 📝 License

GPL v2 or later

## 🎯 Roadmap

- [ ] Webhook support
- [ ] GraphQL API
- [ ] Multi-pixel support
- [ ] Advanced analytics dashboard
- [ ] Fraud detection
- [ ] A/B testing integration
- [ ] Machine learning attribution

## ⭐ Version

**1.0.1** - Bug fix release (2025-11-06)
- Fixed referrer parameter validation error
- Improved API parameter handling

**1.0.0** - Initial release (2025-11-05)

---

Made with ❤️ for conversion tracking
