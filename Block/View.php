<?php

namespace Zero1\HotTags\Block;

use Zero1\HotTags\Model\Source\Trigger as SourceModelTrigger;
use Zero1\HotTags\Model\Source\Trigger as SourceModelTime;
use Magento\Framework\App\ResourceConnection as ResourceConnection;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */

    /**
     * @var SourceModelTrigger
     */
    public $sourceModelTrigger;

    /**
     * @var SourceModelTime
     */
    public $sourceModelTime;

    protected $_resourceConnection;

    protected $_connection;

    protected $_storeManager;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Product
     */
    private $product;

    public function __construct(
        SourceModelTrigger $sourceModelTrigger,
        SourceModelTime $sourceModelTime,
        ResourceConnection $resourceConnection,
        Registry $registry,
        StoreManager $storeManager,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->sourceModelTrigger = $sourceModelTrigger;
        $this->sourceModelTime = $sourceModelTime;
        $this->_resourceConnection = $resourceConnection;
        $this->registry = $registry;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function getConfigValue($group, $path)
    {
        return $this->_scopeConfig->getValue('hottags/' . $group . '/' . $path);
    }

    public function getConfigFlag($group, $path)
    {
        return $this->_scopeConfig->isSetFlag('hottags/' . $group . '/' . $path);
    }

    public function getTriggerType()
    {
        return $this->getConfigValue('frontend','trigger');
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->registry->registry('product');
            if (!$this->product->getId()) {
                throw new LocalizedException(__('Failed to initialize product'));
            }
        }
        return $this->product;
    }

    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    public function getTimescale()
    {
        $timescale = '1 DAY';
        if ($this->getConfigValue('frontend','triggertimescale') == 'last_hour') {
            $timescale = '1 HOUR';
        } else if ($this->getConfigValue('frontend','triggertimescale') == 'this_week') {
            $timescale = '1 WEEK';
        }
        return $timescale;
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getProductsTriggeredCount()
    {
        $productId = $this->getProductId();
        $timescale = $this->getTimescale();
        $storeId = $this->getStoreId();
        $triggeredProducts = 0;
        // TODO in a future version
        // Add purchased option $triggeredProducts = $this->getProductPurchasedCount($productId,$timescale,$storeId);
        if ($this->getTriggerType() == 'viewed') {
            $triggeredProducts = $this->getProductViewedCount($productId,$timescale,$storeId);
        } else {
            $triggeredProducts = $this->getProductAddedToCartCount($productId,$timescale,$storeId);
        }
        if ($triggeredProducts > 0) {
            return $triggeredProducts;
        }
        return $triggeredProducts;
    }

    public function getProductAddedToCartCount($productId, $timescale, $storeId)
    {	
        $this->_connection = $this->_resourceConnection->getConnection();
        $query = "SELECT * FROM report_event WHERE event_type_id = (select event_type_id from report_event_types WHERE event_name='checkout_cart_add_product') AND object_id=" . $productId . " and store_id=" . $storeId . " AND logged_at > (CURDATE() - INTERVAL " . $timescale . ")";
        $collection = $this->_connection->fetchAll($query);
        return count($collection);
    }

    public function getProductViewedCount($productId, $timescale, $storeId)
    {	
        $this->_connection = $this->_resourceConnection->getConnection();
        $query = "SELECT * FROM report_event WHERE event_type_id = (select event_type_id from report_event_types WHERE event_name='catalog_product_view') AND object_id=" . $productId . " and store_id=" . $storeId . " AND logged_at > (CURDATE() - INTERVAL " . $timescale . ")";
        $collection = $this->_connection->fetchAll($query);
        return count($collection);
    }

    /* TODO in a future version
    public function getProductPurchasedCount($productId, $timescale, $storeId)
    {	
        return 'TODO';
    }
    */

    public function getMinimalTriggerAmount()
    {
        if (is_int($this->getConfigValue('frontend','minimum_triggers'))) {
            return $this->getConfigValue('frontend','minimum_triggers');
        }
        return 10;
    }

    public function shouldShowTag()
    {
        if ($this->getProductsTriggeredCount() >= $this->getMinimalTriggerAmount()) {
            return true;
        }
        return false;
    }

    public function getTagPreText()
    {
        if ($this->getConfigValue('frontend','show_tag_pre_text')) {
            return '<span>' . $this->getConfigValue('frontend','tag_pre_text') . '</span>';
        }
        return '';
    }

    public function getTriggerText()
    {
        return $this->getConfigValue('frontend','triggertext');
    }

    public function getTriggerTimescaleText()
    {
        return $this->getConfigValue('frontend','triggertimescaletext');
    }

    public function getTagText()
    {
        $tagPreText = $this->getTagPreText();
        $tagText = $this->getConfigValue('frontend','tag_text');
        $tagText = preg_replace("/%amount%/", $this->getProductsTriggeredCount(), $tagText);
        $tagText = preg_replace("/%triggertext%/", $this->getTriggerText(), $tagText);
        $tagText = preg_replace("/%triggertimescaletext%/", $this->getTriggerTimescaleText(), $tagText);
        return $tagPreText . $tagText;
    }

    public function isEnabled()
    {
        return $this->getConfigFlag('general','active');
    }

    public function isAnimationRequired()
    {
        if (($this->getConfigValue('frontend','show_tag_after') != '') || ($this->getConfigValue('frontend','hide_tag_after') != '')) {
            return true;
        }
        return false;
    }

    public function getShowTagAfterSeconds()
    {
        if ($this->getConfigValue('frontend','show_tag_after')) {
            return $this->getConfigValue('frontend','show_tag_after');
        }
        return '2';
    }

    public function getHideTagAfterSeconds()
    {
        if ($this->getConfigValue('frontend','hide_tag_after') != '') {
            return $this->getConfigValue('frontend','hide_tag_after');
        }
        return null;
    }

    public function getTagStyles()
    {
        $backgroundColour = $this->getConfigValue('design','background_colour');
        $textColour = $this->getConfigValue('design','text_colour');
        $borderColour = $this->getConfigValue('design','border_colour');
        $styles = '';
        if ($backgroundColour != '') {
            $styles .= 'background: ' . $backgroundColour . '; ';
        }
        if ($textColour != '') {
            $styles .= 'color: ' . $textColour . '; ';
        }
        if ($borderColour != '') {
            $styles .= 'border: 1px solid ' . $borderColour . '; ';
        }
        return $styles;
    }

    public function getCustomCss()
    {
        $customCssFromAdmin = $this->getConfigValue('design','custom_css');
        $customCss = '';
        if ($customCssFromAdmin != '') {
            $customCss = '<style>' . $customCssFromAdmin . '</style>';
        }
        return $customCss;
    }

}