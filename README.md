# Security Bank WebCollect for WooCommerce

## Description

The **Security Bank WebCollect for WooCommerce** plugin allows you to accept payments via Visa, Mastercard, and popular E-Wallets like GCash and PayMaya through Security Bank WebCollect. This plugin integrates seamlessly with WooCommerce, providing a secure and efficient payment solution for your online store.

## Features

- Accept payments via Credit/Debit Cards, GCash, PayMaya, and more.
- Easy integration with WooCommerce.
- Test mode for safe development and testing.
- Webhook support for real-time payment updates.

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.0 or higher

## Installation

1. **Download the Plugin:**
   - Download the latest version of the plugin from the repository.

2. **Upload the Plugin:**
   - Go to your WordPress admin panel.
   - Navigate to **Plugins > Add New > Upload Plugin**.
   - Upload the downloaded ZIP file and click **Install Now**.

3. **Activate the Plugin:**
   - Once installed, click on **Activate** to enable the plugin.

4. **Configure the Plugin:**
   - Go to **WooCommerce > Settings > Payments**.
   - Find **Security Bank WebCollect** in the list and click on it to configure the settings.

## Configuration

1. **Enable the Plugin:**
   - Check the **Enable Security Bank WebCollect** option.

2. **Set Title and Description:**
   - Customize the title and description that will be displayed during checkout.

3. **Test Mode:**
   - Enable **Test Mode** to use test API keys for development.

4. **API Keys:**
   - Enter your **Test Publishable Key**, **Test Secret Key**, **Live Publishable Key**, and **Live Secret Key**.
   - These keys can be obtained from your Security Bank account.

5. **Select Payment Methods:**
   - Choose which payment methods to enable (e.g., Credit/Debit Cards, GCash, PayMaya).

6. **Webhook URL:**
   - Set the webhook URL in your Security Bank WebCollect dashboard to receive payment notifications.

## Usage

- Once configured, customers will see the Security Bank WebCollect payment option during checkout.
- Payments will be processed securely through the Security Bank API.

## Troubleshooting

- If you encounter issues, ensure that:
  - Your API keys are correctly configured.
  - The plugin is compatible with your version of WordPress and WooCommerce.
  - Check the plugin logs for any error messages.

## Support

For support, please create an issue in the [GitHub repository](https://github.com/ramerian/security-bank-woocommerce/) or contact the author at [Ramer Ian Dela Pena](https://ramerian.me).

## Changelog

### Version 1.0.3
- Initial release of the Security Bank WebCollect for WooCommerce plugin.

## License

This plugin is licensed under the GPL2. See the [LICENSE](LICENSE) file for more details.
