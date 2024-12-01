<?php
/**
 * Social Chat Icon - Social Messaging Integration for WordPress
 *
 * This plugin adds a floating chat icon to your WordPress site,
 * allowing visitors to easily contact you through messaging.
 *
 * @link              https://wordpress.org/plugins/social-chat-icon/
 * @since             1.0.0
 * @package           Social_Chat_Icon
 *
 * @wordpress-plugin
 * Plugin Name: Social Chat Icon
 * Description: Add a floating chat icon to your WordPress site for easy communication.
 * Version: 2.2
 * Author: meme001
 * Author URI: https://profiles.wordpress.org/meme001/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: social-chat-icon
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('SCI_VERSION', '2.2');

/**
 * Plugin directory path and URL.
 */
define('SCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'sci_enqueue_frontend_assets');
add_action('admin_enqueue_scripts', 'sci_enqueue_admin_assets');

/**
 * Enqueue frontend assets.
 *
 * @since 2.2
 * @return void
 */
function sci_enqueue_frontend_assets() {
    wp_enqueue_style(
        'sci-frontend-style',
        SCI_PLUGIN_URL . 'css/frontend.css',
        array(),
        SCI_VERSION
    );

    // Add custom CSS
    $css = "
        .sci-chat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #128C7E;
            border-radius: 50%;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.4);
            z-index: 99999;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .sci-chat-icon:hover {
            transform: scale(1.1);
            box-shadow: 2px 2px 8px rgba(0,0,0,0.6);
        }
        .sci-chat-icon svg {
            width: 100%;
            height: 100%;
            fill: #ffffff;
        }
    ";

    wp_add_inline_style('sci-frontend-style', $css);
}

/**
 * Enqueue admin assets.
 *
 * @since 2.2
 * @param string $hook The current admin page.
 * @return void
 */
function sci_enqueue_admin_assets($hook) {
    if ('settings_page_sci_settings' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'sci-admin-style',
        SCI_PLUGIN_URL . 'css/admin.css',
        array(),
        SCI_VERSION
    );

    wp_enqueue_script(
        'sci-admin-script',
        SCI_PLUGIN_URL . 'js/admin.js',
        array('jquery'),
        SCI_VERSION,
        true
    );
}

/**
 * Add plugin settings menu.
 *
 * @since 2.2
 * @return void
 */
add_action('admin_menu', 'sci_add_admin_menu');
function sci_add_admin_menu() {
    add_options_page(
        __('WhatsApp Settings', 'social-chat-icon'),
        __('Social Chat Icon', 'social-chat-icon'),
        'manage_options',
        'sci_settings',
        'sci_settings_page'
    );
}

/**
 * Register plugin settings.
 *
 * @since 2.2
 * @return void
 */
add_action('admin_init', 'sci_register_settings');
function sci_register_settings() {
    // Register settings section
    add_settings_section(
        'sci_settings_section',
        __('Chat Settings', 'social-chat-icon'),
        'sci_section_description',
        'sci_settings'
    );

    // Phone Number
    register_setting('sci_settings', 'sci_phone_number', array(
        'type' => 'string',
        'sanitize_callback' => 'sci_sanitize_phone',
        'default' => ''
    ));

    add_settings_field(
        'sci_phone_number',
        __('Phone Number', 'social-chat-icon'),
        'sci_number_render',
        'sci_settings',
        'sci_settings_section'
    );

    // Icon Position
    register_setting('sci_settings', 'sci_icon_position', array(
        'type' => 'string',
        'sanitize_callback' => 'sci_sanitize_position',
        'default' => 'right'
    ));

    add_settings_field(
        'sci_icon_position',
        __('Icon Position', 'social-chat-icon'),
        'sci_position_render',
        'sci_settings',
        'sci_settings_section'
    );

    // Icon Size
    register_setting('sci_settings', 'sci_icon_size', array(
        'type' => 'integer',
        'sanitize_callback' => 'sci_sanitize_size',
        'default' => 50
    ));

    add_settings_field(
        'sci_icon_size',
        __('Icon Size (px)', 'social-chat-icon'),
        'sci_size_render',
        'sci_settings',
        'sci_settings_section'
    );

    // Default Message
    register_setting('sci_settings', 'sci_default_message', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default' => __('Hello!', 'social-chat-icon')
    ));

    add_settings_field(
        'sci_default_message',
        __('Default Message', 'social-chat-icon'),
        'sci_default_message_render',
        'sci_settings',
        'sci_settings_section'
    );

    // Display on all pages
    register_setting('sci_settings', 'sci_display_all_pages', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ));

    add_settings_field(
        'sci_display_all_pages',
        __('Display on All Pages', 'social-chat-icon'),
        'sci_display_all_pages_render',
        'sci_settings',
        'sci_settings_section'
    );
}

