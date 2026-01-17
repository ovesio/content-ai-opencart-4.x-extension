<?php

namespace Opencart\Catalog\Controller\Extension\Ovesio\Module\Ovesio;

use Ovesio\QueueHandler;

require_once(DIR_EXTENSION . 'ovesio/system/library/ovesio/sdk/autoload.php');

class Cronjob extends \Opencart\System\Engine\Controller
{
    private $module_key = 'module_ovesio';
    // private $model; // In OC4 properties are dynamic or use $this->model_...

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('extension/ovesio/module/ovesio');
        // $this->model = $this->model_extension_ovesio_module_ovesio; // Shortcut if needed, but better use full name
    }

    public function index()
    {
        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        $resource_type = !empty($this->request->get['resource_type']) ? $this->request->get['resource_type'] : null;
        $resource_id   = !empty($this->request->get['resource_id']) ? (int) $this->request->get['resource_id'] : null;
        $limit         = !empty($this->request->get['limit']) ? (int) $this->request->get['limit'] : 20;

        $status = 0;
        $status += (bool) $this->config->get($this->module_key . '_generate_content_status');
        $status += (bool) $this->config->get($this->module_key . '_generate_seo_status');
        $status += (bool) $this->config->get($this->module_key . '_translate_status');

        if ($status == 0) {
            return $this->setOutput(['error' => 'All operations are disabled']);
        }

        /**
         * @var QueueHandler
         */
        // $this->ovesio is loaded via library? OC4 libraries are different.
        // The original code tried $this->load->library('ovesio');
        // If 'ovesio' library is in system/library/ovesio.php, it's loaded into registry.
        // Check if library file exists and how it's registered.
        // Assuming $this->registry->get('ovesio') works if loaded.

        // However, we are in extension directory.
        // OC4 Loading library from extension: $this->load->library('extension/ovesio/ovesio'); -> $this->ovesio (if class name matches)

        // We need to ensure library is loaded.

        // Let's assume queue handler building is same.
        $this->load->library('extension/ovesio/ovesio');
        $queue_handler = $this->ovesio->buildQueueHandler();

        $list = $queue_handler->processQueue([
            'resource_type' => $resource_type,
            'resource_id'   => $resource_id,
            'limit'         => $limit,
        ]);

        $queue_handler->showDebug();

        echo "Entries found: " . count($list);
    }

    /**
     * Custom response
     */
    private function setOutput($response)
    {
        if (is_array($response)) {
            $response = json_encode($response);

            $this->response->addHeader('Content-Type: application/json');
        }

        $this->response->setOutput($response);
    }
}
