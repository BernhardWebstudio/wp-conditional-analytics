<?php

/**
 * Plugin Name:  Conditional Analytics
 * Description:  Embeds Google Analytics & Co. only if "accept" banner was accepted. Compatible with WP Consent API.
 * Author:       Tim Bernhard
 * Author URI:   https://genieblog.ch
 * Version:      1.1.0
 * License:      MIT License
 * License URI:  https://opensource.org/license/mit/
 * Text Domain:  wp-conditional-analytics
 * Domain Path:  /languages
 * Requires PHP: 7.0
 */

class WpConditionalAnalytics
{
  public const COOKIE_NAME = 'wp_conditional_ana_banner';

  /**
   * 
   * @var array
   */
  private $additionalScripts = [];

  /**
   * @var string
   */
  private $plugin_path;

  /**
   * @var WordPressSettingsFramework
   */
  private $wpsf;

  /**
   * @var bool
   */
  private $wp_consent_api_available = false;

  /**
   * WPSFTest constructor.
   */
  function __construct()
  {
    $this->plugin_path = plugin_dir_path(__FILE__);

    // Include and create a new WordPressSettingsFramework
    require_once(__DIR__ . '/wp-settings-framework/wp-settings-framework.php');
    $this->wpsf = new WordPressSettingsFramework($this->plugin_path . 'settings/settings-general.php', 'bewe_wp_conditional_analytics');

    // Check if WP Consent API is available
    add_action('plugins_loaded', array($this, 'check_wp_consent_api'));

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
   * Check if WP Consent API is available and register consent purposes
   */
  function check_wp_consent_api()
  {
    $settings = $this->wpsf->get_settings();
    $use_consent_api = isset($settings['general_use_wp_consent_api']) ? $settings['general_use_wp_consent_api'] : true;
    
    if ($use_consent_api && function_exists('wp_has_consent')) {
      $this->wp_consent_api_available = true;
      
      // Register our consent purposes
      add_filter('wp_consent_api_registered_' . 'statistics', array($this, 'register_consent_purpose'));
    }
  }

  /**
   * Register consent purpose with WP Consent API
   * 
   * @param array $purposes
   * @return array
   */
  function register_consent_purpose($purposes)
  {
    if (!in_array('wp-conditional-analytics', $purposes)) {
      $purposes[] = 'wp-conditional-analytics';
    }
    return $purposes;
  }

  /**
   * Check if user has given consent either through WP Consent API or our cookie
   * 
   * @return bool
   */
  function has_consent()
  {
    // If WP Consent API is available, use it
    if ($this->wp_consent_api_available && function_exists('wp_has_consent')) {
      return wp_has_consent('statistics');
    }
    
    // Otherwise, fall back to our own cookie checking
    return false; // Will be checked via JavaScript
  }

  public function registerScript($id, $url)
  {
    $this->additionalScripts[$id] = $url;
  }

  public function removeScript($id)
  {
    unset($this->additionalScripts[$id]);
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
        function wpcaLoadAnalytics() {
          wpcaLoadScript("google-analytics", "https://www.googletagmanager.com/gtag/js?id=<?php echo $ganalytics_tag; ?>");

          var addDataLayer = document.createElement("script");
          var dataLayerData = document.createTextNode("window.dataLayer = window.dataLayer || []; \n function gtag(){dataLayer.push(arguments);} \n gtag('js', new Date()); \n gtag('config', '<?php echo $ganalytics_tag; ?>');");
          addDataLayer.appendChild(dataLayerData);
          document.head.appendChild(addDataLayer);

          <?php
          // load the adidtional scripts we may load
          foreach ($this->additionalScripts as $id => $url) {
            echo "wpcaLoadScript('$id', '$url');";
          }
          ?>
        }
      </script>
    <?php
    } else {
    ?>
      <script>
        function wpcaLoadAnalytics() {}
      </script>
    <?php
    }

    //////////////////////////////// The heart of things
    echo "<!-- Outputting wp-conditional-analytics stuff -->";

    $showBanner = $settings["general_activate_banner"];
    $includeAnalytics = !$showBanner;

    // Check if using WP Consent API
    $useConsentAPI = $this->wp_consent_api_available;

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

      body.cookies-accepted .hide-on-cookies-accepted {
        display: none;
      }

      body.cookies-declined .hide-on-cookies-declined {
        display: none;
      }
    </style>
    <div class="wp-conditional-analytics-banner fixed full-width hidden" id="wpca_banner">
      <script type="text/javascript">
        const wpcaUseConsentAPI = <?php echo $useConsentAPI ? 'true' : 'false'; ?>;

        function wpcaSetCookie(cname, cvalue, exdays) {
          const d = new Date();
          d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
          let expires = "expires=" + d.toUTCString();
          document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function wpcaHideBanner() {
          document.getElementById("wpca_banner").classList.add("hidden");
        }
      </script>
      <div class="flex row wp-conditional-analytics-banner-content">
        <div class="column wp-conditional-analytics-banner-text">
          <p>
            <?php _e('Can we use Analytics, please?', 'wp-conditional-analytics'); ?>
          </p>
        </div>
        <div class="column wp-conditional-analytics-banner-buttons button-group wp-element-button-group">
          <a class="button btn wp-element-button " href="<?php echo get_privacy_policy_url(); ?>"><?php _e('Privacy Policy', 'wp-conditional-analytics'); ?></a>
          <button type="button" class="button btn wp-element-button btn-primary btn-accept" onclick='wpcaAcceptCookies()'><?php _e('Allow', 'wp-conditional-analytics'); ?></button>
          <button type="button" class="button btn wp-element-button btn-secondary btn-cancel" onclick='wpcaDeclineCookies()'><?php _e('Decline', 'wp-conditional-analytics'); ?></button>
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
      
      // Only show banner if not using WP Consent API and no cookie is set
      // If using WP Consent API, let the consent management plugin handle the UI
      if (!wpcaUseConsentAPI && myCookie == null) {
        document.getElementById("wpca_banner").classList.remove("hidden");
      }

      function wpcaLoadScript(id, url, attributes = null) {
        var script = document.createElement("script");
        script.setAttribute("src", url);
        script.setAttribute("id", id + "-js");
        script.async = true;
        if (attributes != null) {
          for (const [key, value] of Object.entries(attributes)) {
            script.setAttribute(key, value);
          }
        }
        document.head.appendChild(script);
      }

      function wpcaConditionallyLoadScript(id, url, attributes = null) {
        var hasConsent = wpcaUseConsentAPI && typeof wp_has_consent === 'function' 
          ? wp_has_consent('statistics') 
          : wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "true";
          
        if (hasConsent && !document.getElementById(id)) {
          wpcaLoadScript(id, url, attributes);
        }
      }

      function wpcaAcceptCookies(reload = false) {
        wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "true", <?php echo $settings["general_acceptance_save_duration"] ?>);
        
        // If WP Consent API is available, set consent via the API
        if (wpcaUseConsentAPI && typeof wp_set_consent === 'function') {
          wp_set_consent('statistics', 'allow');
        }
        
        wpcaLoadAnalytics();
        wpcaHideBanner();
        if (reload) {
          location.reload();
        }
      }

      function wpcaDeclineCookies() {
        wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "false", <?php echo $settings["general_acceptance_decline_duration"] ?>);
        
        // If WP Consent API is available, deny consent via the API
        if (wpcaUseConsentAPI && typeof wp_set_consent === 'function') {
          wp_set_consent('statistics', 'deny');
        }
        
        wpcaHideBanner();
      }

