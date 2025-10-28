<?php

namespace BernhardWebstudio\WPConditionalAnalytics;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Registers all block folders found in the `blocks` directory.
 *
 * @return void
 */
function register_blocks()
{
	$block_folders = glob(WP_CONDITIONAL_ANALYTICS_PLUGIN_PATH . '/blocks/*', GLOB_ONLYDIR);
	foreach ($block_folders as $block_folder) {
		register_block_type($block_folder);
	}
}

add_action('init', __NAMESPACE__ . '\register_blocks');
