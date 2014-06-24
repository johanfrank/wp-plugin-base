WPB - WP Plugin Base
====================
0.6 - June 24nd 2014

Extendable PHP class for creation of WordPress plugins. Just include `wp-plugin-base.php` in your project and extend the Base class:

	class MyPlugin extends WPB\Base {
		
		public function __construct() {

			// Set up your stuff here:
			$this->project_name = 'WPB Example';
			$this->project_prefix = 'wpb';

			parent::__construct(__FILE__);
		}
	}

Most of the magic in WPB happens in the constructor, where you set up fields in an associative array and calls the parent constructor to initiate the heavy lifting. You can see a simple working example of how to use the class in `example.php`.

This class aims to use only standard WP functions and not depend on any third-party library, tool or utility.

It is currently being developed using **WordPress 3.9.1**.

## Features

* Handles reading and writing post meta automatically.
* Sets up and registers metaboxes, custom post types, taxonomies, scripts and styles.
* Renders metaboxes automatically, if custom render method isn't defined.
* Offers simple hooks before saving and rendering for custom cases.
* Admin view to automatically remove all created data (post meta, taxonomy terms, custom posts) before uninstalling.

#### Requirements

* PHP 5.4+

#### Prefix

It is recommended that you choose a project-specific slug to use as a prefix for all meta keys, values and various fields.

Set it by defining `$project_prefix` in your class constructor:

	$this->project_prefix = 'wpb';

You should also create a name for your project, if you want to show a data removal page in the Tools admin section:

	$this->project_name = 'WPB Example';

#### Metaboxes

A nested array (`$metaboxes`) that defines individual metaboxes and their post meta fields:

	$this->metaboxes = array(
		'mb_book_details' => array(
			'metabox' => array(
				'title' => __('Book details', $this->translate_domain),
				'post_type' => 'cpt_book',
				'priority' => 'high',
				'context' => 'side'
			),
			'post_meta' => array(
				'writer' => array(
					'label' => __('Writer', $this->translate_domain),
					'type' => 'text',
				)
			)
		),
		'mb_bookcase_list' => array(
			'metabox' => array(
				'title' => __('List of books', $this->translate_domain),
				'post_type' => 'cpt_bookcase',
				'context' => 'side',
			),
			'post_meta' => array(
				'books' => array(
					'label' => __('Books', $this->translate_domain),
					'type' => 'text'
				)
			)
		),
	);

Available field types:
* `text` - Default input type.
* `textarea` - A textarea with option of `rows` (default: 5).
* `color` - An input text with built-in colorpicker.
* `media` - An input text with built-in media gallery overlay.
* `checkbox` - One or many key-value pairs, set by defining `values` as an array.
* `radio` - Like checkbox only rendered as radio buttons.
	
#### Post types

`$post_types` - A nested array that defines new custom post types:

	$this->post_types = array(
		'cpt_book' => array(
			'label' => __('Book', $this->translate_domain),
			'public' => true
		),
		'cpt_bookcase' => array(
			'label' => __('Bookcase', $this->translate_domain),
			'public' => true,
		),
	);

For slug and unique identifier, the key is used (`cpt_book` and `cpt_bookcase` in example). The arguments are the same as the second argument for [register_post_type](https://codex.wordpress.org/Function_Reference/register_post_type).

#### Taxonomies

`$taxonomies` - A nested array that defines new taxonomies:

	$this->taxonomies = array(
		'tax_genre' => array(
			'custom_post_types' => 'cpt_book',
			'post_types' => array(),
			'args' => array(
				'label' => __('Genres', $this->translate_domain)
			)
		)
	);

The arguments in `args` are the same as in [register_taxonomy](https://codex.wordpress.org/Function_Reference/register_taxonomy).

#### Admin scripts

`$scripts` - A nested array that defines new scripts:

	$this->scripts = array(
		'script_example' => array(
			'src' => '/js/example.js',
			'deps' => array('jquery')
		)
	);

The arguments are the same as for [wp_enqueue_script](https://codex.wordpress.org/Function_Reference/wp_enqueue_script).

#### Admin styles

`$stylesheets` - A nested array that defines new styles:

	$this->stylesheets = array(
		'script_example' => array(
			'src' => 'css/example.css',
			'deps' => array(),
			'ver' => false,
			'media' => 'all'
		)
	);

The arguments are the same as for [wp_enqueue_style](https://codex.wordpress.org/Function_Reference/wp_enqueue_style).

## Callback functions

By default WPB will load, save and render all post meta fields that are properly set up. But sometimes you need to render the field in a different way, or the data may require further operations to be presentable, either before rendering or before saving. You can set up your own functions, in your `post_meta` declarations in the `$metaboxes` setup:

	$this->metaboxes = array(
		'mb_book_details' => array(
			'metabox' => array(
				'title' => __('Book details', $this->translate_domain),
				'post_type' => 'cpt_book',
				'priority' => 'high',
				'context' => 'side'
			),
			'post_meta' => array(
				'writer' => array(
					'label' => __('Writer', $this->translate_domain),
					'type' => 'text',
					'render' => 'custom_rendering_function',
					'before_render' => 'custom_before_render',
					'before_save' => 'custom_before_save'
				)
			)
		),
	);

In this example (which is taken from `example.php`), we set up a post meta field called `writer` in our constructor. But we want to fetch the saved value and render it in a different way:

	public function custom_rendering_function($meta_key, $data) {
		return '<label>'.$data['label'] . '<br><input name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$data['content'].'">Old value: '.$data['content'].'</label>';
	}

This will show us the old value below the input field as text as you edit the input field. Your custom render function will have the `meta_key` (in example: `writer`) and `data` (in example: array of `label`, `type`). In the same fashion, we can define our own callback functions with `before_render` and `before_save`:

	public function custom_before_render($data) {
		return $data;
	}

	public function custom_before_save($data) {
		return $data;
	}

The before callback functions have one parameter, and that is the `$data` about to be saved as post meta.

## Update history

**Version 0.6**
* Refactored image upload field.
* Added WPB CSS file and moved inline CSS.

**Version 0.5**
* Fixed bug where post meta fields could only be edited on custom post types.
* Image preview for media field type.
* Added function to render templates.

**Version 0.4**
* Added data removal page in Admin > Tools menu.

**Version 0.3**
* Minor fixes and cleaned up documentation.
* Removed uninstall callbacks for now.
* Fixed error where activation_hook() was called twice.

**Version 0.2:**
* Added `textarea` as an input type.
