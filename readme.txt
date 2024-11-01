=== White Rabbit All in One Suite ===
Contributors: whiterabbitsuite
Tags: seo,post,order,user
Donate link: https://www.whiterabbit.cloud/partners-commerciali-e-distributori/
Requires at least: 5.1
Tested up to: 6.4.3
Requires PHP: 7.4.0
Stable tag: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

White Rabbit software enables you to implement an e-commerce in Wordpress through  just one click


== Description ==
White Rabbit software enables you to implement an e-commerce in Wordpress through  just one click.

The site will be hosted by professional servers and the data will be protected and updated directly  by our team.

Being generated on safe  and  well-established platforms, the White Rabbit E-Commerce retains  features and potentials of  the source sites, together with the added value of the other Happy Business Suite  functions.

If you already have a wordpress site download the plug-in  in order to add it to White Rabbit immediately.

In this way you will synchronize your showcase functions with the business and customers databases.
You will be able to manage comunication, notifications and surveys , by monitoring the trend of comunication and marketing  activities  easily , quickly and in an integrated way.

Have a Happy Business with White Rabbit !

Read more on [https://www.whiterabbitsuite.com](https://www.whiterabbitsuite.com)

== Installation ==
Currently, the sites created with the White Rabbit Suite are not automatically linked to the suite. In the future this link will be done automatically. To link he newly created Wordpress site and be able to interact with its multiple tools (from direct publication via the Suite of the articles to automatic classification on the CRM of the received contacts) you will have to download the package plugin to install on Wordpress.
1- Click on the Download plugin icon featured in the Actions
2- Download the linking package suitable for your site type (e.g. Wordpress) by clicking on the Download button
3- Within the Wordpress Administration Panel, select from the menu on the left the Plugin option, then Add new
4- Click on the Upload plugin button
5- Select the .zip file downloaded from the Suite of White Rabbit (do not unzip the .zip file, but select the .zip file)
6- Click Wordpress Install Now button to proceed with the installation of the Plugin
7- Following the installation procedure, you will be prompted a page with the message \"Plugin installed correctly\", click on the link \"Activate plugin\"
8- You should now find within the Dashboard of Wordpress plugins, the activated Plugin of White Rabbit (in the event of failed activation you will still find the \"Enable plugin\" option of the White Rabbit plugin)
9- Click on \"White Rabbit\" option from the menu on the left of the Wordpress Administration area
10- Enter the Token Site Login Suite given from the suite on the site settings (Attention! Not the Wordpress login data, but the Suite Login data)
11- In case you already have WooCommerce installed, you must enter the API REST keys. You can create them by following the official WooCommerce documentation.
12- Click on Save changes, the system should prompt a successful connection message

The Actions menu of a non linked site (top) and a linked site (bottom)
Now go back to the Suite, the site in question should be correctly linked to the Suite, you will find the link symbol that turned into a green chain instead of a red disconnected chain).

== Frequently Asked Questions ==
= Why do I get an activation error of the plugin? =
You are very likely to have an old version of the plugin. To solve the problem, uninstall the old plugin (version lower than or equal to 2.3.4) and reinstall it from here.
= Why doesn't the Suite fetch all the fields when I fill out a form? =
To make sure the Suite fetches all the fields in a form, you need to use the [Contact Form 7](https://it.wordpress.org/plugins/contact-form-7/) plugin or [WPForms](https://it.wordpress.org/plugins/wpforms-lite) plugin. You also need to edit the contact form and use the exact field names that the Suite accepts. In case the procedure doesn't work, even if followed correctly, delete the website from the Suite and the plugin from your website and start over by re-connecting the website.
= What are the field names that the Suite accepts in the contact form? =
The complete field names list can be found at the following [link](https://s3-eu-west-1.amazonaws.com/whiterabbitsuite.com/plugins/wordpress/Campi_WPCF7.xlsx). Some minor details about the fields:
- Only one tag can be passed
- The "province/nation" field must be written in the ISO format (PE, MI, NA...) to make sure that the CRM will interpret it in the correct manner. If the province is italian, the nation will be automatically set to IT.
- Use the exact same nomenclature that the fields in the left column of the spreadsheet above inside the Contact Form 7 forms' HTML have.
- The Suite does not perform any type of validation of the fields, so make sure to validate them inside the form before the sending, using the form controls that Contact Form 7 provides.
= What am I supposed to do in case of errors or malfunctioning of the plugin? =
In case any problem arises, you can contact the support at this [email](mailto:assistenza@whiterabbit.cloud) and make sure to attach your server error log. In some cases you will be asked to give us access to your website to solve the problem.
= Why doesn't the plugin track WooCommerce data in real time? =
You probably have an outdated version of WooCommerce that our plugin doesn't support for technical reasons. The plugin supports [WooCommerce](https://it.wordpress.org/plugins/woocommerce/) versions from 3 onwards.
= How do I create the API REST keys to import WooCommerce's data in the Suite? =
To create WooCommerce's API REST keys, follow the [official guide](https://woocommerce.github.io/woocommerce-rest-api-docs/#rest-api-keys) taken from their documentation. The plugin asks for these keys only when WooCommerce has been installed before our plugin.

== Screenshots ==
1. White Rabbit Suite plugin setting
2. White Rabbit Suite GDPR view
3. White Rabbit Suite post setting
4. White Rabbit Suite advanced

== Changelog ==
Version 1.0   First release
Version 1.1   New features:
                       * Piwik integration
                       * Author and default category setting
                       * New White Rabbit menu
Version 1.2 Bug fix and performance improvements
Version 1.3 Uninstall and deactivate bug fix; new url api feature
Version 1.4 Various fixes
Version 1.5 Choosing the post status to be published from the suite to WP
             * Recovery Authors by choice in posts to be published from the suite to WP
             * Upload img to WP media
             * Ticket Creation From the contact form using WPCF7 from option
             * Landing from suites as external page
Version 1.6 Bug fix and performance improvements
Version 1.7 Bug fix and performance improvements
Version 1.8 GDPR . Bug fix and performance improvements
Version 1.9 Securety update
Version 2.0 Securety update Login
Version 2.1 fix and performance improvements
Version 2.2 Update Woocomerce 3.0
Version 2.3 New Suite Crm
Version 2.3.1 New Action Site Submitted Form
Version 2.3.2 Check if JWT lib aready exists to exclude JWT.php
Version 2.3.3 Check if JWT lib aready exists to exclude SignatureInvalidException.php
Version 2.3.4 All fields CRM from form contact 7 + user (meta dati)
Version 2.3.5 Bug fix and performance improvements + italian translation
Version 2.3.6 Bug fix and performance improvements
Version 2.3.7 Generate sitemap & robots SEO manually
Version 2.3.8 New WPForms Integration with All fields CRM
Version 2.3.9 Bug fix and performance improvements
Version 2.4 Bug fix and performance improvements
Version 2.4.1 Bug fix and performance improvements
Version 2.5 Added event management and setUserId for Woocommerce
Version 2.5.1 Bug fix and performance improvements
Version 2.5.2 Bug fix and performance improvements
Version 2.6 Scripts retrieve & matomo improvements
Version 2.6.1 Bug fix and performance improvements
Version 2.7 Import Abandoned Carts by custom core Crons
Version 2.7.1 Bug fix and performance improvements
Version 2.7.2 Backward compatibility for WP 4.9.13
Version 2.7.3 Added Custom Variable management from contact form 7
Version 2.7.4 Bug fix and performance improvements
Version 2.8 Added support for multisite network
Version 2.8.1 Added change order status bulk action
Version 2.8.2 Added tags coupon to change order status
Version 2.8.3 Added gdpr purpose to Woocommerce registration custom fields
Version 2.8.4 Bug fix and performance improvements
Version 2.8.5 Bug fix and performance improvements
Version 3.0.0 New Release update
== Upgrade Notice ==
the 1.6 version upgrade allows posting and landing directly from the suite, having piwik statistics, retrieving woocommerce orders and displaying them directly into the suite
the 2.3.5 version upgrade allows import from Suite by Woocommerce API REST keys and plugin translated in italian
the 2.3.7 version upgrade allows to generate sitemap and robots on root site folder from backend
the 2.3.8 version upgrade allows to map fields from WPForms directly by admin settings panel from backend
the 2.3.9 version upgrade allows to track email fields from WPForms and WPCF7 on submit by Piwik
the 2.5 version upgrade allows to track events and email fields from Woocommerce by Piwik
the 2.6 version upgrade allows to get remote scripts once and update Matomo tracking
the 2.7 version upgrade allows to import abandoned carts by custom core crons
the 2.8 version upgrade allows to support installation for multisite network
the 3.0 version upgrade required php 7.4.0