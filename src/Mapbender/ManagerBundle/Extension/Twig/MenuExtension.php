<?php


namespace Mapbender\ManagerBundle\Extension\Twig;


use Mapbender\ManagerBundle\Component\ManagerBundle;
use Mapbender\ManagerBundle\Component\Menu\LegacyItem;
use Mapbender\ManagerBundle\Component\Menu\MenuItem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuExtension extends \Twig_Extension
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;
    /** @var MenuItem[] */
    protected $items;
    /** @var string[] (serialized items) */
    protected $itemData;
    /** @var bool */
    protected $initialized = false;
    /** @var array|null */
    protected $legacyInitArgs;
    /** @var RequestStack */
    protected $requestStack;


    /**
     * @param MenuItem[] $items
     * @param RequestStack $requestStack
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param KernelInterface $kernel
     * @param string[] $legacyBundleNames
     * @param string[] $routePrefixBlacklist
     */
    public function __construct($items,
                                RequestStack $requestStack,
                                AuthorizationCheckerInterface $authorizationChecker,
                                KernelInterface $kernel,
                                $legacyBundleNames,
                                $routePrefixBlacklist)
    {
        $this->itemData = $items;
        $this->requestStack = $requestStack;
        $this->authorizationChecker = $authorizationChecker;
        if ($legacyBundleNames) {
            $this->legacyInitArgs = array($kernel, $legacyBundleNames, $routePrefixBlacklist);
        }
    }

    public function getFunctions()
    {
        return array(
            'mapbender_manager_menu_items' => new \Twig_SimpleFunction('mapbender_manager_menu_items', array($this, 'mapbender_manager_menu_items')),
        );
    }

    public function mapbender_manager_menu_items($legacyParamDummy = null)
    {
        return $this->getItems(true);
    }

    public function getDefaultRoute()
    {
        $items = $this->getItems(false);
        if (!$items) {
            throw new \RuntimeException("No manager routes defined");
        }
        return $items[0]->getRoute();
    }

    /**
     * @param bool $filterAccess
     * @return MenuItem[]
     */
    protected function getItems($filterAccess)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $items = array();
        foreach ($this->items as $item) {
            if (!$filterAccess || $item->filter($this->authorizationChecker)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    protected function initialize()
    {
        $this->items = array_map('\unserialize', $this->itemData);
        if ($args = $this->legacyInitArgs) {
            $this->legacyInit($args[0], $args[1], $args[2]);
        }
        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        foreach ($this->items as $item) {
            $item->checkActive($route);
        }

        $this->initialized = true;
    }

    /**
     * @param KernelInterface $kernel
     * @param $bundleNames
     * @param $routePrefixBlacklist
     * @deprecated remove in v3.1, plus all related DI dependencies and attributes
     */
    protected function legacyInit(KernelInterface $kernel, $bundleNames, $routePrefixBlacklist)
    {
        foreach ($bundleNames as $legacyBundleName) {
            /** @var ManagerBundle $bundle */
            $bundle = $kernel->getBundle($legacyBundleName);
            foreach ($bundle->getManagerControllers() as $topLevelMenuDefinition) {
                $item = LegacyItem::fromArray($topLevelMenuDefinition);
                if (MenuItem::filterBlacklistedRoutes(array($item), $routePrefixBlacklist)) {
                    $this->items[] = $item;
                }
            }
        }
        $this->items = MenuItem::sortItems($this->items);
    }
}
