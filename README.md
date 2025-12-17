# Region Manager for WooCommerce

A powerful WordPress plugin that enables multi-regional WooCommerce management with region-specific pricing, product availability, and order tracking.

## Description

Region Manager transforms your WooCommerce store into a multi-regional powerhouse. Create separate regions with country-specific URLs, manage product availability and pricing per region, and track orders with advanced filtering and a custom "In Transit" order status.

Perfect for international stores, multi-country operations, or businesses targeting specific geographic markets.

## Features

### 🌍 Region Management
- **Unlimited Regions** (Pro) / **2 Regions** (Free)
- Country assignment with URL slugs (e.g., `/pt/`, `/es/`, `/fr/`)
- Language code integration for translator plugins (WPML, Polylang, TranslatePress)
- Default country selection per region
- Active/Inactive status control

### 💰 Pricing & Availability
- Regional price overrides (regular and sale prices)
- Product availability control per region
- Bulk assign/remove products to/from regions
- Variable product support with variation-level pricing
- Automatic price filtering on frontend

### 🛒 Checkout & Orders
- Region detection from URL
- Cross-region purchase policies:
  - Allow all purchases
  - Add international shipping surcharge
  - Block purchases outside region
- Custom "In Transit" order status (🚚)
- Region tracking in order meta
- Cross-region order detection and flagging

### 📊 Dashboard & Analytics
- Overview cards (Total Regions, Orders Today, Revenue Today, Products)
- Trend indicators (% change vs yesterday)
- Region performance breakdown
- Recent orders by region
- Orders by status visualization

### 🔗 URL & SEO
- Region-based URL structure (`example.com/pt/product/item`)
- Automatic URL rewriting and permalink handling
- Hreflang tag generation for SEO
- WooCommerce URL localization (cart, checkout, shop pages)
- Region switcher shortcode and widget

### 🎨 Frontend Components
- Region switcher with 3 display styles:
  - Dropdown selector
  - Link list
  - Flag emoji buttons
- WordPress widget for sidebars
- Shortcode with customization options

### 🔧 Developer Features
- Action hooks for translator plugin integration
- Filter hooks for customization
- Comprehensive inline documentation
- Debug mode for troubleshooting
- WordPress Coding Standards compliant

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to WordPress admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Upload the `region-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Region Manager > Settings to configure

### Post-Installation
1. Go to Region Manager > Settings
2. Create your first region
3. Add countries to the region
4. Configure checkout settings
5. Visit Settings > Permalinks to flush rewrite rules

## Configuration

### Creating Regions

1. **Navigate to Settings**
   - Go to Region Manager > Settings > Regions tab

2. **Add New Region**
   - Click "Add New Region" button
   - Enter region name (e.g., "Western Europe", "North America")
   - Enter URL slug (e.g., "eu", "us", "pt")

3. **Add Countries**
   - Select countries from dropdown
   - Set URL slug for each country (used in URLs)
   - Set language code (e.g., "en", "pt", "es")
   - Mark one country as default

4. **Save Region**
   - Click "Save Region"
   - Region is now active and ready to use

### Configuring Checkout Settings

Navigate to Region Manager > Settings > Checkout Settings:

**Cross-Region Purchase Policy:**
- **Allow**: Customers can purchase from any region (default)
- **Charge Extra**: Add configurable surcharge for cross-region orders
- **Block**: Prevent checkout if shipping country doesn't match region

**Extra Charge Amount:** Set the international shipping surcharge (when using "Charge Extra")

**Error Message:** Customize the message shown when blocking cross-region purchases

### Assigning Products to Regions

**Individual Product:**
1. Go to Region Manager > Products
2. Click "Edit Regions" on any product
3. Check "Available in [Region Name]" for each region
4. Optionally set price overrides
5. For variable products, check "Apply to all variations"
6. Save changes

**Bulk Assignment:**
1. Go to Region Manager > Products
2. Select multiple products (checkboxes)
3. Choose "Assign to Region" from Bulk Actions
4. Select target region
5. Optionally set price overrides
6. Click Apply

### Managing Orders

Navigate to Region Manager > Orders:

**Filtering:**
- Filter by Region
- Filter by Status (Processing, In Transit, Completed, etc.)
- Filter by Date Range
- Search by Order # or Customer Name

**Quick Actions:**
- Mark Processing orders as "In Transit" (one-click)
- Mark In Transit orders as "Completed" (one-click)
- View detailed order information
- Export filtered orders to CSV

**Bulk Actions:**
- Mark multiple orders as In Transit
- Mark multiple orders as Completed
- Export selection to CSV

## Shortcodes

### Region Switcher

Display a region switcher anywhere on your site:

```php
[rm_region_switcher style="dropdown" show_flags="true"]
```

**Attributes:**

- `style` - Display style (default: "dropdown")
  - `dropdown` - Select dropdown
  - `list` - Unordered list with links
  - `flags` - Flag emoji buttons only

- `show_flags` - Show country flags (default: "true")
  - `true` - Display flags
  - `false` - Hide flags

**Examples:**

```php
// Dropdown with flags
[rm_region_switcher style="dropdown" show_flags="true"]

// Simple list without flags
[rm_region_switcher style="list" show_flags="false"]

