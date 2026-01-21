# wp-conditional-analytics

WordPress Plugin providing a "accept" banner before embedding Google Analytics (and other scripts that can be registered)

## Settings

This plugin has a setting page under "Tools".
There, you can set some common Analytics codes, and disable the banner altogether if you only use
the Gutenberg part of it.

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
```

Additionally, the event `wpcaCookiesAccepted` is triggered when all cookies are accepted,
and `wpcaContentTypeAccepted` is triggered if a certain subset of cookies for one content type is accepted.

## Gutenberg

This plugin provides a Gutenberg block, called "External Content Wrapper",
which blocks the child blocks from being rendered until sufficient cookie permissions are given.

### Usage Example

1. Add the "External Content Wrapper" block to your post/page
2. Select the content type (Analytics, Marketing, Social Media, etc.)
3. Add your content blocks inside the wrapper
4. The content will only be displayed after the user accepts the corresponding cookie type

This is particularly useful for embedding content from external sources like YouTube videos, Google Maps, or social media feeds that may track user data.
