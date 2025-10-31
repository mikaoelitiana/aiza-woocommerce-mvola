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
        // The script is registered and translations loaded via the init hook in the main plugin file.
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
