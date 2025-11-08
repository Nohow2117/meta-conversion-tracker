-- Migration Script: Add Country and UTM Parameters to Beacon Log
-- 
-- This script adds the following columns to the wp_mct_beacon_log table:
-- - page_url: URL of the page where the captcha was completed
-- - country: ISO country code (2 chars) detected from IP address
-- - utm_source, utm_medium, utm_campaign, utm_content, utm_term: UTM tracking parameters
--
-- Run this script if you're upgrading from a version without country/UTM tracking
-- NOTE: Replace 'wp_' prefix with your actual WordPress table prefix if different

-- Check if table exists before attempting migration
-- SELECT COUNT(*) FROM information_schema.tables 
-- WHERE table_schema = DATABASE() AND table_name = 'wp_mct_beacon_log';

-- Add page_url column (if not exists)
ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS page_url TEXT AFTER referrer;

-- Add country column with index (if not exists)
ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS country VARCHAR(2) AFTER page_url;

-- Add UTM parameters columns (if not exists)
ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS utm_source VARCHAR(255) AFTER custom_data;

ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS utm_medium VARCHAR(255) AFTER utm_source;

ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(255) AFTER utm_medium;

ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS utm_content VARCHAR(255) AFTER utm_campaign;

ALTER TABLE wp_mct_beacon_log 
ADD COLUMN IF NOT EXISTS utm_term VARCHAR(255) AFTER utm_content;

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_country ON wp_mct_beacon_log(country);
CREATE INDEX IF NOT EXISTS idx_utm_campaign ON wp_mct_beacon_log(utm_campaign);

-- Verify migration
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'wp_mct_beacon_log'
  AND COLUMN_NAME IN ('page_url', 'country', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term')
ORDER BY ORDINAL_POSITION;

-- Success message (comment out if running in non-interactive mode)
-- SELECT 'Migration completed successfully! New columns added: page_url, country, utm_source, utm_medium, utm_campaign, utm_content, utm_term' AS status;
