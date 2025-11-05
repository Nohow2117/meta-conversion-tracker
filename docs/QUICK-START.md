# Quick Start Guide

Get up and running in 10 minutes.

## Step 1: Install Plugin (2 minutes)

1. Upload `meta-conversion-tracker` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Activate "Meta Conversion Tracker"

✅ Plugin creates database tables automatically

## Step 2: Configure Meta API (3 minutes)

1. Go to [Meta Events Manager](https://business.facebook.com/events_manager2/list/pixel)
2. Get your **Pixel ID** (15-16 digits)
3. Generate **Access Token** in Settings → Conversions API
4. In WordPress: Conversion Tracker → Settings
5. Enter Pixel ID and Access Token
6. Check "Enable Meta API"
7. Click "Save Settings"
8. Click "Test Connection" ✅

## Step 3: Get API Key (1 minute)

1. Go to Settings page
2. Copy your API Key
3. Save it somewhere secure

## Step 4: Add to Landing Page (4 minutes)

Add this to your landing page HTML:

```html
<!-- Load tracker -->
<script src="https://YOURSITE.com/wp-content/plugins/meta-conversion-tracker/assets/js/tracker.js"></script>

<!-- Initialize -->
<script>
const tracker = new MCTTracker({
    apiUrl: 'https://YOURSITE.com/wp-json/mct/v1/track',
    apiKey: 'YOUR-API-KEY-HERE'
});

// Track when user clicks button
function goToDiscord() {
    tracker.trackAndRedirect('https://discord.gg/yourserver', {
        platform: 'discord'
    });
}
</script>

<!-- Button -->
<button onclick="goToDiscord()">Join Discord</button>
```

Replace:
- `YOURSITE.com` with your WordPress domain
- `YOUR-API-KEY-HERE` with your API key
- `https://discord.gg/yourserver` with your Discord/Telegram link

## Step 5: Test (2 minutes)

1. Visit your landing page with UTM parameters:
   ```
   https://yourlandingpage.com?utm_campaign=test&utm_source=facebook
   ```
2. Click the button
3. Check WordPress Admin → Conversion Tracker → Dashboard
4. You should see 1 conversion ✅
5. Check Meta Events Manager → Test Events (if test code configured)

## Done! 🎉

Your conversion tracking is now live.

## Next Steps

- View conversions: Dashboard or Conversions page
- Get API docs: API Docs page
- Database access: Database Access page
- Add to more landing pages: Use same tracker code

## Common Issues

**"401 Unauthorized"**
→ Check API key is correct

**"Conversions not appearing"**
→ Check browser console for errors
→ Enable debug mode: `debug: true`

**"Meta not receiving events"**
→ Test connection in Settings
→ Check Pixel ID and Access Token
→ Verify HTTPS is enabled

## Need Help?

Check the full documentation in `docs/README.md`
