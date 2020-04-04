<?php
/**
 * Copyright © Acesofspades. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Aos\CustomerView\Block\Adminhtml\Category\Edit\Button;

use Aos\CustomerView\Block\Adminhtml\UrlBuilder;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Block\Template\Context;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Catalog\Block\Adminhtml\Category\AbstractCategory;
use Aos\CustomerView\Block\Adminhtml\RewriteResolver;
use Magento\Catalog\Model\ResourceModel\Category\Tree;
use Magento\Catalog\Model\CategoryFactory;

/**
 * Class CustomerView
 *
 * @package Aos\Customerview\Block\Adminhtml\Category\Edit\Button
 */
class CustomerView extends AbstractCategory implements ButtonProviderInterface
{
    /**
     * @var UrlBuilder
     */
    protected $actionUrlBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RewriteResolver
     */
    protected $rewriteResolver;

    /**
     * CustomerView constructor.
     *
     * @param Context $context
     * @param Tree $categoryTree
     * @param Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlBuilder $actionUrlBuilder
     * @param RewriteResolver $rewriteResolver
     * @param array $data
     */
    public function __construct(
        Context $context,
        Tree $categoryTree,
        Registry $registry,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        UrlBuilder $actionUrlBuilder,
        RewriteResolver $rewriteResolver,
        array $data = []
    ) {
        $this->storeManager     = $storeManager;
        $this->actionUrlBuilder = $actionUrlBuilder;
        $this->rewriteResolver  = $rewriteResolver;

        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
    }

    /**
     * Get button data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getButtonData()
    {
        $urls = $this->getCustomerViewUrls();

        $buttonData = [
            'label'      => __('Customer View'),
            'class'      => '',
            'on_click'   => !empty($urls) ? sprintf("window.open('%s', '_blank');", reset($urls)) : null,
            'class_name' => \Aos\CustomerView\Ui\Component\Control\SplitButton::class,
            'options'    => $this->getOptions(),
            'sort_order' => -10
        ];

        $category = $this->getCategory();
        if (!$category->getIsActive() || !$category->getId() || empty($urls)) {
            $buttonData['disabled'] = 'disabled';
        }

        return $buttonData;
    }

    /**
     * Get options
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getOptions()
    {
        $options = [];

        $urls = $this->getCustomerViewUrls();

        if (!empty($urls)) {
            foreach ($urls as $rewrite => $url) {
                $options[] = [
                    'id_hard' => $rewrite,
                    'label'   => __($rewrite),
                    'onclick' => sprintf("window.open('%s', '_blank');", $url),
                ];
            }
        }

        return $options;
    }

    /**
     * Get url rewrites
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUrlRewrites()
    {
        $urlRewrites = [];

        $select = $this->getConnection()
                       ->select()
                       ->from(['u' => $this->getTable('url_rewrite')], ['u.entity_id', 'u.request_path'])
                       ->where('u.store_id = ?', $this->getStore()->getId())
                       ->where('u.is_autogenerated = 1')
                       ->where('u.entity_type = ?', CategoryUrlRewriteGenerator::ENTITY_TYPE)
                       ->where('u.entity_id = ?', $this->getCategory()->getId());

        foreach ($this->getConnection()->fetchAll($select) as $row) {
            $urlRewrites[] = $row['request_path'];
        }

        return $urlRewrites;
    }

    /**
     * Get customer view urls
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerViewUrls()
    {
        $url = [];

        /* @var \Magento\Store\Model\Store\Interceptor */
        $currentStore = $this->rewriteResolver->getStore();

        //pobierz urle do request_path
        $urlRewrites = $this->rewriteResolver->getUrlRewrites(
            $this->getCategory()->getId(),
            CategoryUrlRewriteGenerator::ENTITY_TYPE
        );

        if (!empty($urlRewrites)) {
            foreach ($urlRewrites as $rewrite) {
                $url[$rewrite] = $this->actionUrlBuilder->getUrl(
                    $rewrite,
                    $currentStore
                );
            }
        }

        return $url;
    }
}
