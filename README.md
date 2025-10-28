# wp-conditional-analytics

WordPress Plugin providing a "accept" banner before embedding Google Analytics (and other scripts that can be registered)

## Features

- Display a cookie consent banner for EU visitors
- Conditionally load Google Analytics based on user consent
- **WP Consent API Integration**: Automatically integrates with the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) for seamless compatibility with consent management platforms
- Support for additional custom scripts
- Configurable cookie duration settings

## WP Consent API Compatibility

This plugin is fully compatible with the WP Consent API. When a consent management plugin that implements the WP Consent API is active:

- The plugin will automatically detect and use the WP Consent API
- User consent is handled through the consent management platform's interface
- The built-in banner will be hidden (consent UI is managed by your consent plugin)
- Analytics scripts are loaded based on consent given through the API
- Consent changes are tracked in real-time

This allows you to use professional consent management solutions like Complianz, CookieYes, or other WP Consent API compatible plugins while still benefiting from this plugin's analytics management.

## API

### PHP

You can use, for example, 

```php
if (function_exists('wpca_enqueue_script')){
  wpca_enqueue_script($id, $url);
} else {
  wp_enqueue_script($id, $url);
}
```

to enqueue a script that should be loaded in case the cookies are accepted.
Do this wherever (i.e., in a init hook) you would do the `wp_enqueue_script`.

### JavaScript

This plugin exposes the following global functions:

```javascript
// Load analytics scripts (if consent is given)
wpcaLoadAnalytics();

// Accept cookies and load analytics
wpcaAcceptCookies(reload = false);

// Decline cookies
wpcaDeclineCookies();

// Check if user has given consent (via WP Consent API or cookie)
wpcaHasConsent();

// Conditionally load a script based on consent status
wpcaConditionallyLoadScript(id, url, attributes = null);

// Load a script unconditionally
wpcaLoadScript(id, url, attributes = null);

// Get cookie value
wpcaGetCookie(name);

// Set cookie
wpcaSetCookie(name, value, exdays);
```

When the WP Consent API is active, consent changes are automatically handled through the `wp_listen_for_consent_change` event.
