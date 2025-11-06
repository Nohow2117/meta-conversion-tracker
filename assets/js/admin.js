/**
 * Meta Conversion Tracker - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Copy to clipboard
        $('.mct-copy-btn').on('click', function() {
            const text = $(this).data('copy');
            const $btn = $(this);
            
            navigator.clipboard.writeText(text).then(function() {
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(function() {
                    $btn.text(originalText);
                }, 2000);
            });
        });
        
        // Test Meta connection
        $('#mct-test-connection').on('click', function() {
            const $btn = $(this);
            const $result = $('#mct-test-result');
            
            $btn.prop('disabled', true).text('Testing...');
            $result.html('');
            
            $.ajax({
                url: mctAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mct_test_meta_connection',
                    nonce: mctAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span style="color: red;">✗ Connection failed</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Connection');
                }
            });
        });
        
        // Retry failed conversions
        $('#mct-retry-failed').on('click', function() {
            const $btn = $(this);
            
            if (!confirm('Retry sending failed conversions to Meta?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: mctAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mct_retry_failed',
                    nonce: mctAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Retry complete!\nSuccess: ' + response.data.success + '\nFailed: ' + response.data.failed);
                        location.reload();
                    } else {
                        alert('Retry failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Request failed');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Retry Failed Conversions');
                }
            });
        });
        
        // Regenerate API key
        $('#mct-regenerate-key').on('click', function() {
            const $btn = $(this);
            
            if (!confirm('Regenerate API key? This will invalidate the old key for all integrations.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: mctAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mct_regenerate_api_key',
                    nonce: mctAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#mct_api_key_display').val(response.data.api_key);
                        alert('New API key generated successfully!');
                    } else {
                        alert('Failed to regenerate key');
                    }
                },
                error: function() {
                    alert('Request failed');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Regenerate Key');
                }
            });
        });
        
        // Manual cleanup
        $('#mct-cleanup-now').on('click', function() {
            const $btn = $(this);
            const $result = $('#mct-cleanup-result');
            
            if (!confirm('Are you sure you want to delete all conversions older than 30 days? This action cannot be undone.')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Cleaning...');
            $result.html('');
            
            $.ajax({
                url: mctAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mct_cleanup_now',
                    nonce: mctAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html(
                            '<div class="notice notice-success inline">' +
                            '<p><strong>Cleanup completed!</strong><br>' +
                            'Conversions deleted: ' + response.data.conversions_deleted + '<br>' +
                            'Logs deleted: ' + response.data.logs_deleted + '</p>' +
                            '</div>'
                        );
                    } else {
                        $result.html(
                            '<div class="notice notice-error inline">' +
                            '<p>Cleanup failed: ' + (response.data || 'Unknown error') + '</p>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $result.html(
                        '<div class="notice notice-error inline">' +
                        '<p>Request failed</p>' +
                        '</div>'
                    );
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Clean Old Data Now');
                }
            });
        });
        
        // View conversion details
        $('.mct-view-details').on('click', function() {
            const conversionId = $(this).data('id');
            const $modal = $('#mct-details-modal');
            const $content = $('#mct-details-content');
            
            $modal.show();
            $content.html('Loading...');
            
            $.ajax({
                url: mctAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mct_get_conversion_details',
                    nonce: mctAdmin.nonce,
                    id: conversionId
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(formatConversionDetails(response.data));
                    } else {
                        $content.html('<p>Failed to load details</p>');
                    }
                },
                error: function() {
                    $content.html('<p>Request failed</p>');
                }
            });
        });
        
        // Close modal
        $('.mct-modal-close, .mct-modal-overlay').on('click', function() {
            $('#mct-details-modal').hide();
        });
        
        // Format conversion details
        function formatConversionDetails(data) {
            let html = '<h2>Conversion #' + data.id + '</h2>';
            html += '<table class="wp-list-table widefat">';
            
            const fields = {
                'ID': 'id',
                'Campaign': 'utm_campaign',
                'Source': 'utm_source',
                'Medium': 'utm_medium',
                'Content': 'utm_content',
                'Term': 'utm_term',
                'FBCLID': 'fbclid',
                'FBC': 'fbc',
                'FBP': 'fbp',
                'Platform': 'platform',
                'IP Address': 'ip_address',
                'User Agent': 'user_agent',
                'Fingerprint': 'fingerprint',
                'Landing Page': 'landing_page',
                'Referrer': 'referrer',
                'Event ID': 'event_id',
                'Event Name': 'event_name',
                'Meta Sent': 'meta_sent',
                'Meta Sent At': 'meta_sent_at',
                'Created At': 'created_at'
            };
            
            for (let label in fields) {
                let value = data[fields[label]];
                if (value === null || value === '') value = '-';
                if (fields[label] === 'meta_sent') value = value ? 'Yes' : 'No';
                
                html += '<tr><th style="width: 200px;">' + label + '</th><td>' + value + '</td></tr>';
            }
            
            html += '</table>';
            
            if (data.browser_fingerprint) {
                html += '<h3>Browser Fingerprint</h3>';
                html += '<pre>' + JSON.stringify(JSON.parse(data.browser_fingerprint), null, 2) + '</pre>';
            }
            
            if (data.custom_data) {
                html += '<h3>Custom Data</h3>';
                html += '<pre>' + JSON.stringify(JSON.parse(data.custom_data), null, 2) + '</pre>';
            }
            
            if (data.meta_response) {
                html += '<h3>Meta Response</h3>';
                html += '<pre>' + JSON.stringify(JSON.parse(data.meta_response), null, 2) + '</pre>';
            }
            
            return html;
        }
    });
    
})(jQuery);
