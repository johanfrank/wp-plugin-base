wppluginbase
============

Extendable PHP class for creation of WordPress plugins. Just include the class in your project and use:

	class MyPlugin extends WP_Plugin_Base {
		# stuff being done
	}

## Features

* Handles reading and writing post_meta automatically.
* Removes all post_meta when uninstalling plugin.
* Sets up and registers metaboxes, custom post types and taxonomies.
* Renders metaboxes automatically, if not explicitly rendered by render().

## Properties

* `$plugin_name` - Name of your plugin.
* `$plugin_slug` - Lowercase, URL friendly version of `$plugin_name`.
* `$translate_domain` (optional) - Must be defined if you want to use proper localization.
* `$metaboxes` - A nested array that defines individual metaboxes and their fields:

* `$posttypes` - A nested array that defines new custom post types:

* `$taxonomies` - A nested array that defines new taxonomies:

## Methods

* `render()`