/**
 * Sanitization callbacks
 */

/**
 * Sanitize phone number.
 *
 * @since 2.2
 * @param string $phone Phone number.
 * @return string Sanitized phone number.
 */
function sci_sanitize_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) {
        add_settings_error(
            'sci_phone_number',
            'invalid_phone',
            __('Please enter a valid phone number with country code (numbers only).', 'social-chat-icon')
        );
        return get_option('sci_phone_number');
    }
    return $phone;
}

/**
 * Sanitize icon position.
 *
 * @since 2.2
 * @param string $position Icon position.
 * @return string Sanitized icon position.
 */
function sci_sanitize_position($position) {
    $valid_positions = array('left', 'right');
    if (!in_array($position, $valid_positions)) {
        return 'right';
    }
    return $position;
}

/**
 * Sanitize icon size.
 *
 * @since 2.2
 * @param int $size Icon size.
 * @return int Sanitized icon size.
 */
function sci_sanitize_size($size) {
    $size = absint($size);
    if ($size < 20 || $size > 100) {
        return 50;
    }
    return $size;
}

/**
 * Settings page
 */

/**
 * Display settings page.
 *
 * @since 2.2
 * @return void
 */
function sci_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if data has been posted
    if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD']) {
        // Verify nonce
        if (!isset($_POST['sci_settings_nonce']) || 
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['sci_settings_nonce'])), 
                'sci_save_settings'
            )
        ) {
            wp_die(esc_html__('Invalid nonce specified', 'social-chat-icon'));
        }

        // Save settings
        if (isset($_POST['sci_phone_number'])) {
            update_option(
                'sci_phone_number', 
                sanitize_text_field(wp_unslash($_POST['sci_phone_number']))
            );
        }
        if (isset($_POST['sci_default_message'])) {
            update_option(
                'sci_default_message', 
                sanitize_textarea_field(wp_unslash($_POST['sci_default_message']))
            );
        }
        if (isset($_POST['sci_icon_position'])) {
            update_option(
                'sci_icon_position', 
                sanitize_text_field(wp_unslash($_POST['sci_icon_position']))
            );
        }
        if (isset($_POST['sci_icon_size'])) {
            update_option(
                'sci_icon_size', 
                absint(wp_unslash($_POST['sci_icon_size']))
            );
        }
        if (isset($_POST['sci_display_all_pages'])) {
            update_option(
                'sci_display_all_pages', 
                sanitize_text_field(wp_unslash($_POST['sci_display_all_pages']))
            );
        }

        add_settings_error(
            'sci_messages', 
            'sci_message', 
            esc_html__('Settings Saved', 'social-chat-icon'), 
            'updated'
        );
    }

    // Show settings form
    ?>
    <div class="wrap sci-settings-page">
        <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
        <?php settings_errors('sci_messages'); ?>

        <form action="" method="post">
            <?php wp_nonce_field('sci_save_settings', 'sci_settings_nonce'); ?>
            <?php settings_fields('sci_settings'); ?>
            <?php do_settings_sections('sci_settings'); ?>
            <?php submit_button(__('Save Settings', 'social-chat-icon')); ?>
        </form>
    </div>
    <?php
}

/**
 * Settings fields renderers
 */

/**
 * Display settings section description.
 *
 * @since 2.2
 * @return void
 */
