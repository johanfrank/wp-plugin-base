<?php

namespace WPB;

class Base {

	protected $plugin_base;
	protected $plugin_rel_base;
	protected $cpt;

	public function __construct() {

		$this->plugin_base = rtrim(dirname(__FILE__), '/');
		$this->plugin_rel_base = dirname(plugin_basename(__FILE__));

		register_activation_hook(__FILE__, array(&$this, 'activation_hook'));
		register_activation_hook(__FILE__, array(&$this, 'deactivation_hook'));
		register_uninstall_hook(__FILE__, array(get_class(), 'uninstall_hook'));

		add_action('init', array($this, 'register_cpt'));
	}

	public function activation_hook($network_wide) {

		$this->check_requirements();
	}

	public function deactivation_hook($network_wide) {

	}

	public static function uninstall_hook($network_wide) {

		if (! defined('WP_UNINSTALL_PLUGIN')) {
			die();
		}
	}

	public function register_cpt() {

		echo '<pre>'.print_r($this->cpt, true).'</pre>';

		if ($this->cpt) {
			foreach ($this->cpt as $post_type => $options) {

				if ($post_type == 0) {
					$post_type = $options;
					$options = null;
				}

				if (empty($options)) {
					$options = null;
				}

				register_post_type($post_type, $options);
			}
		}
	}
}