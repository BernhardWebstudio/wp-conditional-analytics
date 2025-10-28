# wp-conditional-analytics

WordPress Plugin providing a "accept" banner before embedding Google Analytics (and other scripts that can be registered)

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

```
