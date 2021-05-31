<?php


namespace Mapbender\FrameworkBundle\Component\Renderer;

use Mapbender\Component\Enumeration\ScreenTypes;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;


/**
 * Default implementation for service mapbender.renderer.element_markup
 * Produces HTML markup for elements
 * Deals exclusively with Element Entity, never Component\Entity
 */
class ElementMarkupRenderer
{
    /** @var EngineInterface */
    protected $templatingEngine;
    /** @var bool */
    protected $allowResponsiveElements;
    /** @var ElementFactory */
    protected $elementFactory;

    public function __construct(EngineInterface $templatingEngine,
                                ElementFactory $elementFactory,
                                $allowResponsiveElements)
    {
        $this->templatingEngine = $templatingEngine;
        $this->elementFactory = $elementFactory;
        $this->allowResponsiveElements = $allowResponsiveElements;
    }

    /**
     * @param Element[] $elements
     * @return string
     */
    public function renderElements($elements)
    {
        $wrappers = array();
        $markupFragments = array();
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
            }
            $regionName = $element->getRegion();
            if (!array_key_exists($regionName, $wrappers)) {
                $wrappers[$regionName] = $this->getRegionGlue($regionName);
            }
            $defaultWrapperMarkup = $this->renderWrappers(array_filter(array($wrappers[$regionName])));

            $elementWrapper = $this->getElementWrapper($element);
            if ($elementWrapper) {
                $elementWrapMarkup = $this->renderWrappers(array_merge($wrappers, array($elementWrapper)));
            } else {
                $elementWrapMarkup = $defaultWrapperMarkup;
            }
            $markupFragments[] = $elementWrapMarkup['open'];
            $markupFragments[] = $this->renderContent($element);
            $markupFragments[] = $elementWrapMarkup['close'];
        }
        return implode('', $markupFragments);
    }

    /**
     * @param Element[] $elements
     * @return string
     */
    public function renderFloatingElements($elements)
    {
        $markup = '';
        foreach ($elements as $element) {
            if (!$element instanceof Element) {
                throw new \InvalidArgumentException("Unsupported type " . ($element && \is_object($element)) ? \get_class($element) : gettype($element));
            }
            $markup .= '<div class="' . rtrim('element-wrapper ' . $this->getElementVisibilityClass($element)) . '">'
                     . $this->renderContent($element)
                     . '</div>'
            ;
        }
        return $markup;
    }

    protected function renderContent(Element $element)
    {
        if (\is_a($element->getClass(), 'Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface', true)) {
            return $this->elementFactory->componentFromEntity($element, true)->render();
        } else {
            /** @todo: implement Element services with visitor-style rendering */
            throw new \Exception("Not implemented");
        }
    }

    /**
     * @param Element $element
     * @return string[]|null
     */
    protected function getElementWrapper(Element $element)
    {
        $visibilityClass = $this->getElementVisibilityClass($element);
        if ($visibilityClass) {
            return array(
                'tagName' => 'div',
                'class' => $visibilityClass,
            );
        } else {
            return null;
        }
    }

    /**
     * @param Element $element
     * @return string|null
     */
    public function getElementVisibilityClass(Element $element)
    {
        // Allow screenType filtering only on current map engine
        if (!$this->allowResponsiveElements || $element->getApplication()->getMapEngineCode() === Application::MAP_ENGINE_OL2) {
            return null;
        }
        switch ($element->getScreenType()) {
            case ScreenTypes::ALL:
            default:
                return null;
            case ScreenTypes::MOBILE_ONLY:
                return 'hide-screentype-desktop';
            case ScreenTypes::DESKTOP_ONLY:
                return 'hide-screentype-mobile';
        }
    }


    /**
     * @param (string[]|null)[] $wrappers
     * @return string[] with entries 'open', 'close'
     */
    protected function renderWrappers($wrappers)
    {
        $tagName = null;
        $classes = array();
        foreach ($wrappers ?: array() as $wrapper) {
            // use tag name from first wrapper entry
            if ($tagName === null) {
                $tagName = $wrapper['tagName'];
            }
            // concatenate all classes
            $classes[] = $wrapper['class'];
        }
        if (!$tagName) {
            return array(
                'open' => '',
                'close' => '',
            );
        } else {
            return array(
                'open' => '<' . $tagName . ' class="' . implode(' ', $classes) . '">',
                'close' => "</{$tagName}>",
            );
        }
    }

    /**
     * Detect appropriate Element markup wrapping tag for a named region.
     *
     * @param string $regionName
     * @return string[]|null
     */
    protected static function getRegionGlue($regionName)
    {
        // Legacy lenience in patterns: allow postfixes / prefixes around region names, e.g.
        // "some-custom-project-footer"
        if (false !== strpos($regionName, 'footer') || false !== strpos($regionName, 'toolbar')) {
            return array(
                'tagName' => 'li',
                'class' => 'toolBarItem',
            );
        } else {
            return null;
        }
    }
}