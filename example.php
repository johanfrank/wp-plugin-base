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
				'public' => true
			),
			'cpt_bookcase' => array(
				'label' => __('Bookcase', $this->translate_domain),
				'public' => true,
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
					)
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

		parent::__construct();
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