<?php

/**
 * Define your settings
 *
 * The first parameter of this filter should be wpsf_register_settings_[options_group],
 * in this case "bewe_wp_conditional_analytics".
 *
 * Your "options_group" is the second param you use when running new WordPressSettingsFramework()
 * from your init function. It's important as it differentiates your options from others.
 *
 * To use the tabbed example, simply change the second param in the filter below to 'wpsf_tabbed_settings'
 * and check out the tabbed settings function on line 156.
 */

add_filter('wpsf_register_settings_bewe_wp_conditional_analytics', 'bewe_wp_conditional_analytics_settings');

/**
 * Tabless example.
 *
 * @param array $wpsf_settings Settings.
 */
function bewe_wp_conditional_analytics_settings($wpsf_settings)
{

  $wpsf_settings[] = array(
    'section_id' => 'general',
    'section_title' => __('General Settings', 'wp-conditional-analytics'),
    'section_order' => 1,
    'fields' => [
      [
        'id' => 'activate_banner',
        'title' => __('Activate Banner', 'wp-conditional-analytics'),
        'type' => 'checkbox',
        'default' => true
      ],
      [
        'id' => 'acceptance_save_duration',
        'title' => __('Store accept state for [days]', 'wp-conditional-analytics'),
        'type' => 'number',
        'default' => 1825
      ],
      [
        'id' => 'acceptance_decline_duration',
        'title' => __('Store decline state for [days]', 'wp-conditional-analytics'),
        'type' => 'number',
        'default' => 30
      ],
    ]
  );

  $wpsf_settings[] = array(
    'section_id' => 'google_analytics',
    'section_title' => __('Google Analytics', 'wp-conditional-analytics'),
    'section_order' => 2,
    'fields' => [
      [
        'id' => 'google_analytics_tag',
        'title' => __('Google Analytics Tag', 'wp-conditional-analytics'),
        'type' => 'text',
        'default' => null
      ]
    ]
  );

  // $wpsf_settings[] = array(
  //   'section_id' => 'google_tagmanager',
  //   'section_title' => __('Google Tag Manager', 'wp-conditional-analytics'),
  //   'section_order' => 3,
  //   'fields' => [
  //     [
  //       'id' => 'google_tag_manager_tag',
  //       'title' => __('Google Tag Manager Tag', 'wp-conditional-analytics'),
  //       'type' => 'text',
  //       'default' => null
  //     ]
  //   ]
  // );

  return $wpsf_settings;
}
