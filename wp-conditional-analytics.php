<?php

/**
 * Plugin Name:  Conditional Analytics
 * Description:  Embeds Google Analytics & Co. only if "accept" banner was accepted
 * Author:       Tim Bernhard
 * Author URI:   https://genieblog.ch
 * Version:      1.0.0
 * License:      MIT License
 * License URI:  https://opensource.org/license/mit/
 * Text Domain:  wp-conditional-analytics
 * Domain Path:  /languages
 */

class WpConditionalAnalytics
{
  public const COOKIE_NAME = 'wp_conditional_ana_banner';

  /**
   * @var string
   */
  private $plugin_path;

  /**
   * @var WordPressSettingsFramework
   */
  private $wpsf;

  /**
   * WPSFTest constructor.
   */
  function __construct()
  {
    $this->plugin_path = plugin_dir_path(__FILE__);

    // Include and create a new WordPressSettingsFramework
    require_once(__DIR__ . '/wp-settings-framework/wp-settings-framework.php');
    $this->wpsf = new WordPressSettingsFramework($this->plugin_path . 'settings/settings-general.php', 'bewe_wp_conditional_analytics');

    // Add admin menu
    add_action('admin_menu', array($this, 'add_settings_page'), 20);

    // Add an optional settings validation filter (recommended)
    add_filter($this->wpsf->get_option_group() . '_settings_validate', array(&$this, 'validate_settings'));

    // Add actual output
    add_action('wp_body_open', array($this, 'conditionally_output_anayltics'), 20);

    // Make sure to serve a different version depending on WP Rocket
    add_filter('rocket_cache_dynamic_cookies', array(&$this, 'rocket_add_conditionally_analytics_dynamic_cookies'));
  }

  /**
   * Add settings page.
   */
  function add_settings_page()
  {
    $this->wpsf->add_settings_page(array(
      'parent_slug' => 'tools.php',
      'page_title'  => __('Conditional Analytics', 'wp-conditional-analytics'),
      'menu_title'  => __('Conditional Analytics', 'wp-conditional-analytics'),
    ));
  }

  /**
   * Validate settings.
   * 
   * @param $input
   *
   * @return mixed
   */
  function validate_settings($input)
  {
    // Do your settings validation here
    // Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
    return $input;
  }

  /**
   * Tell WP Rocket to serve a different cache depending on the value of the cookie
   * 
   * @param array $cookies 
   * @return array 
   */
  function rocket_add_conditionally_analytics_dynamic_cookies($cookies)
  {
    $cookies[] = self::COOKIE_NAME;
    return $cookies;
  }

  /**
   * Actually output the banner or the analytics
   * 
   * @return void 
   */
  function conditionally_output_anayltics()
  {
    $settings = $this->wpsf->get_settings();
    echo "<!-- Outputting wp-conditional-analytics stuff -->";

    $showBanner = $settings["general_activate_banner"];
    $includeAnalytics = !$showBanner;

    if (isset($_COOKIE[self::COOKIE_NAME])) {
      $showBanner = false;
      $includeAnalytics = filter_var($_COOKIE[self::COOKIE_NAME], FILTER_VALIDATE_BOOLEAN);
    }

    if ($includeAnalytics) {
      //////////////////////////////// ActiveCampaign Analytics
?>
      <script type="text/javascript">
        window.addEventListener('load', () => {
          if (typeof vgo === "function") {
            vgo('process', 'allowTracking');
          }
        });
      </script>
      <?php
      //////////////////////////////// Google Analytics

      $ganalytics_tag = $settings["google_analytics_google_analytics_tag"];
      if ($ganalytics_tag) {

        if (str_starts_with($ganalytics_tag, "UA-")) {
      ?>
          <!-- Google tag (gtag.js) -->
          <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ganalytics_tag; ?>"></script>
          <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
              dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', '<?php echo $ganalytics_tag; ?>');
          </script>

        <?php
        } else {
        ?>
          <!-- Google tag (gtag.js) -->
          <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ganalytics_tag; ?>"></script>
          <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
              dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', '<?php echo $ganalytics_tag; ?>');
          </script>
      <?php
        }
      }
    }

    if ($showBanner) {
      ?>
      <style>
        .wp-conditional-analytics-banner {
          width: 100%;
          position: fixed;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: var(--wp--preset--color--bg-light, inherit);
          color: var(--wp--preset--color--bg-dark, inherit);
          z-index: 999;
          padding: 0.75rem;
        }

        .wp-conditional-analytics-banner-content {
          display: flex;
          gap: 1rem;
        }

        .wp-conditional-analytics-banner-content p {
          margin: 0;
          padding: 0;
        }

        .wp-conditional-analytics-banner-buttons {
          display: flex;
          align-items: baseline;
          gap: 0.5rem;
        }

        @media screen and (min-width: 768px) {
          .wp-conditional-analytics-banner-content {
            flex-basis: 50%;
          }
        }

        @media screen and (max-width: 768px) {
          .wp-conditional-analytics-banner-content {
            flex-wrap: wrap;
          }

          .wp-conditional-analytics-banner-buttons {
            flex-wrap: wrap;
          }
        }
      </style>
      <div class="wp-conditional-analytics-banner fixed full-width">
        <script type="text/javascript">
          function wpcaSetCookie(cname, cvalue, exdays) {
            const d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            let expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
          }
        </script>
        <div class="flex row wp-conditional-analytics-banner-content">
          <div class="column wp-conditional-analytics-banner-text">
            <p>
              <?php _e('Can we use Analytics, please?', 'wp-conditional-analytics'); ?>
            </p>
          </div>
          <div class="column wp-conditional-analytics-banner-buttons button-group">
            <a class="button" href="<?php echo get_privacy_policy_url(); ?>"><?php _e('Privacy Policy', 'wp-conditional-analytics'); ?></a>
            <button type="button" class="button btn btn-primary btn-accept" onclick='wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "true", <?php echo $settings["general"]["acceptance_save_duration"] ?>); location.reload();'><?php _e('Allow', 'wp-conditional-analytics'); ?></button>
            <button type="button" class="button btn btn-secondary btn-cancel" onclick='wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "false", <?php echo $settings["general"]["acceptance_decline_duration"] ?>); location.reload();'><?php _e('Decline', 'wp-conditional-analytics'); ?></button>
          </div>
        </div>
      </div>
<?php
    }
  }
}

new WpConditionalAnalytics();