function sci_section_description() {
    ?>
    <p class="description">
        <?php esc_html_e('Configure your chat icon settings below. Make sure to enter a valid phone number with country code.', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Display phone number field.
 *
 * @since 2.2
 * @return void
 */
function sci_number_render() {
    $value = get_option('sci_phone_number');
    ?>
    <input 
        type="text" 
        name="sci_phone_number" 
        value="<?php echo esc_attr($value); ?>"
        placeholder="<?php echo esc_attr__('Example: 14155552671', 'social-chat-icon'); ?>"
        class="regular-text"
    />
    <p class="description">
        <?php echo esc_html__('Enter your full phone number including country code without spaces or special characters.', 'social-chat-icon'); ?>
        <br>
        <?php echo esc_html__('Example: 14155552671 for +1 415 555 2671', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Display icon position field.
 *
 * @since 2.2
 * @return void
 */
function sci_position_render() {
    $value = get_option('sci_icon_position', 'right');
    ?>
    <select name="sci_icon_position">
        <option value="right" <?php selected($value, 'right'); ?>>
            <?php esc_html_e('Right', 'social-chat-icon'); ?>
        </option>
        <option value="left" <?php selected($value, 'left'); ?>>
            <?php esc_html_e('Left', 'social-chat-icon'); ?>
        </option>
    </select>
    <p class="description">
        <?php esc_html_e('Choose which side of the screen to display the chat icon.', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Display icon size field.
 *
 * @since 2.2
 * @return void
 */
function sci_size_render() {
    $value = get_option('sci_icon_size', 50);
    ?>
    <input 
        type="number" 
        name="sci_icon_size" 
        value="<?php echo esc_attr($value); ?>"
        min="20"
        max="100"
        step="1"
        class="small-text"
    />
    <p class="description">
        <?php esc_html_e('Enter icon size in pixels (between 20 and 100).', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Display default message field.
 *
 * @since 2.2
 * @return void
 */
function sci_default_message_render() {
    $value = get_option('sci_default_message', '');
    ?>
    <textarea 
        name="sci_default_message" 
        class="large-text" 
        rows="3"
        placeholder="<?php echo esc_attr__('Hello! I have a question about...', 'social-chat-icon'); ?>"
    ><?php echo esc_textarea($value); ?></textarea>
    <p class="description">
        <?php esc_html_e('Default message that will be pre-filled in chat. Leave empty for no pre-filled message.', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Display display on all pages field.
 *
 * @since 2.2
 * @return void
 */
function sci_display_all_pages_render() {
    $value = get_option('sci_display_all_pages', true);
    ?>
    <label>
        <input 
            type="checkbox" 
            name="sci_display_all_pages" 
            value="1" 
            <?php checked($value, true); ?>
        />
        <?php esc_html_e('Show the chat icon on all pages', 'social-chat-icon'); ?>
    </label>
    <p class="description">
        <?php esc_html_e('If unchecked, the icon will only appear on the homepage.', 'social-chat-icon'); ?>
    </p>
    <?php
}

/**
 * Frontend display
 */

/**
 * Add chat icon to frontend.
 *
 * @since 2.2
 * @return void
 */
add_action('wp_footer', 'sci_add_chat_icon');
function sci_add_chat_icon() {
    // Check if we should display the icon
    if (!get_option('sci_display_all_pages', true) && !is_front_page()) {
        return;
    }

    $phone_number = get_option('sci_phone_number');
    if (empty($phone_number)) {
        return;
    }

    $position = get_option('sci_icon_position', 'right');
    $size = get_option('sci_icon_size', 50);
    $message = get_option('sci_default_message', '');

    // Build the URL
    $url = sprintf(
        'https://wa.me/%s?text=%s',
        urlencode($phone_number),
        urlencode($message)
    );

    // Build the icon HTML with WhatsApp SVG icon
    printf(
        '<a href="%s" target="_blank" rel="noopener noreferrer" class="sci-chat-icon" style="position:fixed;%s:20px;bottom:20px;width:%dpx;height:%dpx;" title="%s">%s</a>',
        esc_url($url),
        esc_attr($position),
        (int)$size,
        (int)$size,
        esc_attr__('Chat with us on WhatsApp', 'social-chat-icon'),
        '<svg viewBox="0 0 24 24" width="100%" height="100%">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>'
    );
}

/**
 * Plugin activation hook.
 *
 * @since 2.2
 * @return void
 */
register_activation_hook(__FILE__, 'sci_activate');
function sci_activate() {
    // Add default options
    add_option('sci_phone_number', '');
    add_option('sci_icon_position', 'right');
    add_option('sci_icon_size', 50);
    add_option('sci_default_message', '');
    add_option('sci_display_all_pages', true);

    // Clear any existing rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 *
 * @since 2.2
 * @return void
 */
register_deactivation_hook(__FILE__, 'sci_deactivate');
function sci_deactivate() {
    // Clear any rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin uninstall hook.
 * 
 * @since 2.2
 * @return void
 */
register_uninstall_hook(__FILE__, 'sci_uninstall');
function sci_uninstall() {
    // Remove all plugin options
    delete_option('sci_phone_number');
    delete_option('sci_icon_position');
    delete_option('sci_icon_size');
    delete_option('sci_default_message');
    delete_option('sci_display_all_pages');
}

/**
 * Add plugin action links.
 *
 * @since 2.2
 * @param array $links Array of plugin action links.
 * @return array Modified array of plugin action links.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sci_plugin_action_links');
function sci_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('options-general.php?page=sci_settings'),
        __('Settings', 'social-chat-icon')
    );
    array_unshift($links, $settings_link);
    return $links;
}
