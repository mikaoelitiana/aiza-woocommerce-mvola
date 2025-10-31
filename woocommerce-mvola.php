<?php
/**
 * Plugin Name: WooCommerce MVola Madagascar
 * Plugin URI: https://github.com/mikaoelitiana/aiza-woocommerce-mvola
 * Description: MVola Madagascar payment gateway for WooCommerce
 * Version: 1.0.0
 * Author: Mika Andrianarijaona
 * Author URI: https://github.com/mikaoelitiana
 * License: Apache License 2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
 * Text Domain: woocommerce-mvola
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WC_MVOLA_VERSION', '1.0.0');
define('WC_MVOLA_PLUGIN_FILE', __FILE__);
define('WC_MVOLA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_MVOLA_PLUGIN_URL', plugin_dir_url(__FILE__));

function wc_mvola_add_gateway($gateways) {
    $gateways[] = 'WC_Gateway_MVola';
    return $gateways;
}

function wc_mvola_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once WC_MVOLA_PLUGIN_PATH . 'includes/class-wc-gateway-mvola.php';

    add_filter('woocommerce_payment_gateways', 'wc_mvola_add_gateway');
}
add_action('plugins_loaded', 'wc_mvola_init', 11);

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

add_action('init', function() {
    wp_register_script(
        'wc-mvola-blocks',
        WC_MVOLA_PLUGIN_URL . 'assets/js/blocks-checkout.js',
        ['wc-blocks-registry', 'wp-element', 'wp-i18n', 'wp-html-entities'],
        WC_MVOLA_VERSION,
        true
    );
    wp_set_script_translations(
        'wc-mvola-blocks',
        'woocommerce-mvola',
        WC_MVOLA_PLUGIN_PATH . 'languages'
    );
});

add_action('woocommerce_blocks_loaded', function() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once WC_MVOLA_PLUGIN_PATH . 'includes/class-wc-mvola-blocks-support.php';

    add_action('woocommerce_blocks_payment_method_type_registration', function($payment_method_registry) {
        $payment_method_registry->register(new WC_MVola_Blocks_Support());
    });
});

function wc_mvola_plugin_links($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=mvola');
    $plugin_links = array(
        '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'woocommerce-mvola') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_mvola_plugin_links');

function wc_mvola_activate() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('This plugin requires WooCommerce to be installed and active.', 'woocommerce-mvola'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_mvola_transactions';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        server_correlation_id varchar(190) NOT NULL,
        reference varchar(190) NOT NULL,
        user_account_identifier varchar(20),
        status varchar(50),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY server_correlation_id (server_correlation_id),
        UNIQUE KEY reference (reference),
        KEY order_id (order_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wc_mvola_activate');
