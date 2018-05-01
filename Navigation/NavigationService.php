<?php declare(strict_types=1);
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Storefront\Navigation;

use Shopware\Api\Category\Repository\CategoryRepository;
use Shopware\Api\Category\Struct\CategorySearchResult;
use Shopware\Api\Entity\Search\Criteria;
use Shopware\Api\Entity\Search\Query\TermQuery;
use Shopware\Api\Entity\Search\Query\TermsQuery;
use Shopware\Category\Tree\TreeBuilder;
use Shopware\Context\Struct\StorefrontContext;
use Shopware\Defaults;

class NavigationService
{
    /**
     * @var CategoryRepository
     */
    private $repository;

    /**
     * @var Navigation[]
     */
    private $navigation;

    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function load(?string $categoryId, StorefrontContext $context): ?Navigation
    {
        if (!$categoryId) {
            return null;
        }

        if ($this->navigation[$categoryId]) {
            return $this->navigation[$categoryId];
        }

        $activeCategory = $this->repository->readBasic([$categoryId], $context->getApplicationContext())
            ->get($categoryId);

        $systemCategoryId = Defaults::ROOT_CATEGORY;

        if (!$activeCategory) {
            return null;
        }

        $ids = array_merge($activeCategory->getPathArray(), [$activeCategory->getId()]);

        $criteria = new Criteria();
        $criteria->addFilter(new TermsQuery('category.parentId', $ids));
        $criteria->addFilter(new TermQuery('category.active', 1));

        /** @var CategorySearchResult $categories */
        $categories = $this->repository->search($criteria, $context->getApplicationContext());

        $tree = TreeBuilder::buildTree(
            $systemCategoryId,
            $categories->sortByPosition()->sortByName()
        );

        $navigation = new Navigation($activeCategory, $tree);

        $this->navigation[$categoryId] = $navigation;

        return $navigation;
    }
}
