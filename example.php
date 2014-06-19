<?php
/*
* Plugin Name: WPB Example Class
* Plugin URI: https://github.com/pjnorberg/wp-plugin-base
* Description: Example class for WPB
* Version: 1.0
* Author: Johan Norberg (@pjnorberg)
* Author URI: http://johannorberg.biz
*/

include_once('wp-plugin-base.php');

class ExamplePlugin extends WPB\Base {

	// Set up a translate_domain if you want to use localization for your plugin:

	protected $translate_domain = 'wpb_swedish';

	public function __construct() {

		// Set up project prefix used to name fields and variables:

		$this->project_prefix = 'wpb';

		// Define my custom post types:

		$this->post_types = array(
			'cpt_book' => array(
				'label' => __('Book', $this->translate_domain),
				'public' => true,
				'rewrite' => array('slug' => 'book')
			),
			'cpt_bookcase' => array(
				'label' => __('Bookcase', $this->translate_domain),
				'public' => true,
				'rewrite' => array('slug' => 'bookcase')
			),
		);

		// Set up some post meta fields for our Books:

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
						'before_render' => 'custom_before_render',
						'before_save' => 'custom_before_save'
					),
					'checkboxes' => array(
						'label' => __('Checkboxes', $this->translate_domain),
						'type' => 'checkbox',
						'description' => __('Multiple selection is allowed.', $this->translate_domain),
						'options' => array(
							'sci-fi', 'horror', 'drama', 'thriller', 'comedy'
						),
					),
					'radio' => array(
						'label' => __('Radiobuttons', $this->translate_domain),
						'type' => 'radio',
						'description' => __('Only one selection is allowed.', $this->translate_domain),
						'options' => array(
							'banana', 'apple', 'lemon', 'orange', 'kiwi'
						),
					),
					'color' => array(
						'label' => __('Orwells favorite color', $this->translate_domain),
						'type' => 'colorpicker',
						'description' => __('Educated guess.', $this->translate_domain),
					),
					'second_color' => array(
						'label' => __('Orwells second favorite color', $this->translate_domain),
						'type' => 'colorpicker',
						'description' => __('Just random!', $this->translate_domain),
					),
					'media' => array(
						'label' => __('Image', $this->translate_domain),
						'type' => 'media',
						'description' => __('A picture or something else from the media gallery.', $this->translate_domain),
					),
				)
			),
			'mb_bookcase_list' => array(
				'metabox' => array(
					'title' => __('List of books', $this->translate_domain),
					'post_type' => 'cpt_bookcase',
					'context' => 'side',
					'render' => 'custom_rendering_function'
				),
				'post_meta' => array(
					'books' => array(
						'label' => __('Books', $this->translate_domain),
						'type' => 'email'
					)
				)
			),
		);

		// Register your project CSS:

		$this->stylesheets = array(
			'style_example' => array(
				'src' => '/css/example.css',
				'deps' => array(),
				'ver' => false,
				'media' => 'all'
			)
		);

		// Register your project JS:

		$this->scripts = array(
			'script_example' => array(
				'src' => '/js/example.js',
				'deps' => array('jquery')
			)
		);

		// Register your project taxonomies:

		$this->taxonomies = array(
			'tax_genre' => array(
				'custom_post_types' => 'cpt_book',
				'post_types' => array(),
				'args' => array(
					'label' => __('Genres', $this->translate_domain)
				)
			)
		);

		parent::__construct(__FILE__);
	}

	public function custom_rendering_function($meta_key, $data) {
		return '<label>'.$data['label'] . '<br><input name="'.$meta_key.'_meta_value_field" type="text" class="widefat" value="'.$data['content'].'">Old value: '.$data['content'].'</label>';
	}

	public function custom_before_render($data) {
		return $data;
	}

	public function custom_before_save($data) {
		return $data;
	}
}

$ExamplePlugin = new ExamplePlugin();