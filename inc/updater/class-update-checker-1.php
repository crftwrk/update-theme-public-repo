<?php
/**
 * Bootscore Update Checker
 * Shared update library for Bootscore products
 *
 * @package Bootscore_Update_Checker
 * @version 6.5.0
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

if (!class_exists('Bootscore_Update_Checker')) {

  class Bootscore_Update_Checker {

    /**
     * @var string Update server URL
     */
    private $server_url;

    /**
     * @var int Cache duration in seconds
     */
    private $cache_time;

    /**
     * @var array Registered products
     */
    private $products = array();

    /**
     * Constructor
     *
     * @param string $server_url Update server URL
     * @param int    $cache_time Cache duration in seconds
     */
    public function __construct($server_url, $cache_time = 43200) {
      $this->server_url = trailingslashit($server_url);
      $this->cache_time = $cache_time;

      // Hook into WordPress
      add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
      add_filter('site_transient_update_plugins', array($this, 'plugin_updates'));
      add_filter('pre_set_site_transient_update_themes', array($this, 'theme_updates'));
      add_action('upgrader_process_complete', array($this, 'clear_cache'), 10, 2);
    }

    /**
     * Register a product for updates
     *
     * @param array $product {
     *     @type string 'type'            'plugin' or 'theme'
     *     @type string 'slug'            Unique product slug
     *     @type string 'current_version' Current installed version
     *     @type string 'file'            Plugin file path or theme slug
     *     @type string 'server_path'     Path on update server (optional)
     * }
     */
    public function register_product($product) {
      $defaults = array(
        'type' => 'plugin',
        'slug' => '',
        'current_version' => '0.0.0',
        'file' => '',
        'server_path' => '',
        'name' => '',
      );

      $product = wp_parse_args($product, $defaults);

      // Auto-detect server path if not provided
      if (empty($product['server_path'])) {
        $product['server_path'] = $product['type'] . 's/' . $product['slug'];
      }

      // Auto-detect name if not provided
      if (empty($product['name'])) {
        $product['name'] = ucwords(str_replace('-', ' ', $product['slug']));
      }

      $this->products[$product['slug']] = (object) $product;
    }

    /**
     * Get product by slug
     *
     * @param string $slug Product slug
     * @return object|null
     */
    private function get_product($slug) {
      return isset($this->products[$slug]) ? $this->products[$slug] : null;
    }

    /**
     * Get remote product info from server
     *
     * @param string $slug Product slug
     * @param bool   $force Force refresh
     * @return object|null
     */
    private function get_remote_info($slug, $force = false) {
      $product = $this->get_product($slug);
      if (!$product) {
        return null;
      }

      $cache_key = 'bootscore_update_' . $slug;
      $cached = get_transient($cache_key);

      if ($cached !== false && !$force) {
        return $cached;
      }

      $url = $this->server_url . $product->server_path . '/info.json';
      $response = wp_remote_get($url, array('timeout' => 10));

      if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return null;
      }

      $data = json_decode(wp_remote_retrieve_body($response));

      if (!empty($data)) {
        set_transient($cache_key, $data, $this->cache_time);
      }

      return $data;
    }

    /**
     * Check if update is available
     *
     * @param string $slug Product slug
     * @param bool   $force Force check
     * @return object|false
     */
    public function check_for_update($slug, $force = false) {
      $product = $this->get_product($slug);
      if (!$product) {
        return false;
      }

      $remote = $this->get_remote_info($slug, $force);
      if (!$remote) {
        return false;
      }

      // Check if version is newer
      if (!version_compare($product->current_version, $remote->version, '<')) {
        return false;
      }

      // Check WordPress requirements
      if (isset($remote->requires) && version_compare(get_bloginfo('version'), $remote->requires, '<')) {
        return false;
      }

      // Check PHP requirements
      if (isset($remote->requires_php) && version_compare(PHP_VERSION, $remote->requires_php, '<')) {
        return false;
      }

      return $remote;
    }

    /**
     * Plugin info for "View Details" popup
     */
    public function plugin_info($res, $action, $args) {
      if ('plugin_information' !== $action) {
        return $res;
      }

      $product = $this->get_product($args->slug);
      if (!$product || $product->type !== 'plugin') {
        return $res;
      }

      $remote = $this->get_remote_info($args->slug);
      if (!$remote) {
        return $res;
      }

      $res = new stdClass();
      $res->name = $remote->name ?? $product->name;
      $res->slug = $remote->slug ?? $args->slug;
      $res->version = $remote->version ?? '0.0.0';
      $res->tested = $remote->tested ?? '';
      $res->requires = $remote->requires ?? '';
      $res->author = $remote->author ?? '';
      $res->author_profile = $remote->author_profile ?? '';
      $res->download_link = $remote->download_url ?? '';
      $res->trunk = $res->download_link;
      $res->requires_php = $remote->requires_php ?? '';
      $res->last_updated = $remote->last_updated ?? '';

      $res->sections = array(
        'description' => $remote->sections->description ?? 'No description available.',
        'installation' => $remote->sections->installation ?? '',
        'changelog' => $remote->sections->changelog ?? '',
      );

      if (!empty($remote->banners)) {
        $res->banners = array(
          'low' => $remote->banners->low ?? '',
          'high' => $remote->banners->high ?? '',
        );
      }

      return $res;
    }

    /**
     * Plugin updates
     */
    public function plugin_updates($transient) {
      if (empty($transient->checked)) {
        return $transient;
      }

      foreach ($this->products as $slug => $product) {
        if ($product->type !== 'plugin') {
          continue;
        }

        $update = $this->check_for_update($slug);
        if (!$update) {
          continue;
        }

        $res = new stdClass();
        $res->slug = $slug;
        $res->plugin = $product->file;
        $res->new_version = $update->version;
        $res->tested = $update->tested ?? '';
        $res->package = $update->download_url ?? '';

        if (isset($update->requires)) {
          $res->requires = $update->requires;
        }

        if (isset($update->requires_php)) {
          $res->requires_php = $update->requires_php;
        }

        $transient->response[$product->file] = $res;
      }

      return $transient;
    }

    /**
     * Theme updates
     */
    public function theme_updates($transient) {
      foreach ($this->products as $slug => $product) {
        if ($product->type !== 'theme') {
          continue;
        }

        $update = $this->check_for_update($slug);
        if (!$update) {
          continue;
        }

        $transient->response[$slug] = array(
          'theme' => $slug,
          'new_version' => $update->version,
          'package' => $update->download_url ?? '',
          'url' => $update->homepage ?? $this->server_url,
        );
      }

      return $transient;
    }

    /**
     * Clear cache after updates
     */
    public function clear_cache() {
      foreach ($this->products as $slug => $product) {
        delete_transient('bootscore_update_' . $slug);
      }
    }
  }
}
