<?php

declare(strict_types=1);

namespace Chessio\Matomo\Plugin\Magento\Framework\App\View;

class AddSearchResultTracking
{
    public const SEARCH_RESULT_PAGE_FULL_ACTION_NAME = 'catalogsearch_result_index';
    
    public function __construct(
        protected \Chessio\Matomo\Model\Tracker $matomoTracker,
        protected \Chessio\Matomo\Helper\Data $dataHelper,
        protected \Magento\Search\Model\QueryFactory $queryFactory,
        protected \Magento\Framework\View\Result\Layout $layout,
        protected \Magento\Framework\App\RequestInterface $request
    ) {}

    /**
     * Push `trackSiteSearch' to tracker on search result page
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderLayout(\Magento\Framework\App\View $subject, \Magento\Framework\App\View $result): \Magento\Framework\App\View
    {
        if (!$this->dataHelper->isTrackingEnabled() || $this->request->getFullActionName() !== self::SEARCH_RESULT_PAGE_FULL_ACTION_NAME) {
            return $result;
        }

        $query = $this->queryFactory->get();
        $matomoBlock = $this->layout->getLayout()->getBlock('matomo.tracker');
        /** @var \Magento\Search\Model\Query $query */
        /** @var \Chessio\Matomo\Block\Matomo $matomoBlock */

        $keyword = $query->getQueryText();
        $resultsCount = $query->getNumResults();

        if ($resultsCount === null) {
            // If this is a new search query the result count hasn't been saved
            // yet so we have to fetch it from the search result block instead.
            $resultBock = $this->layout->getLayout()->getBlock('search.result');
            /** @var \Magento\CatalogSearch\Block\Result $resultBock */
            if ($resultBock) {
                $resultsCount = $resultBock->getResultCount();
            }
        }

        if ($resultsCount === null) {
            $this->matomoTracker->trackSiteSearch($keyword);
        } else {
            $this->matomoTracker->trackSiteSearch(
                $keyword,
                false,
                (int) $resultsCount
            );
        }

        if ($matomoBlock) {
            // Don't push `trackPageView' when `trackSiteSearch' is set
            $matomoBlock->setSkipTrackPageView(true);
        }

        return $result;
    }
}
