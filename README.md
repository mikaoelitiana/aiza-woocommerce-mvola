# WooCommerce MVola Madagascar

MVola Madagascar payment gateway plugin for WooCommerce.

## Description

This plugin integrates MVola's mobile money API into your WooCommerce store, allowing customers to pay for their orders using MVola mobile money service.

## Features

- Easy integration with MVola API
- Support for both sandbox (testing) and production environments
- Automatic payment confirmation via callbacks
- Customer phone number collection at checkout
- Secure payment processing with OAuth 2.0
- Compatible with WooCommerce 5.0+
- WordPress 5.8+
- Support for WooCommerce Blocks checkout

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- SSL certificate (required for production)
- MVola merchant account

## Installation

### Via Composer (Recommended)

1. Add the plugin to your WordPress project:
   ```bash
   composer require mikaoelitiana/woocommerce-mvola
   ```

2. Activate the plugin through the 'Plugins' menu in WordPress

### Manual Installation

1. Download or clone this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/mikaoelitiana/aiza-woocommerce-mvola.git woocommerce-mvola
   ```

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Go to WooCommerce > Settings > Payments

4. Enable "MVola Madagascar" and click "Manage"

5. Configure your MVola API credentials:
   - **Consumer Key**: Your MVola consumer key
   - **Secret Key**: Your MVola secret key
   - **Merchant Phone Number**: Your MVola merchant account phone number

## Configuration

### Getting API Credentials

1. Register for a MVola merchant account at [MVola Developer Portal](https://www.mvola.mg/devportal)
2. Create a new application
3. Obtain your credentials:
   - Consumer Key
   - Secret Key
   - Merchant Phone Number (your MVola account number that will receive payments)

### Plugin Settings

Navigate to **WooCommerce > Settings > Payments > MVola Madagascar**

#### Basic Settings

- **Enable/Disable**: Enable or disable the payment method
- **Title**: Payment method title displayed to customers (default: "MVola")
- **Description**: Payment method description shown on checkout page

#### API Settings

- **Sandbox Mode**: Enable to use the sandbox environment for testing
- **Consumer Key**: Enter your MVola consumer key
- **Secret Key**: Enter your MVola secret key
- **Merchant Phone Number**: Enter your MVola merchant account phone number

#### Callback URL

The plugin provides a custom callback URL field for local development:
- **Default**: `https://yoursite.com/?wc-api=wc_gateway_mvola`
- **Custom**: Use ngrok or similar service for local testing

## Testing

### Sandbox Mode

When Sandbox Mode is enabled:
- The plugin uses the sandbox API endpoint: `https://devapi.mvola.mg`
- No real money is charged
- You can test the full payment flow

For more information about testing, visit: [MVola Developer Portal](https://www.mvola.mg/devportal)

### Test Credentials

Refer to the MVola developer documentation for test account credentials and phone numbers.

## How It Works

1. Customer selects MVola as payment method at checkout
2. Customer enters their MVola phone number
3. Order is placed and MVola transaction is initiated
4. Customer receives a push notification on their phone to confirm payment
5. Customer confirms payment in their MVola app
6. MVola sends a callback notification to your site
7. Plugin checks transaction status and confirms the payment
8. Order status is updated automatically

## Payment Flow

```
[Customer] → [WooCommerce] → [MVola API]
                ↓                  ↓
         [Order Pending]    [Push Notification]
                ↓                  ↓
         [Callback URL] ← [Customer Confirms]
                ↓
         [Status Check]
                ↓
      [Payment Complete]
```

## Callback Configuration

The plugin automatically handles callback notifications at:
```
https://yoursite.com/?wc-api=wc_gateway_mvola&reference={transaction_reference}
```

Make sure this URL is accessible and not blocked by security plugins or server configurations.

### Local Development

For local development, you need to make your callback URL publicly accessible:

1. Install and run ngrok:
   ```bash
   ngrok http 80
   ```

2. Copy the ngrok HTTPS URL

3. In plugin settings, enter the custom callback URL:
   ```
   https://your-ngrok-url.ngrok.io/?wc-api=wc_gateway_mvola
   ```

## Currency Support

- **Supported Currency**: MGA (Malagasy Ariary)
- The plugin uses "Ar" as the currency code for MVola API

## Database

The plugin creates a custom table `wp_wc_mvola_transactions` to track payments:

- `id`: Transaction record ID
- `order_id`: WooCommerce order ID
- `server_correlation_id`: MVola transaction ID
- `reference`: Unique transaction reference
- `user_account_identifier`: Customer phone number
- `status`: Payment status (pending, completed, failed)
- `created_at`: Record creation timestamp
- `updated_at`: Record update timestamp

## Troubleshooting

### Payment not completing

1. Check that callbacks are not blocked by security plugins
2. Verify your API credentials are correct
3. Ensure SSL is enabled (required for production)
4. Check WooCommerce > Status > Logs for error messages
5. Verify the callback URL is publicly accessible

### Access Token Issues

The plugin caches access tokens. If you experience authentication issues:
1. Check your Consumer Key and Secret Key are correct
2. Verify your credentials are for the correct environment (sandbox/production)
3. Clear WordPress transients

### Customer Not Receiving Push Notification

1. Verify the customer's phone number is correct
2. Check that the phone number starts with 034 (MVola numbers)
3. Ensure the customer has an active MVola account
4. Check MVola service status

### Order Status Not Updating

1. Verify callback URL is accessible
2. Check database table `wp_wc_mvola_transactions` exists
3. Review error logs for callback errors
4. Test callback URL manually with a PUT request

## Security

- All API credentials are stored securely in WordPress options
- OAuth tokens are cached and automatically refreshed
- Payment callbacks are validated using unique references
- Sensitive data is not logged in production
- HTTPS is required for production environment

## API Endpoints Used

### Token Generation
```
POST {api_base}/token
```

### Transaction Initiation
```
POST {api_base}/mvola/mm/transactions/type/merchantpay/1.0.0/
```

### Transaction Status
```
GET {api_base}/mvola/mm/transactions/type/merchantpay/1.0.0/status/{correlationId}
```

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/mikaoelitiana/aiza-woocommerce-mvola/issues
- MVola API Documentation: https://www.mvola.mg/devportal

## Development

Based on:
- Pretix MVola plugin: https://github.com/mikaoelitiana/pretix-mvola
- WooCommerce Orange Money plugin: https://github.com/mikaoelitiana/aiza-woocommerce-orange-money

## License

Copyright 2024 Mika Andrianarijaona

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

## Changelog

### 1.0.0
- Initial release
- MVola API integration with OAuth 2.0
- Support for sandbox and production environments
- Customer phone number collection
- Push notification payment flow
- Callback handling for payment confirmation
- WooCommerce Blocks support
- WooCommerce 5.0+ compatibility
