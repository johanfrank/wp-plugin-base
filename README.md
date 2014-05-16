wppluginbase
============

Extendable PHP class for creation of WordPress plugins. Just include the class in your project and use:

	class MyPlugin extends WP_Plugin_Base {
		# stuff being done
	}

## Features

* Handles reading and writing post_meta automatically.
* Removes all post meta when uninstalling plugin.
* Sets up and registers metaboxes, custom post types and taxonomies.
* Renders metaboxes automatically, if not explicitly rendered by render().

## Properties

#### Basics

* `$plugin_name` - Name of your plugin.
* `$plugin_slug` - Lowercase, URL friendly version of `$plugin_name`.
* `$translate_domain` (optional) - Must be defined if you want to use proper localization.

#### Metaboxes

A nested array (`$metaboxes`) that defines individual metaboxes and their fields:

	$metaboxes = array(
		'first_metabox' => array(
			array(
				'field_key' => 'field_one_key',
				'field_name' => 'field_one_name',
				'type' => 'text'
			),
			array(
				'field_key' => 'field_two_key', 
				'field_name' => 'field_two_name', 
				'type' => 'color'
			),
			array(
				'field_key' => 'field_three_key', 
				'field_name' => 'field_three_name', 
				'type' => 'media'
			),
			array(
				'field_key' => 'field_four_key', 
				'field_name' => 'field_four_name', 
				'type' => 'checkbox', 
				'values' => array(
					0 => 'no',
					1 => 'yes'
				)
			),
		)
	);

Available field types:
* `text` - Default input type.
* `color` - An input text with built-in colorpicker.
* `media` - An input text with built-in media gallery overlay.
* `checkbox` - One or many key-value pairs, set by defining `values` as an array.
* `radio` - Like checkbox only rendered as radio buttons.
	
#### Post types

`$posttypes` - A nested array that defines new custom post types:

#### Taxonomies

`$taxonomies` - A nested array that defines new taxonomies:

## Methods

* `render($template = null)`
Explicitly render a custom template in `/templates/`.
