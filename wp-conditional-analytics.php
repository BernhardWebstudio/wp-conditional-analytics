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

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define("WP_CONDITIONAL_ANALYTICS_PLUGIN_PATH", plugin_dir_path(__FILE__));
require_once __DIR__ . '/blocks/index.php';

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
	private $wp_consent_api_active = false;

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

		// Detect and integrate with WP Consent API
		add_action('plugins_loaded', array($this, 'init_wp_consent_api_integration'));
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
	 * Initialize WP Consent API integration
	 */
	function init_wp_consent_api_integration()
	{
		// Check if WP Consent API is available
		$this->wp_consent_api_active = function_exists('wp_has_consent');

		if ($this->wp_consent_api_active) {
			// Declare compliance with WP Consent API
			$plugin = plugin_basename(__FILE__);
			add_filter("wp_consent_api_registered_{$plugin}", '__return_true');

			// Register our cookies with WP Consent API
			$this->register_cookies_with_consent_api();
		} else {
			// Act as consent management plugin if no other is available
			$this->provide_consent_type_filter();
		}
	}

	/**
	 * Register cookies with WP Consent API
	 */
	function register_cookies_with_consent_api()
	{
		if (function_exists('wp_add_cookie_info')) {
			// Register our own cookie
			wp_add_cookie_info(
				self::COOKIE_NAME,
				'Conditional Analytics',
				'functional',
				__('Session', 'wp-conditional-analytics'),
				__('Stores user consent preferences', 'wp-conditional-analytics')
			);

			// Register Google Analytics if configured
			$settings = $this->wpsf->get_settings();
			if (!empty($settings['google_analytics_google_analytics_tag'])) {
				wp_add_cookie_info(
					'_ga',
					'Google Analytics',
					'statistics',
					__('2 years', 'wp-conditional-analytics'),
					__('Used to distinguish users', 'wp-conditional-analytics')
				);
			}
		}
	}

	/**
	 * Provide consent type filter when acting as consent management plugin
	 */
	function provide_consent_type_filter()
	{
		// Only provide consent type if no other consent plugin is doing it
		add_filter('wp_get_consent_type', array($this, 'set_consent_type'), 10, 1);
	}

	/**
	 * Set consent type based on user's region (EU = optin, others = optout)
	 */
	function set_consent_type($consent_type)
	{
		// If already set by another plugin, don't override
		if ($consent_type !== false) {
			return $consent_type;
		}

		// Set to 'optin' for EU regions, detected client-side
		// This will be overridden by JavaScript if needed
		return 'optin';
	}

	/**
	 * Map content types to WP Consent API categories
	 */
	function map_content_type_to_consent_category($content_type)
	{
		$mapping = array(
			'analytics' => 'statistics',
			'marketing' => 'marketing',
			'social-media' => 'marketing',
			'video' => 'marketing',
			'maps' => 'functional',
			'general' => 'functional',
			'custom' => 'preferences',
		);

		return isset($mapping[$content_type]) ? $mapping[$content_type] : 'functional';
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
		$ganalytics_tag = $settings["google_analytics_google_analytics_tag"];
		$showBanner = $settings["general_activate_banner"];

		// Check if we should show our banner (only if no other consent plugin is active)
		$show_our_banner = $showBanner && !$this->wp_consent_api_active;

		//////////////////////////////// The heart of things
		echo "<!-- Outputting wp-conditional-analytics stuff -->";

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

			.wp-conditional-analytics-banner-text {
				display: flex;
				justify-content: center;
				align-items: center;
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
		<?php if ($show_our_banner) : ?>
			<div class="wp-conditional-analytics-banner fixed full-width hidden" id="wpca_banner">
				<div class="flex row wp-conditional-analytics-banner-content">
					<div class="column wp-conditional-analytics-banner-text">
						<p>
							<?php _e('Can we use Analytics, please?', 'wp-conditional-analytics'); ?>
						</p>
					</div>
					<div class="column wp-conditional-analytics-banner-buttons button-group wp-element-button-group">
						<a class="button btn wp-element-button " href="<?php echo get_privacy_policy_url(); ?>"><?php _e('Privacy Policy', 'wp-conditional-analytics'); ?></a>
						<button type="button" class="button btn wp-element-button btn-primary btn-accept" onclick='wpcaAcceptCookies()'><?php _e('Allow', 'wp-conditional-analytics'); ?></button>
						<button type="button" class="button btn wp-element-button btn-secondary btn-cancel" onclick='wpcaSetCookie("<?php echo self::COOKIE_NAME; ?>", "false", <?php echo $settings["general_acceptance_decline_duration"] ?>); wpcaHideBanner()'><?php _e('Decline', 'wp-conditional-analytics'); ?></button>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<script type="text/javascript">
			// WP Consent API integration
			const wpConsentApiActive = <?php echo $this->wp_consent_api_active ? 'true' : 'false'; ?>;
			const wpcaShouldManageBanner = <?php echo $show_our_banner ? 'true' : 'false'; ?>;
			const wpcaCookieName = "<?php echo self::COOKIE_NAME; ?>";
			const wpcaStatisticsConsentDuration = <?php echo (int) $settings["general_acceptance_save_duration"]; ?>;
			const wpcaContentTypeConsentDuration = 365;

			const WPCA_CONTENT_TYPE_TO_CATEGORY = {
				'analytics': 'statistics',
				'marketing': 'marketing',
				'social-media': 'marketing',
				'video': 'marketing',
				'maps': 'functional',
				'general': 'functional',
				'custom': 'preferences'
			};

			window.wpcaAnalyticsAssetsLoaded = window.wpcaAnalyticsAssetsLoaded || false;
			window.wpcaActiveCampaignTrackingProcessed = window.wpcaActiveCampaignTrackingProcessed || false;

			// Helper function to check consent via WP Consent API or fallback to cookies
			function wpcaHasConsent(category) {
				if (wpConsentApiActive && typeof wp_has_consent === 'function') {
					return wp_has_consent(category);
				}
				// Fallback to our own cookie system
				return wpcaGetCookie(wpcaCookieName) === "true";
			}

			// Set consent via WP Consent API or fallback to cookies
			function wpcaSetConsent(category, value) {
				if (wpConsentApiActive && typeof wp_set_consent === 'function') {
					wp_set_consent(category, value);
				}
			}

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

			function wpcaSetCookie(cname, cvalue, exdays) {
				const d = new Date();
				d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
				let expires = "expires=" + d.toUTCString();
				document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
			}

			function wpcaHideBanner() {
				document.getElementById("wpca_banner")?.classList.add("hidden");
			}

			function wpcaShowBanner() {
				if (!wpcaShouldManageBanner) {
					return;
				}

				document.getElementById("wpca_banner")?.classList.remove("hidden");
			}

			function wpcaUpdateBannerVisibility() {
				if (!wpcaShouldManageBanner) {
					return;
				}

				const hasDecision = wpcaGetCookie(wpcaCookieName) !== null;
				if (hasDecision || wpcaHasConsent('statistics')) {
					wpcaHideBanner();
					return;
				}

				wpcaShowBanner();
			}

			function wpcaGetScriptElementId(id) {
				return id + "-js";
			}

			function wpcaLoadScript(id, url, attributes = null) {
				const scriptElementId = wpcaGetScriptElementId(id);
				if (document.getElementById(scriptElementId)) {
					return false;
				}

				var script = document.createElement("script");
				script.setAttribute("src", url);
				script.setAttribute("id", scriptElementId);
				script.async = true;
				if (attributes != null) {
					for (const [key, value] of Object.entries(attributes)) {
						script.setAttribute(key, value);
					}
				}
				document.head.appendChild(script);

				return true;
			}

			function wpcaConditionallyLoadScript(id, url, attributes = null) {
				if (wpcaHasConsent('statistics') && !document.getElementById(wpcaGetScriptElementId(id))) {
					wpcaLoadScript(id, url, attributes);
				}
			}

			function wpcaCookiesAreAllowed() {
				return wpcaHasConsent('statistics');
			}

			function wpcaContentTypeToConsentCategory(contentType) {
				return WPCA_CONTENT_TYPE_TO_CATEGORY[contentType] || 'functional';
			}

			function wpcaEnableActiveCampaignTrackingIfAvailable() {
				if (window.wpcaActiveCampaignTrackingProcessed) {
					return;
				}

				if (typeof vgo === "function") {
					vgo('process', 'allowTracking');
					window.wpcaActiveCampaignTrackingProcessed = true;
				}
			}

			function wpcaLoadAnalytics() {
				if (!window.wpcaAnalyticsAssetsLoaded) {
					<?php if ($ganalytics_tag) : ?>
						wpcaLoadScript("google-analytics", "https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ganalytics_tag); ?>");

						if (!document.getElementById("wpca-google-analytics-config")) {
							var addDataLayer = document.createElement("script");
							addDataLayer.setAttribute("id", "wpca-google-analytics-config");
							var dataLayerData = document.createTextNode("window.dataLayer = window.dataLayer || []; \nfunction gtag(){dataLayer.push(arguments);} \ngtag('js', new Date()); \ngtag('config', '<?php echo esc_js($ganalytics_tag); ?>');");
							addDataLayer.appendChild(dataLayerData);
							document.head.appendChild(addDataLayer);
						}

						<?php
						// load the adidtional scripts we may load
						foreach ($this->additionalScripts as $id => $url) {
							echo "wpcaLoadScript('" . esc_js($id) . "', '" . esc_url_raw($url) . "');";
						}
						?>
					<?php endif; ?>

					window.wpcaAnalyticsAssetsLoaded = true;
				}

				wpcaEnableActiveCampaignTrackingIfAvailable();
			}

			function wpcaAcceptCookies(reload = false) {
				if (wpConsentApiActive) {
					// Use WP Consent API to set consent for all categories
					wpcaSetConsent('statistics', 'allow');
					wpcaSetConsent('marketing', 'allow');
					wpcaSetConsent('preferences', 'allow');
				} else {
					// Fallback to our own cookie system
					wpcaSetCookie(wpcaCookieName, "true", wpcaStatisticsConsentDuration);
				}

				wpcaLoadAnalytics();
				wpcaHideBanner();

				if (reload) {
					location.reload();
				}

				// Dispatch an event that content type cookies were accepted
				const event = new CustomEvent("wpcaCookiesAccepted", {
					detail: {}
				});
				document.dispatchEvent(event);
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

			function wpcaShouldAutoloadAnalytics() {
				return !EU_TIMEZONES.includes(Intl.DateTimeFormat().resolvedOptions().timeZone) || (/bot|crawler|spider|crawling/i.test(navigator.userAgent));
			}

			function wpcaMaybeLoadAnalytics() {
				if (wpcaHasConsent('statistics') || wpcaShouldAutoloadAnalytics()) {
					wpcaLoadAnalytics();
					document.body.classList.add("cookies-accepted");
					return true;
				}

				return false;
			}

			if (!wpcaMaybeLoadAnalytics() && !wpConsentApiActive && wpcaGetCookie(wpcaCookieName) == "false") {
				document.body.classList.add("cookies-declined");
			}

			wpcaUpdateBannerVisibility();
			document.addEventListener('DOMContentLoaded', wpcaUpdateBannerVisibility);

			// Check if cookies are allowed for a specific content type
			function wpcaContentTypeIsAllowed(contentType) {
				if (!contentType) return wpcaCookiesAreAllowed();

				const consentCategory = wpcaContentTypeToConsentCategory(contentType);

				if (wpConsentApiActive) {
					return wpcaHasConsent(consentCategory);
				}

				// Check for cookies that store content type preferences
				const contentTypeCookie = wpcaGetCookie("wp_conditional_ana_" + contentType);
				if (contentTypeCookie === "accepted") {
					return true;
				}

				// Fall back to general cookie acceptance
				return wpcaCookiesAreAllowed();
			}

			// Accept cookies for a specific content type
			function wpcaAcceptContentType(contentType) {
				if (!contentType) {
					wpcaAcceptCookies();
					return;
				}

				const consentCategory = wpcaContentTypeToConsentCategory(contentType);

				if (wpConsentApiActive) {
					// Use WP Consent API
					wpcaSetConsent(consentCategory, 'allow');
				} else {
					// Set a cookie for this specific content type
					wpcaSetCookie("wp_conditional_ana_" + contentType, "accepted", wpcaContentTypeConsentDuration);
				}

				// Dispatch an event that content type cookies were accepted
				const event = new CustomEvent("wpcaContentTypeAccepted", {
					detail: {
						contentType: contentType
					}
				});
				document.dispatchEvent(event);

				// Refresh content that depends on this content type
				document.querySelectorAll(".wpca-external-content-wrapper[data-content-type='" + contentType + "']").forEach(function(wrapper) {
					const event = new CustomEvent("wpcaRefreshContent");
					wrapper.dispatchEvent(event);
				});
			}

			// Listen to WP Consent API events when available
			if (wpConsentApiActive) {
				document.addEventListener("wp_listen_for_consent_change", function(e) {
					var changedConsentCategory = e.detail;
					for (var key in changedConsentCategory) {
						if (changedConsentCategory.hasOwnProperty(key)) {
							if (key === 'statistics' && changedConsentCategory[key] === 'allow') {
								wpcaMaybeLoadAnalytics();
								wpcaUpdateBannerVisibility();
							}
						}
					}
				});
			}

			// After loading the page, ensure cache-delivered HTML is corrected client-side
			window.addEventListener('load', () => {
				wpcaUpdateBannerVisibility();
				wpcaMaybeLoadAnalytics();
			});
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
