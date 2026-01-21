# wp-conditional-analytics

WordPress Plugin providing a "accept" banner before embedding Google Analytics (and other scripts that can be registered)

## Features

- **WP Consent API Integration**: Automatically integrates with the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) when available
- **Dual Role**: Acts as both a consent management plugin (when no other is installed) or as an analytics manager (when used with consent plugins)
- **Granular Consent**: Support for content-type specific consent (analytics, marketing, social media, video, maps, etc.)
- **Gutenberg Block**: External Content Wrapper block for conditional content rendering
- **GDPR Compliant**: EU timezone detection and opt-in banner

## Settings

This plugin has a setting page under "Tools".
There, you can set some common Analytics codes, and disable the banner altogether if you only use
the Gutenberg part of it.

**Note**: When a consent management plugin (supporting WP Consent API) is installed, this plugin's banner will be automatically hidden to prevent conflicts.

## WP Consent API Integration

This plugin fully supports the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/), allowing it to work seamlessly with consent management plugins like:

- Complianz GDPR/CCPA
- CookieYes
- Other WP Consent API compatible plugins

### How it works

1. **With WP Consent API**: The plugin detects if the API is available and uses it for consent checks
2. **Without WP Consent API**: Falls back to its own cookie-based consent system
3. **Content Type Mapping**: Maps content types to WP Consent API categories:
   - `analytics` → `statistics`
   - `marketing` → `marketing`
   - `social-media` → `marketing`
   - `video` → `marketing`
   - `maps` → `functional`
   - `general` → `functional`
   - `custom` → `preferences`

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
wpcaCookiesAreAllowed(); // returns whether all cookies are allowed
wpcaAcceptCookies(); // trigger "accept all cookies"
wpcaContentTypeIsAllowed(contentType); // returns whether the given content type is allowed
wpcaAcceptContentType(contentType); // trigger "accept the given content type"
wpcaHasConsent(category); // check consent for WP Consent API category (statistics, marketing, etc.)
```

Additionally, the event `wpcaCookiesAccepted` is triggered when all cookies are accepted,
and `wpcaContentTypeAccepted` is triggered if a certain subset of cookies for one content type is accepted.

The plugin also listens to WP Consent API events (`wp_listen_for_consent_change`) when the API is available.

## Gutenberg

This plugin provides a Gutenberg block, called "External Content Wrapper",
which blocks the child blocks from being rendered until sufficient cookie permissions are given.

## Usage Example

1. Add the "External Content Wrapper" block to your post/page
2. Select the content type (Analytics, Marketing, Social Media, etc.)
3. Add your content blocks inside the wrapper
4. The content will only be displayed after the user accepts the corresponding cookie type

This is particularly useful for embedding content from external sources like YouTube videos, Google Maps, or social media feeds that may track user data.

The block automatically integrates with WP Consent API when available, checking the appropriate consent category based on the selected content type.
