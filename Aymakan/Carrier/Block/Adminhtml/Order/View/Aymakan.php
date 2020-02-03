<?php

namespace Aymakan\Carrier\Block\Adminhtml\Order\View;

use Aymakan\Carrier\Helper\Api;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CacheInterface;

class Aymakan extends Generic
{
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param EncoderInterface $jsonEncoder
     * @param ScopeConfigInterface $scopeConfig
     * @param Order $order
     * @param CacheInterface $cache
     * @param Api $api
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        EncoderInterface $jsonEncoder,
        ScopeConfigInterface $scopeConfig,
        Order $order,
        CacheInterface $cache,
        Api $api,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->urlBuilder  = $context->getUrlBuilder();
        $this->jsonEncoder = $jsonEncoder;
        $this->scopeConfig = $scopeConfig;
        $this->order       = $order->load($this->_request->getParam('order_id'));
        $this->setUseContainer(true);
        $this->api = $api;
        $this->cache = $cache;
    }

    /**
     * Form preparation
     *
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var Form $form */
        $form = $this->_formFactory->create([
            'data' => [
                'action' => $this->getUrl('aymakan'),
                'id' => 'aymakan_shipping_form',
                'class' => 'admin__scope-old',
                'enctype' => 'multipart/form-data',
                'method' => 'post'
            ]
        ]);

        $form->setUseContainer($this->getUseContainer());
        $form->addField('aymakan_modal_messages', 'note', []);
        $fieldset = $form->addFieldset('aymakan_shipping_form_fieldset_1', [
            'class' => 'fieldset-column',
            'legend' => __('Customer Address Information')
        ]);
        $fieldset->addField(
            '',
            'hidden',
            [
                'name' => 'form_key',
                'value' => $this->getFormKey(),
            ]
        );
        $fieldset->addField(
            'delivery_order_id',
            'hidden',
            [
                'name' => 'id',
                'value' => $this->order->getId(),
            ]
        );
        $fieldset->addField(
            'delivery_country',
            'hidden',
            [
                'name' => 'delivery_country',
                'value' => $this->getAddress()->getCountryId(),
            ]
        );
        $fieldset->addField(
            'delivery_name',
            'text',
            [
                'class' => 'edited-data validate',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
                'name' => 'delivery_name',
                'value' => $this->getAddress()->getFirstname(),
            ]
        );
        $fieldset->addField(
            'delivery_email validate-email',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Email'),
                'title' => __('Email'),
                'required' => true,
                'name' => 'delivery_email',
                'value' => $this->order->getCustomerEmail(),
            ]
        );
        $fieldset->addField(
            'delivery_address validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Address'),
                'title' => __('Address'),
                'required' => true,
                'name' => 'delivery_address',
                'value' => $this->getAddress()->getStreetLine(1),
            ]
        );
        $fieldset->addField(
            'delivery_region',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Region'),
                'title' => __('Region'),
                'name' => 'delivery_region',
                'value' => $this->getAddress()->getRegion(),
            ]
        );
        $fieldset->addField(
            'delivery_phone validate-phone',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Phone'),
                'title' => __('Phone'),
                'required' => true,
                'name' => 'delivery_phone',
                'value' => $this->getAddress()->getTelephone(),
            ]
        );
        $fieldset->addField(
            'delivery_postcode validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Postcode'),
                'title' => __('Postcode'),
                'name' => 'delivery_postcode',
                'value' => $this->getAddress()->getPostcode(),
            ]
        );
        $fieldset->addField(
            'delivery_city validate',
            'select',
            [
                'class' => 'edited-data',
                'label' => __('City'),
                'title' => __('City'),
                'required' => false,
                'name' => 'delivery_city',
                'values' => $this->getCities(),
                'note' => 'Aymakan deliver to specific cities only. Each city has its specific namings as listed in Aymakan documentation.'
            ]
        );
        $fieldset = $form->addFieldset('aymakan_shipping_form_fieldset_2', [
            'class' => 'fieldset-column',
            'legend' => __('Shipping Information')
        ]);

        $fieldset->addField(
            'delivery_reference validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Reference'),
                'title' => __('Reference'),
                'required' => true,
                'name' => 'reference',
                'value' => $this->order->getIncrementId()
            ]
        );
        $fieldset->addField(
            'declared_value validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Order Total'),
                'title' => __('Order Total'),
                'required' => true,
                'name' => 'declared_value',
                'value' => round($this->order->getGrandTotal())
            ]
        );
        $fieldset->addField(
            'deliver_items validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Items'),
                'title' => __('Items'),
                'required' => true,
                'name' => 'items',
                'note' => 'Number of items in the shipment.'
            ]
        );
        $fieldset->addField(
            'deliver_pieces validate',
            'text',
            [
                'class' => 'edited-data',
                'label' => __('Pieces'),
                'title' => __('Pieces'),
                'required' => true,
                'name' => 'pieces',
                'note' => 'Pieces in the shipment. For example, for a large orders, the items can be boxed in multiple cottons. The number of boxed cottons should be entered here. ',
            ]
        );

        $this->setForm($form);
    }

    /**
     * Get widget options
     *
     * @return string
     */
    public function getWidgetOptions()
    {
        return $this->jsonEncoder->encode(
            [
                'saveVideoUrl' => $this->getUrl('catalog/product_gallery/upload'),
                'saveRemoteVideoUrl' => $this->getUrl('product_video/product_gallery/retrieveImage'),
                'htmlId' => $this->getHtmlId(),
            ]
        );
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderAddressInterface|Order\Address|null
     */
    public function getAddress()
    {
        $billingAddress = $this->order->getBillingAddress();
        return (!isset($billingAddress)) ? $this->order->getShippingAddress() : $billingAddress;
    }

    /**
     * @return array
     */
    public function getCities()
    {
        $key = 'aymakan_cities';
        $fromCache = $this->cache->load($key);
        if (!$fromCache)
        {
            $cities = $this->api->getCities();
            $options = [];

            if (count($cities) > 0) {
                foreach ($cities as $city) {
                    $options[$city['city_en']] = addslashes($city['city_en']);
                }
            }
            $this->cache->save(json_encode($options), $key);
            $fromCache = $this->cache->load($key);
        }
        $options = json_decode($fromCache);
        return $options;
    }


    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
