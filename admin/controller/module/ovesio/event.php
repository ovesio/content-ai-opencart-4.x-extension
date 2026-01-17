<?php

class ControllerExtensionModuleOvesioEvent extends Controller
{
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
        $resource = $temp[1];
        if (strpos($temp[2], 'edit') === 0) {
            $resource_id = $data[0];
        }

        if(!in_array($resource, ['product', 'category', 'attribute_group', 'attribute', 'option'])) {
            return;
        }

        $this->load->model('extension/ovesio/module/ovesio');
        $this->model_extension_ovesio_module_ovesio->addList($data);

        if ($resource == 'attribute') {
            $this->load->model('catalog/attribute');

            $attribute_group_id = $this->model_extension_ovesio_module_ovesio->getAttributeGroupId($resource_id);

            $resource    = 'attribute_group';
            $resource_id = $attribute_group_id;
        }

        $this->model_extension_ovesio_module_ovesio->setStale($resource, $resource_id, 1);
    }
}
