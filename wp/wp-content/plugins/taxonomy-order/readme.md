# Taxonomy Order #
**Contributors:** shivadai
  
**Donate link:** http://css-gen.com/donate
  
**Tags:** order, re-order, ordering, taxonomy, taxonomies, manage, ajax, drag-and-drop, admin
  
**Requires at least:** 3.4
  
**Tested up to:** 3.6
  
**Stable tag:** 1.1
  
**License:** GPLv3
  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html
  

Order your categories, tags or any taxonomy's terms with simple Ajax Drag and Drop Interface right from the standard terms list.

## Description ##

Have you ever wanted to sort your built in categories(hierarchical) and tags(non-hierarchical) taxonomies terms with simple Ajax Drag and Drop Interface right from their standard list? Have you ever tried a plugin that implements some sort of custom taxonomy but their lacks support for drag and drop interface?

As the name suggests this plugin is intended toward taxonomy terms order with awesome drag and drop support. If your wordpress website is one of the content source or blog then for sometime it becomes hard for you to managed categories or taxonomies. Than, this plugin is definitely for you. Using it you can enable Ajax Drag and Drop Interface for ordering built in taxonomies (i.e categories and tags). But not only, you can set it to enable custom ordering of any other taxonomy’s terms, even taxonomies created by other plugins too.

By default, Ajax Drag and Drop Interface is enable for your posts categories and tags. But if you want to order any other taxonomy then check out [FAQ](http://wordpress.org/plugins/taxonomy-order/faq/) Page.

### Special Features ###
* Simply drag and drop the taxonomy’s term into the desired position. *It's that awesome.*
* Can easily provide ajax drag and drop interface ordering support for only desired taxonomy’s terms too.
* No new admin menus pages, no clunky, no ads or bolted on user interfaces.
* Integrated Easy Documentation. Just click the *“help”* tab at the top right of the screen.
* When you unistall the plugin tables that were created by this plugin will also be removed. **So you are safe while using this plugin.**

### Feedback ###
* I am open for your suggestions and feedback - Thank you for using or trying out one of my plugins!
* I'll try to add more plugin/theme support if it makes some sense. So stay tuned :).
* Drop me a line [@InfoShiva](http://twitter.com/#!/InfoShiva) on Twitter
* Follow us on [+Shiva Poudel](https://plus.google.com/100870524275518259709) on Google Plus
* Or follow our community on [Facebook Page](http://facebook.com/cssgen) ;-)
 
### More ###
* [Also see my other plugins](http://css-gen.com/products/wp-plugins/) or see [my WordPress.org profile page](http://profiles.wordpress.org/users/shivadai/)

### Help and Support ###
You would like to support us? Go over [at our website](http://css-gen.com/donate "Buy us a Coffee") and donate us for buying us a coffee for making us crazy to think creative and develop interesting and fabulous plugins/themes.

Want to contribute? This plugin is on [Github](https://github.com/shivapoudel/Taxonomy-Order).

**Credit where credit is due:-** This plugin here is inspired and based on the work of *"Simple Page Ordering"* and *"Gecka Terms Ordering"* Plugin.

## Installation ##

1. Upload the entire `Taxonomy-Order` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it Go and manage you categories or tags now :)

## Frequently Asked Questions ##

### Does this plugin work with newest WP version and also older versions? ###
Yes, this plugin works really fine with WordPress 3.4!
It also should with WP 3.0+ - but we only tested extensively with WP 3.4 to 3.6. So you always should run the latest WordPress version for a lot of reasons.

### Is this plugin Secure ###
This plugin is 100% secure to install. While activating this plugin new table is created in database but if you no longer need this plugin and want to roolback changes made by this plugin then you can easily deactivate and then uninstall it from your 'plugins' menu in wordpress.

### Does it require PHP version 5.0.0+ ###
Definitely yes because this plugin is based on OOP Class and if you are running PHP<5.0.0 then this plugin will never be activated.

### Can I make my cusom taxonomy take advantage of this plug-in? ###
Yep. To enable ordering terms of a specific taxonomy add t
his code to your theme's *function.php* file:

`if( function_exists('add_interface_taxonomy_order') ){
	add_interface_taxonomy_order ("$taxonomy_name");
}`

### Can I remove support for a specific taxonomy ###
Yep. To remove support for a specific taxonomy add this code to your theme's *function.php* file:

`if( function_exists('remove_interface_taxonomy_order') ){
	remove_interface_taxonomy_order ("$taxonomy_name");
}`

### Can I check if terms ordering is enable ###
Yep. To check if terms ordering is enable for a specific taxonomy add this code to your theme's *function.php* file:

`if( function_exists('has_interface_taxonomy_order') ){
	$enable = has_interface_taxonomy_order ("$taxonomy_name");
}`

## Screenshots ##

###1. Dragging the taxonomy's term to its new position
###
![Dragging the taxonomy's term to its new position
](https://raw.github.com/shivapoudel/Taxonomy-Order/master/screenshot-1.png)

###2. Processing indicator
###
![Processing indicator
](https://raw.github.com/shivapoudel/Taxonomy-Order/master/screenshot-2.png)


## Changelog ##

### 1.1 ###
Small Bug Fix...

### 1.0 ###
Initial Version...

## Upgrade Notice ##

### 1.1 ###
Just released into the wild.

### 1.0 ###
Just released into the wild.