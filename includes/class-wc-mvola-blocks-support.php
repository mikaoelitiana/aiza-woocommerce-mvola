<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_MVola_Blocks_Support extends AbstractPaymentMethodType {
    
    protected $name = 'mvola';

    public function initialize() {
        $this->settings = get_option('woocommerce_mvola_settings', []);
    }

    public function is_active() {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-mvola-blocks',
            WC_MVOLA_PLUGIN_URL . 'assets/js/blocks-checkout.js',
            ['wc-blocks-registry', 'wp-element', 'wp-i18n', 'wp-html-entities'],
            WC_MVOLA_VERSION,
            true
        );

        return ['wc-mvola-blocks'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => ['products'],
        ];
    }
}
