=== iikoCloud for WooCommerce ===
Contributors: makspostal
Tags: iiko, woocommerce, delivery, restaurant
Requires at least: 5.5
Tested up to: 5.7
Requires PHP: 7.2
Stable tag: 1.3.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

iikoCloud API (iikoTransport API) is a single point to use the functionality provided by iiko.

== Description ==

This plugin provides the integration of the basic functionality of the iikoCloud API into WooCommerce:
1. Import of organizations, terminals, nomenclature (categories of dishes, goods, and dishes with sizes and modifiers), payment types, cities, and streets.
2. Export of orders.
3. Auto import using WP CRON (PREMIUM).

[iiko](https://iiko.ru/) – a convenient and reliable solution used by a wide variety of establishments: from tiny bars and coffee houses to large restaurant chains with hundreds of outlets and a developed franchise, from food court cafes to gourmet restaurants, from ready-to-eat food delivery projects to gastro-markets.
[Documentation iiko](https://ru.iiko.help/)
[iikoTransport. API description](https://api-ru.iiko.services/)

= How to work with the plugin =

1. Enter apiLogin on the plugin settings page (Main tab). Click the "Save Changes" button.
2. Go to the plugin page and click the "Get Organizations" button.
3. Select the required organization and click the "Get Terminals" button.
4. Select the required terminal and click the "Get nomenclature" button.
5. Select the desired groups and click the "Import Selected Groups and Products" button. The selected groups and the products / products they contain will be uploaded to the site.
6. Click the "Get Cities" button.
7. Select the city for which you want to load streets and click the "Get streets" button.
8. Click the "Get Payment Types" button.
9. Enter the default street name in the plugin settings. It will be used when the client enters a street name that is not in iiko. This street must be added to iiko.
10. Fill in the WooCommerce payment method designations corresponding to the iiko payment types in the plugin settings. For example, the settings for the standard Cash payment method are available at /wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod, where `cod` stands for the Cash payment method.
11. Orders are exported to iiko automatically when placing orders on the website.
12. Check 'Import nomenclature automatically' and set recurrence period on 'Auto import' plugin settings page if you want to use it.

= Notes =

1. You can upload an item to the site without selecting a terminal, but it is impossible to export orders to iiko without specifying a terminal.
2. Groups highlighted in green are optional to load, since these are modifier groups loaded as individual attributes of goods. Strikethrough groups - marked for deletion in iiko.
3. The plugin settings contain the names and IDs of the selected organization, terminal and city, as well as the number of loaded streets.
These fields are not available for editing in the plugin settings and are filled in automatically:
    - the name and ID of the organization and the terminal - by clicking on the "Get the item" button;
    - city name and ID, as well as the number of loaded streets - by clicking on the "Get streets" button.
4. When placing an order, the fields "Name", "Phone", "Address", and "Supplement to the address" must be required.
5. The phone number must start with the "+" symbol and be at least 8 digits long.
6. To change the customer's phone when exporting an order, you can use the `skyweb_wc_iiko_order_phone` filter.
7. To add the date and time of order preparation when exporting orders, you can use the `skyweb_wc_iiko_order_complete_before` filter.
8. To add additional address data when exporting orders, you can use the `skyweb_wc_iiko_order_delivery_point` filter.
9. To add additional data to the comment when exporting orders, you can use the `skyweb_wc_iiko_order_comment` filter.
10. To add the number of guests when exporting orders, you can use the `skyweb_wc_iiko_order_guests` filter.
11. To add payment methods when exporting orders, you can use the `skyweb_wc_iiko_order_payments` filter or use the logic implemented by the plugin.
12. Supports downloading a list of streets for one city.

= Working with iiko =

* [Connection iikoCloud API](https://ru.iiko.help/articles/#!api-documentations/connect-to-iiko-cloud)
* [Dishes categories](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-35)
* [Nomenclature elements](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-41)
* [Modifiers](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-3100)
* [Size scale](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-3080)
* [Modifier schemes](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-224)
* [Orders to change the price list](https://ru.iiko.help/articles/#!iikooffice-7-6/topic-801)
* [Payment types](https://ru.iiko.help/articles/iikooffice-7-6/topic-103)

= Support for themes =

**This plugin works great with the shipping theme [Skyweb Delivery](https://rudelivery.skyweb.team)**

= PREMIUM version =

**PREMIUM version includes:**

* Lifetime license.
* Free updates.
* Direct support.
* Installation support.
* Additional services.

To get it please email to **hi@skyweb.team**


== Frequently Asked Questions ==

= Where can I get apiLogin? =

Read the documentation [iikoCloud API connection](https://ru.iiko.help/articles/#!api-documentations/connect-to-iiko-cloud) or contact your personal iiko manager / integrator.

= Is import of nested iiko product groups supported? =

No, but you can manually nest WooCommerce product categories through the Products - Categories section.

= Is WooCommerce variable product import supported? =

Yes, with the following features:
1. Supports loading iiko dish sizes (one size scale per dish - iiko limitation) as individual attributes of a WooCommerce product.
2. Supports loading only group modifiers (one group of modifiers - plugin restriction) as individual attributes of a WooCommerce product.
3. Price / quantity / conditions of modifiers are not taken into account when creating variations of a WooCommerce product.
The variation price is formed as follows:
* If the loaded dish has only sizes, then for each size its own variation with the price of the size is created.
* If the loaded dish has only a group of modifiers, then for each modifier its own variation with the price of the dish is created.
* If the loaded dish has both sizes and a group of modifiers, then for each size several variations are created (depending on the number of modifications) with the price of the size.
4. The modifier schema is not supported in menu unloading, that is, modifier schemas are not transferred via the iikoCloud API. Use only custom modifiers for the dish.

= Is the "Default" attribute of iiko sizes supported (specified when creating the size scale)? =

Not.

= Is the "Available" attribute of iiko sizes supported (specified when editing a dish)? =

Yes.

= Is import iiko modifiers supported as WooCommerce products? =

No, product modifiers are loaded only as attributes, see the question above.

= Is import of iiko products supported as WooCommerce products? =

Yes.

= What data is being imported? =

**Categories (groups):**
- name;
- description;
- image;
- iiko ID;
- SEO title (SeoTitle);
- SEO description (SeoText).
Modifier groups that are removed and excluded from the group menu are not imported or updated.
SEO data is imported for the Yoast SEO plugin.

**Products (dishes):**
- name;
- description;
- short description (technical information);
- main image;
- image gallery (not supported by iiko API);
- SKU;
- price;
- weight;
- tags (labels), filled in iiko BackOffice separated by semicolons `;` without spaces;
- iiko ID;
- SEO title (SeoTitle);
- SEO description (SeoText).
Deleted dishes are not imported or updated.
Dishes excluded from the menu are imported with the "hidden everywhere" status.
SEO data is imported for the Yoast SEO plugin.

= What does the 'Error while getting iiko nomenclature from the cache.' mean? =

It means that you need to update the item from iiko on the plugin page.

= What if I want additional features? =

Email **hi@skyweb.team**

= How do I get the PREMIUM version? =

Email **hi@skyweb.team**


== Installation ==

= Using The WordPress Dashboard (Recommended) =

1. Go to `Plugins` → `Add New`.
2. In a search field type **iikoCloud for WooCommerce** and hit enter.
3. Click `Install Now` next to **iikoCloud for WooCommerce** by SkywebSite.
4. Click `Activate the plugin` when the installation is complete.

= Uploading in WordPress Dashboard =

1. Go to `Plugins` → `Add New`.
2. Click on the `Upload Plugin` button next to the **Add Plugins** page title.
3. Click on the `Choose File` button.
4. Locate **skyweb-wc-iiko.zip** on your computer.
5. Click the `Install Now` button.
6. Click `Activate the plugin` when the installation is complete.

= Using FTP (Not Recommended) =

1. Download **skyweb-wc-iiko.zip**.
2. Extract the **iikoCloud for WooCommerce** directory to your computer.
3. Upload the **skyweb-wc-iiko** directory **/wp-content/plugins/**
4. Go to `Plugins` → `Installed Plugins`.
5. Click `Activate` under **iikoCloud for WooCommerce** plugin title.

= Requirements =

1. For the plugin **iikoCloud for WooCommerce** to work, you must have installed and activated plugin **WooCommerce**.
2. Plugin settings are located in the menu: `WooCommerce -> Settings -> iikoCloud`.
3. If the apiLogin field is empty, then the plugin will not work.


== Changelog ==

= 1.3.2 =
* Added auto import using WP CRON (PREMIUM).
* Minor fixes.

= 1.3.1 =
* Added order checking from order list in admin panel.
* Improved order export comment.
* Improved logs.
* Fixed known issues.

= 1.3 =
* The export of the order is sent once when the order is created.
* If the order is successfully exported, a corresponding notification is added to it.
* Added the ability to manually export an order from the admin panel.
* Added a filter to change the customer's phone number when exporting an order.
* Added 'Complete before' field to indicate order time.
* Added processing of address fields on the checkout page.
* Added new import settings.
* The settings page is grouped into blocks by separate tabs.
* And something more.

= 1.2 =
* Added notifications to the administrator's mail in case of problems with exporting orders.

= 1.1 =
* Support for WooCommerce and iiko payment methods.
* Increased minimum PHP version to 7.2 (for compatibility with the nearest WooCommerce versions).

= 1.0 =
* The first version of the plugin.


== Screenshots ==

1. Plugin settings (tab General).
2. Plugin settings (tab Import).
3. Plugin settings (tab Export).
4. Plugin settings (tab Payment types).
5. Plugin settings (tab Auto import).
6. Plugin settings (tab Checkout).
7. Plugin page.
8. Imported products (front).
9. Checkout page (example).
10. Orders page (additional order actions: export to iiko and check status).