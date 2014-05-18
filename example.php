<?php
/*
* Plugin Name: WPB Example Class
* Plugin URI: https://github.com/pjnorberg/wp-plugin-base
* Description: Example class for WPB
* Version: 1.0
* Author: Johan Norberg (pjnorberg)
*/

include_once('wp-plugin-base.php');

class ExamplePlugin extends WPB\Base {

	public function __construct() {

		$this->cpt = array(
			'my_example_cpt'
		);

		parent::__construct();
	}
}

$ExamplePlugin = new ExamplePlugin();