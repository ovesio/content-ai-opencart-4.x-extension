<?php

namespace Opencart\Admin\Controller\Extension\Ovesio\Module;

class Event extends \Opencart\System\Engine\Controller
{
    private $token = 'user_token';
    private $module_key = 'module_ovesio';

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function trigger($route, $data, $resource_id = null)
    {
        $status = $this->config->get($this->module_key . '_status');
        if(!$status) {
            return;
        }

        $temp = explode('/', $route);
        $action = explode('.', $temp[1]);
        $resource = $action[0];
        if (strpos($action[1], 'edit') === 0) {
            $resource_id = $data[0];
        }

        if(!in_array($resource, ['product', 'category', 'attribute_group', 'attribute', 'option'])) {
            return;
        }

        $this->load->model('extension/ovesio/module/ovesio');

        if ($resource == 'attribute') {
            $this->load->model('catalog/attribute');

            $attribute_group_id = $this->model_extension_ovesio_module_ovesio->getAttributeGroupId($resource_id);

            $resource    = 'attribute_group';
            $resource_id = $attribute_group_id;
        }

        $this->model_extension_ovesio_module_ovesio->setStale($resource, $resource_id, 1);
    }

    public function header_after(string &$route, array &$data, mixed &$output): void
    {
        if (isset($this->request->get['route'])) {
            $current_route = $this->request->get['route'];

            $ovesio_route_resource = [
                'catalog/product'         => 'products',
                'catalog/category'        => 'categories',
                'catalog/attribute_group' => 'attributes',
                'catalog/attribute'       => 'attributes',
                'catalog/option'          => 'options',
            ];

            if (isset($ovesio_route_resource[$current_route])) {
                $resource = $ovesio_route_resource[$current_route];
                $token_qs = 'user_token=' . $this->session->data['user_token'];

                $this->load->language('extension/ovesio/module/ovesio');

                $ovesio_status                  = $this->config->get('module_ovesio_status');
                $ovesio_generate_content_status = $this->config->get('module_ovesio_generate_content_status');
                $ovesio_generate_seo_status     = $this->config->get('module_ovesio_generate_seo_status');
                $ovesio_translate_status        = $this->config->get('module_ovesio_translate_status');

                $config = [];
                $config['resource'] = $resource;
                $config['route'] = $current_route;
                $config['manualUrl'] = $this->url->link('extension/ovesio/module/manual', 'from=' . $current_route . '&' . $token_qs, true);

                $config['status'] = [
                    'content'   => $ovesio_status && $ovesio_generate_content_status && in_array($resource, ['products', 'categories']),
                    'seo'       => $ovesio_status && $ovesio_generate_seo_status && in_array($resource, ['products', 'categories']),
                    'translate' => $ovesio_status && $ovesio_translate_status,
                ];

                $config['text'] = [
                    'content'   => $this->language->get('text_generate_content_with_ovesio'),
                    'seo'       => $this->language->get('text_generate_seo_with_ovesio'),
                    'translate' => $this->language->get('text_translate_with_ovesio'),
                ];

                // Inject script and config
                $script_inject = '<link href="../extension/ovesio/admin/view/stylesheet/ovesio.css" type="text/css" rel="stylesheet" media="screen" />';
                $script_inject .= '<script>var ovesioConfig = ' . json_encode($config) . ';</script>';
                $script_inject .= '<script src="../extension/ovesio/admin/view/javascript/ovesio.js" type="text/javascript"></script>';
                $script_inject .= '<script src="../extension/ovesio/admin/view/javascript/ovesio_inject.js" type="text/javascript"></script>';

                $output = str_replace('</head>', $script_inject . "\n</head>", $output);
            }
        }
    }

    public function column_left_before(string &$route, array &$data, mixed &$output): void
    {
        if ($this->user->hasPermission('access', 'extension/ovesio/module/ovesio')) {
            $name = 'Ovesio - Content AI';

            $this->load->model('extension/ovesio/module/ovesio');
            // Assuming model has been loaded as model_extension_ovesio_module_ovesio
            $count_errors = $this->model_extension_ovesio_module_ovesio->getActivitiesTotal(['status' => 'error']);
            // $count_errors = 1; // Debug line removed
            if ($count_errors) {
                $name .= ' <span class="badge badge-danger pull-right">' . $count_errors . '</span>';
            }

            $data['menus'][] = [
                'id'       => 'menu-ovesio-list',
                'icon'     => 'fa-android',
                'name'     => $name,
                'href'     => $this->url->link('extension/ovesio/module/ovesio.activityList', $this->tokenQs()),
                'children' => [],
            ];
        }
    }

    private function tokenQs()
    {
        return $this->token .'=' . $this->session->data[$this->token];
    }
}
