<?php


require_once(__DIR__ . '/ovesio/sdk/autoload.php');

use Ovesio\OvesioAI;
use Ovesio\QueueHandler;
use Opencart\System\Library\Log;

class Ovesio extends \Opencart\System\Engine\Model
{
    private $default_language_id;
    private $module_key = 'ovesio';

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->db->cache = false; //custom cache query disabled

        $default_language = $this->config->get($this->module_key . '_default_language');
        $config_language  = $this->config->get('config_language_catalog');

        if (stripos($default_language, $config_language) === 0 || $default_language == 'auto') {
            $default_language_id = $this->config->get('config_language_id');
        } else {
            $query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE code LIKE '" . $this->db->escape($default_language) . "%' LIMIT 1");

            if (empty($query->row['language_id'])) {
                throw new \Exception("Could not detect local default language based on language code '$default_language'");
            }

            $default_language_id = $query->row['language_id'];
        }

        $this->default_language_id = $default_language_id;
    }

    public function getDefaultLanguageId()
    {
        return $this->default_language_id;
    }

    public function getModuleKey()
    {
        return $this->module_key;
    }

    public function buildQueueHandler($manual = false)
    {
        $options = [];

        $this->load->model('setting/setting');

        $data = $this->model_setting_setting->getSetting($this->module_key);

        foreach ($data as $key => $value) {
            $value = $this->config->get($key); // ensure we get the latest config value
            $key = str_replace($this->module_key . '_', '', $key);
            $options[$key] = $value;
        }

        // Add additional options
        $options['server_url']          = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTP_CATALOG') ? HTTP_CATALOG : (defined('HTTPS_SERVER') ? HTTPS_SERVER : (defined('HTTP_SERVER') ? HTTP_SERVER : '')));
        $options['default_language_id'] = $this->default_language_id;
        $options['manual']              = $manual;

        $api_url   = $this->config->get($this->module_key . '_api_url');
        $api_token = $this->config->get($this->module_key . '_api_token');

        $api = new OvesioAI($api_token, $api_url);

        $this->load->model('extension/ovesio/module/ovesio');
        $model = $this->model_extension_ovesio_module_ovesio;

        return new QueueHandler(
            $model,
            $api,
            $options,
            new Log('ovesio_queue.log')
        );
    }
}