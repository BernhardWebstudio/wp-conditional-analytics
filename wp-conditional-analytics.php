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
    add_action('wp_body_open', array($this, 'conditionally_output_analytics'), 20);
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
   * Actually output the banner or the analytics
   * 
   * @return void 
   */
  function conditionally_output_analytics()
  {
    $settings = $this->wpsf->get_settings();
    echo "<!-- Outputting wp-conditional-analytics stuff -->";

    $showBanner = $settings["general_activate_banner"];
    $includeAnalytics = !$showBanner;

    // always include the banner, but hidden
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

      .hidden {
        display: none;
        visibility: collapse;
      }
    </style>
    <div class="wp-conditional-analytics-banner fixed full-width hidden" id="wpca_banner">
      <script type="text/javascript">
        function wpcaSetCookie(cname, cvalue, exdays) {
          const d = new Date();
          d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
          let expires = "expires=" + d.toUTCString();
          document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function hideBanner() {
          document.getElementById("wpca_banner").classList.add("hidden");
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
          <button type="button" class="button btn btn-primary btn-accept" onclick='wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "true", <?php echo $settings["general"]["acceptance_save_duration"] ?>); loadAnalytics(); hideBanner()'><?php _e('Allow', 'wp-conditional-analytics'); ?></button>
          <button type="button" class="button btn btn-secondary btn-cancel" onclick='wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "false", <?php echo $settings["general"]["acceptance_decline_duration"] ?>); hideBanner()'><?php _e('Decline', 'wp-conditional-analytics'); ?></button>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      function wpcaGetCookie(name) {
        var dc = document.cookie;
        var prefix = name + "=";
        var begin = dc.indexOf("; " + prefix);
        if (begin == -1) {
          begin = dc.indexOf(prefix);
          if (begin != 0) return null;
        } else {
          begin += 2;
          var end = document.cookie.indexOf(";", begin);
          if (end == -1) {
            end = dc.length;
          }
        }
        // because unescape has been deprecated, replaced with decodeURI
        //return unescape(dc.substring(begin + prefix.length, end));
        return decodeURI(dc.substring(begin + prefix.length, end));
      }

      var myCookie = wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>");
      if (myCookie == null) {
        document.getElementById("wpca_banner").classList.remove("hidden");
      }
    </script>
    <?php

    //////////////////////////////// ActiveCampaign Analytics
    ?>
    <script type="text/javascript">
      window.addEventListener('load', () => {
        if (typeof vgo === "function" && wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "true") {
          vgo('process', 'allowTracking');
        }
      });
    </script>
    <?php
    //////////////////////////////// Google Analytics

    $ganalytics_tag = $settings["google_analytics_google_analytics_tag"];
    if ($ganalytics_tag) {
    ?>
      <script>
        function loadAnalytics() {
          var addGoogleAnalytics = document.createElement("script");
          addGoogleAnalytics.setAttribute("src", "https://www.googletagmanager.com/gtag/js?id=<?php echo $ganalytics_tag; ?>");
          addGoogleAnalytics.async = "true";
          document.head.appendChild(addGoogleAnalytics);

          var addDataLayer = document.createElement("script");
          var dataLayerData = document.createTextNode("window.dataLayer = window.dataLayer || []; \n function gtag(){dataLayer.push(arguments);} \n gtag('js', new Date()); \n gtag('config', '<?php echo $ganalytics_tag; ?>');");
          addDataLayer.appendChild(dataLayerData);
          document.head.appendChild(addDataLayer);
        }

        if (wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "true") {

        }
      </script>
<?php
    }
  }
}

new WpConditionalAnalytics();