// Flags only
[rm_region_switcher style="flags"]
```

## Widgets

### Region Switcher Widget

Add the Region Switcher to any widget area:

1. Go to Appearance > Widgets
2. Find "Region Switcher" widget
3. Drag to desired widget area
4. Configure:
   - Title
   - Display Style (Dropdown/List/Flags)
   - Show Flags (checkbox)
5. Save

## Developer Hooks

### Action Hooks

**Region Detection:**
```php
// Fired when a region is detected from URL
do_action( 'rm_region_detected', $region_id, $url_slug );

// Fired when a language code is detected
do_action( 'rm_language_code_detected', $language_code );
```

**Order Processing:**
```php
// When order status changes to In Transit
do_action( 'woocommerce_order_status_in-transit', $order_id, $order );
```

### Filter Hooks

**Region Modification:**
```php
// Modify the detected current region
apply_filters( 'rm_current_region', $region_id );

// Modify the detected language code
apply_filters( 'rm_current_language_code', $language_code );
```

**URL Modification:**
```php
// Modify localized URL
apply_filters( 'home_url', $url, $path );
```

### Example: Integrate with Translator Plugin

```php
// Automatically switch language when region is detected
add_action( 'rm_language_code_detected', function( $language_code ) {
    if ( function_exists( 'pll_current_language' ) ) {
        // Polylang integration
        PLL()->curlang = PLL()->model->get_language( $language_code );
    }
}, 10, 1 );
```

### Example: Custom Region Detection Logic

```php
// Override region detection based on custom logic
add_filter( 'rm_current_region', function( $region_id ) {
    // Use user meta to determine region
    if ( is_user_logged_in() ) {
        $user_region = get_user_meta( get_current_user_id(), 'preferred_region', true );
        if ( $user_region ) {
            return $user_region;
        }
    }
    return $region_id;
}, 20, 1 );
```

## Database Schema

### Tables Created

The plugin creates 4 custom database tables:

1. **wp_rm_regions** - Stores region information
2. **wp_rm_region_countries** - Maps countries to regions
3. **wp_rm_product_regions** - Product-region assignments with pricing
4. **wp_rm_region_settings** - Region-specific settings (future use)

### Order Meta Keys

- `_rm_region_id` - Region ID for the order
- `_rm_order_url_slug` - URL slug used during checkout
- `_rm_cross_region_order` - Flag for cross-region orders ("yes"/"")
- `_rm_cross_region_fee` - Applied cross-region fee amount

## URL Structure

### Default Structure
```
example.com/product/item/
example.com/shop/
example.com/cart/
```

### With Region Manager
```
example.com/pt/product/item/
example.com/es/shop/
example.com/fr/cart/
```

Region slug is automatically prepended to all URLs when a region is active. The base URL (without region) remains accessible as the default/fallback.

## Troubleshooting

### Rewrite Rules Not Working

1. Go to Settings > Permalinks
2. Click "Save Changes" (no modifications needed)
3. This flushes the rewrite rules
4. Test region URLs

### Prices Not Changing

1. Verify product is assigned to the region
2. Check that price override is set (if desired)
3. Clear WooCommerce cache
4. Test in incognito/private browsing mode

### Region Not Detected from URL

1. Ensure URL-based regions are enabled (Settings > Checkout Settings)
2. Check region has a valid URL slug
3. Verify region status is "Active"
4. Enable WP_DEBUG to see detection logs

### Session Issues

1. Ensure WooCommerce sessions are working
2. Check for session conflicts with other plugins
3. Clear browser cookies
4. Test with different browsers

## Frequently Asked Questions

**Q: Can I use this plugin without WooCommerce?**
A: No, Region Manager requires WooCommerce to be installed and active.

**Q: How many regions can I create in the free version?**
A: The free version supports up to 2 regions. Upgrade to Pro for unlimited regions.

**Q: Does this work with variable products?**
A: Yes! Regional pricing and availability work with variable products and their variations.

**Q: Can I set different currencies per region?**
A: Region Manager handles pricing overrides but not currency conversion. Use a multi-currency plugin alongside Region Manager.

**Q: Does this work with caching plugins?**
A: Yes, but you may need to exclude region URLs from page caching or use dynamic caching rules.

**Q: Can I customize the region switcher design?**
A: Yes! The switcher uses standard HTML and CSS classes that you can style in your theme.

## Changelog

### Version 1.0.0 (2025-01-XX)
**Initial Release**
- Region management with country assignments
- Regional product pricing and availability
- Custom "In Transit" order status
- Dashboard with regional analytics
- Products and Orders management pages
- WooCommerce integration for checkout and pricing
- URL rewriting for region-based URLs
- Hreflang tags for SEO
- Region switcher shortcode and widget
- Free tier: 2 regions, Pro tier: unlimited
- WordPress Coding Standards compliant

## Support

### Free Support
- GitHub Issues: [Report bugs and request features](https://github.com/yourusername/region-manager/issues)
- Documentation: [Online documentation](https://example.com/docs)
- Community Forum: [WordPress.org forums](https://wordpress.org/support/plugin/region-manager)

### Premium Support
- Email support with priority response
- Direct access to developers
- Custom integration assistance
- [Upgrade to Pro](https://example.com/region-manager-pro)

## License

This plugin is licensed under the GPL v2 or later.

```
Region Manager
Copyright (C) 2025 Region Manager Team

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

Developed by the Region Manager Team
Icons by [Dashicons](https://developer.wordpress.org/resource/dashicons/)
Built with ❤️ for the WordPress and WooCommerce communities

---

**[Get Region Manager Pro](https://example.com/region-manager-pro)** | **[Documentation](https://example.com/docs)** | **[Support](https://example.com/support)**
