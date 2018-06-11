<?php declare(strict_types=1);
/**
 * Shopware\Core 5
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
 * "Shopware\Core" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Storefront\Twig;

use Shopware\Core\Checkout\CustomerContext;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Theme\ThemeConfigReader;
use Shopware\Core\System\Config\Util\ConfigServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class TemplateDataExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ConfigServiceInterface
     */
    private $configService;

    /**
     * @var ThemeConfigReader
     */
    private $themeConfigReader;

    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack,
        ConfigServiceInterface $configService,
        ThemeConfigReader $themeConfigReader
    ) {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->configService = $configService;
        $this->themeConfigReader = $themeConfigReader;
    }

    public function getFunctions(): array
    {
        return [
            new \Twig_Function('snippet', function ($snippet, $namespace = null) {
                return $this->translator->trans($snippet, [], $namespace);
            }),
        ];
    }

    public function getGlobals(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [];
        }

        /** @var CustomerContext $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_STOREFRONT_CONTEXT_OBJECT);

        if (!$context) {
            return [];
        }

        $controllerInfo = $this->getControllerInfo($request);

        return [
            'shopware' => [
                'config' => array_merge(
                    $this->getDefaultConfiguration(),
                    $this->configService->get($context->getTouchpoint()->getId(), null)
                ),
                'theme' => $this->getThemeConfig(),
            ],
            'controllerName' => $controllerInfo->getName(),
            'controllerAction' => $controllerInfo->getAction(),
            'context' => $context,
            'activeRoute' => $request->attributes->get('_route'),
        ];
    }

    /**
     * @return array
     */
    protected function getThemeConfig(): array
    {
        $themeConfig = $this->themeConfigReader->get();

        $themeConfig = array_merge(
            $themeConfig,
            [
                'desktopLogo' => 'bundles/storefront/src/img/logos/logo--tablet.png',
                'tabletLandscapeLogo' => 'bundles/storefront/src/img/logos/logo--tablet.png',
                'tabletLogo' => 'bundles/storefront/src/img/logos/logo--tablet.png',
                'mobileLogo' => 'bundles/storefront/src/img/logos/logo--mobile.png',
                'ajaxVariantSwitch' => true,
                'offcanvasCart' => true,
            ]
        );

        return $themeConfig;
    }

    private function getDefaultConfiguration(): array
    {
        return [
            'showBirthdayField' => true,
        ];
    }

    private function getControllerInfo(Request $request): ControllerInfo
    {
        $controllerInfo = new ControllerInfo();
        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return $controllerInfo;
        }

        $matches = [];
        preg_match('/Controller\\\\(\w+)Controller::?(\w+)$/', $controller, $matches);

        if ($matches) {
            $controllerInfo->setName($matches[1]);
            $controllerInfo->setAction($matches[2]);
        }

        return $controllerInfo;
    }
}
