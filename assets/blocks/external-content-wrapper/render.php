<?php

/**
 * Render the External Content Wrapper block
 *
 * @package WP_Conditional_Analytics
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

$wrapper_attributes = get_block_wrapper_attributes();
$blocking_message = isset($attributes['blockingMessage']) ? $attributes['blockingMessage'] : 'This content is currently blocked to protect your privacy.';
$button_text = isset($attributes['buttonText']) ? $attributes['buttonText'] : 'Accept cookies and show content';
$content_identifier = isset($attributes['contentIdentifier']) ? $attributes['contentIdentifier'] : '';

// If the content identifier is "other" and a custom identifier is set, use that instead
if ($content_identifier === 'other' && !empty($attributes['customIdentifier'])) {
	$content_identifier = sanitize_key($attributes['customIdentifier']);
}

$wrapper_id = 'wpca-wrapper-' . uniqid();

// Store the inner content in a variable but don't output it directly
$inner_content = $content;
?>

<div <?php echo $wrapper_attributes; ?>>
	<div id="<?php echo esc_attr($wrapper_id); ?>" class="wpca-external-content-wrapper" data-content-type="<?php echo esc_attr($content_identifier); ?>">
		<!-- Content placeholder that will be populated only when cookies are accepted -->
		<div class="wrapped-content" data-content="<?php echo esc_attr(base64_encode($inner_content)); ?>">
			<!-- Content will be inserted here via JavaScript only when cookies are accepted -->
		</div>

		<div class="content-blocking-overlay">
			<p><?php echo esc_html($blocking_message); ?></p>
			<button type="button" class="accept-cookies-btn btn button btn-primary wp-element-button" data-content-type="<?php echo esc_attr($content_identifier); ?>"><?php echo esc_html($button_text); ?></button>
			<button type="button" class="accept-cookies-btn btn button btn-secondary wp-element-button"><?php echo __("Accept all cookies", 'wp-conditional-analytics') ?></button>
		</div>
	</div>

	<script type="text/javascript">
		(function() {
			function initializeWrapper() {
				const wrapper = document.getElementById('<?php echo esc_js($wrapper_id); ?>');
				if (!wrapper) return;

				const contentContainer = wrapper.querySelector('.wrapped-content');
				const overlay = wrapper.querySelector('.content-blocking-overlay');
				const encodedContent = contentContainer.getAttribute('data-content');
				const contentType = wrapper.getAttribute('data-content-type');

				function updateVisibility() {
					let cookiesAllowed = false;

					// Check if cookies are allowed for this specific content type
					if (typeof wpcaContentTypeIsAllowed === 'function' && contentType) {
						cookiesAllowed = wpcaContentTypeIsAllowed(contentType);
					}
					// Fallback to general cookie acceptance check
					if (typeof wpcaCookiesAreAllowed === 'function' && !cookiesAllowed) {
						cookiesAllowed = wpcaCookiesAreAllowed();
					}

					if (cookiesAllowed) {
						// Only decode and insert the content when cookies are allowed
						try {
							const decodedContent = atob(encodedContent);
							contentContainer.innerHTML = decodedContent;

							// Execute any scripts that were part of the content
							const scripts = contentContainer.querySelectorAll('script');
							scripts.forEach(oldScript => {
								const newScript = document.createElement('script');
								Array.from(oldScript.attributes).forEach(attr => {
									newScript.setAttribute(attr.name, attr.value);
								});
								newScript.appendChild(document.createTextNode(oldScript.innerHTML));
								oldScript.parentNode.replaceChild(newScript, oldScript);
							});
						} catch (e) {
							console.error('Error decoding content:', e);
						}
						contentContainer.style.display = 'block';
						overlay.style.display = 'none';
					} else {
						contentContainer.style.display = 'none';
						overlay.style.display = 'block';
					}
				}

				// Initial check
				updateVisibility();

				// Set up a MutationObserver to watch for body class changes
				const observer = new MutationObserver(function(mutations) {
					mutations.forEach(function(mutation) {
						if (mutation.attributeName === 'class') {
							updateVisibility();
						}
					});
				});

				observer.observe(document.body, {
					attributes: true
				});

				// Listen for WP Consent API events
				document.addEventListener("wp_listen_for_consent_change", function (e) {
					updateVisibility();
				});

				// Listen for custom events from this plugin
				document.addEventListener("wpcaCookiesAccepted", function() {
					updateVisibility();
				});

				document.addEventListener("wpcaContentTypeAccepted", function(e) {
					if (e.detail && e.detail.contentType === contentType) {
						updateVisibility();
					}
				});

				// Handle the accept button clicks
				const acceptButtons = wrapper.querySelectorAll('.accept-cookies-btn');
				acceptButtons.forEach(function(button) {
					button.addEventListener('click', function() {
						const buttonContentType = this.getAttribute('data-content-type');

						if (buttonContentType && typeof wpcaAcceptContentType === 'function') {
							// Accept cookies for this specific content type
							wpcaAcceptContentType(buttonContentType);
						} else if (typeof wpcaAcceptCookies === 'function') {
							// Fallback to accepting all cookies
							wpcaAcceptCookies();
						}

						updateVisibility();
					});
				});
			}

			// Initialize when DOM is ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initializeWrapper);
			} else {
				initializeWrapper();
			}
		})();
	</script>
</div>