      function wpcaHasConsent() {
        // Check WP Consent API first if available
        if (wpcaUseConsentAPI && typeof wp_has_consent === 'function') {
          return wp_has_consent('statistics');
        }
        
        // Fallback to cookie check
        return wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "true";
      }

      // Listen to WP Consent API events if available
      if (wpcaUseConsentAPI) {
        document.addEventListener('wp_listen_for_consent_change', function(e) {
          var changedConsentCategory = e.detail;
          if (changedConsentCategory === 'statistics') {
            if (wp_has_consent('statistics')) {
              wpcaLoadAnalytics();
              document.body.classList.add("cookies-accepted");
              document.body.classList.remove("cookies-declined");
            } else {
              document.body.classList.add("cookies-declined");
              document.body.classList.remove("cookies-accepted");
            }
          }
        });
      }

      const EU_TIMEZONES = [
        'Europe/Vienna',
        'Europe/Brussels',
        'Europe/Sofia',
        'Europe/Zagreb',
        'Asia/Famagusta',
        'Asia/Nicosia',
        'Europe/Prague',
        'Europe/Copenhagen',
        'Europe/Tallinn',
        'Europe/Helsinki',
        'Europe/Paris',
        'Europe/Berlin',
        'Europe/Zurich',
        'Europe/Bern',
        'Europe/Busingen',
        'Europe/Athens',
        'Europe/Budapest',
        'Europe/Dublin',
        'Europe/Rome',
        'Europe/Riga',
        'Europe/Vilnius',
        'Europe/Luxembourg',
        'Europe/Malta',
        'Europe/Amsterdam',
        'Europe/Warsaw',
        'Atlantic/Azores',
        'Atlantic/Madeira',
        'Europe/Lisbon',
        'Europe/Bucharest',
        'Europe/Bratislava',
        'Europe/Ljubljana',
        'Africa/Ceuta',
        'Atlantic/Canary',
        'Europe/Madrid',
        'Europe/Stockholm'
      ];

      // Determine if we should load analytics
      var shouldLoadAnalytics = false;
      
      if (wpcaUseConsentAPI && typeof wp_has_consent === 'function') {
        // Use WP Consent API if available
        shouldLoadAnalytics = wp_has_consent('statistics');
      } else {
        // Fallback to original logic
        shouldLoadAnalytics = (wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "true") || 
                             (!EU_TIMEZONES.includes(Intl.DateTimeFormat().resolvedOptions().timeZone)) || 
                             (/bot|crawler|spider|crawling/i.test(navigator.userAgent));
      }
      
      if (shouldLoadAnalytics) {
        wpcaLoadAnalytics();
        document.body.classList.add("cookies-accepted");
      }
      if (wpcaGetCookie("<?php echo self::COOKIE_NAME; ?>") == "false" || 
          (wpcaUseConsentAPI && typeof wp_has_consent === 'function' && !wp_has_consent('statistics'))) {
        document.body.classList.add("cookies-declined");
      }
    </script>
<?php
  }
}

$globalWpConditionalAnalytics = new WpConditionalAnalytics();

function wpca_enqueue_script($id, $url)
{
  global $globalWpConditionalAnalytics;
  $globalWpConditionalAnalytics->registerScript($id, $url);
}
