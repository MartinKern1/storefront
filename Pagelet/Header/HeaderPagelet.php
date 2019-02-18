<?php declare(strict_types=1);

namespace Shopware\Storefront\Pagelet\Header;

use Shopware\Core\Framework\DataAbstractionLayer\Util\Tree\Tree;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\Currency\CurrencyCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;

class HeaderPagelet extends Struct
{
    /**
     * @var \Shopware\Core\Framework\DataAbstractionLayer\Util\Tree\Tree
     */
    private $navigation;

    /**
     * @var LanguageCollection
     */
    private $languages;

    /**
     * @var CurrencyCollection
     */
    private $currencies;

    /**
     * @var LanguageEntity
     */
    private $activeLanguage;

    /**
     * @var CurrencyEntity
     */
    private $activeCurrency;

    public function __construct(
        Tree $navigation,
        LanguageCollection $languages,
        CurrencyCollection $currencies,
        LanguageEntity $activeLanguage,
        CurrencyEntity $activeCurrency
    ) {
        $this->navigation = $navigation;
        $this->languages = $languages;
        $this->currencies = $currencies;
        $this->activeLanguage = $activeLanguage;
        $this->activeCurrency = $activeCurrency;
    }

    public function getNavigation(): Tree
    {
        return $this->navigation;
    }

    public function getLanguages(): LanguageCollection
    {
        return $this->languages;
    }

    public function getCurrencies(): CurrencyCollection
    {
        return $this->currencies;
    }

    public function getActiveLanguage(): LanguageEntity
    {
        return $this->activeLanguage;
    }

    public function getActiveCurrency(): CurrencyEntity
    {
        return $this->activeCurrency;
    }
}
