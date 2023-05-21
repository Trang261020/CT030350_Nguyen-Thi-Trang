<?php

namespace Ecommage\CustomCatalog\Helper;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Url\Helper\Data as UrlHelper;

class PostHelper extends AbstractHelper
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var RedirectInterface
     */
    protected $redirect;
    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var \Amasty\Preorder\ViewModel\Product\ProductList\Preorder
     */
    protected $viewModel;

    /**
     * @var JsonSerializer
     */
    protected $serializer;

    /**
     * PostHelper constructor.
     *
     * @param RedirectInterface $redirect
     * @param UrlHelper         $urlHelper
     * @param Context           $context
     */
    public function __construct(
        \Amasty\Preorder\ViewModel\Product\ProductList\Preorder $viewModelProduct,
        RedirectInterface $redirect,
        JsonSerializer $serializer,
        UrlHelper $urlHelper,
        Registry $registry,
        Context $context
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
        $this->viewModel = $viewModelProduct;
        $this->redirect  = $redirect;
        $this->urlHelper = $urlHelper;
        $this->registry  = $registry;
    }

    /**
     * get data for post by javascript in format acceptable to $.mage.dataPost widget
     *
     * @param string $url
     * @param array  $data
     *
     * @return string
     */
    public function getPostData($url, array $data = [])
    {

        if (!isset($data[ActionInterface::PARAM_NAME_URL_ENCODED])) {
            $redirectUrl = $this->redirect->getRedirectUrl();
            $product = $this->getCurrentProduct();
            if ($product) {
                $redirectUrl   = $product->getUrlModel()->getUrl($product);
            }

            $urlReject = $this->urlHelper->getEncodedUrl($redirectUrl);
            $uenc = $this->getUencUrl($url);
            if ($uenc) {
                $url = str_replace($uenc, $urlReject, $url);
            }

            $data[ActionInterface::PARAM_NAME_URL_ENCODED] = $urlReject;
        }

        return json_encode(['action' => $url, 'data' => $data]);
    }

    /**
     * @param $url
     *
     * @return string|null
     */
    public function getUencUrl($url)
    {
        $params = explode('/', $url);
        $key = array_search('uenc', $params) + 1;
        return $params[$key] ?? null;
    }

    /**
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return \Amasty\Preorder\ViewModel\Product\ProductList\Preorder
     */
    public function getViewModelProduct()
    {
        return $this->viewModel;
    }

    /**
     * @param $jsonConfig
     *
     * @return array|bool|float|int|mixed|string|null
     */
    public function getTitleAddToCart($jsonConfig)
    {
        return $this->serializer->unserialize($jsonConfig);
    }

    /**
     * @param $product
     * @param $data
     *
     * @return false
     */
    public function getButtonAddToCart($product,$data)
    {
        if(!empty($product->getEntityId()) && !empty($data) && array_key_exists($product->getEntityId(),$data))
        {
            if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) return $data[$product->getEntityId()]['cart_label'] ?? null;
            if ( array_key_exists($product->getTypeId(),$data[$product->getEntityId()]) &&
                 !empty($data[$product->getEntityId()][$product->getTypeId()]['isAllProductsPreorder'])
            )
            {
                return $data[$product->getEntityId()][$product->getTypeId()]['addToCartLabel'];
            }
        }
        return  false;
    }
}
