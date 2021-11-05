<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceMetadata;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Controller\ApplicationController;

/**
 * Collects template variables from a WmsInstance for MapbenderCoreBundle::metadata.html.twig
 * Renders frontend meta data for an entire Wms source or an individual layer.
 * @deprecated this entire thing should be implemented purely in twig
 * @see ApplicationController::metadataAction()
 *
 * @inheritdoc
 * @author Paul Schmidt
 */
class WmsMetadata extends SourceMetadata
{

    public function getTemplate()
    {
        return 'MapbenderCoreBundle::metadata.html.twig';
    }

    /**
     * @param SourceInstance $instance
     * @param string $itemId
     * @return array
     */
    public function getData(SourceInstance $instance, $itemId = null)
    {
        /** @var WmsInstance $instance */
        $root = $instance->getRootlayer();
        $title = $root
            ? $root->getTitle() ?: $root->getSourceItem()->getTitle() ?: $instance->getSource()->getTitle()
            : $instance->getSource()->getTitle()
        ;
        $src = $instance->getSource();
        $sectionData = array();

        # add items metadata
        if ($itemId) {
            foreach ($instance->getLayers() as $layer) {
                if (strval($layer->getId()) === strval($itemId)) {
                    $layerItems = $this->prepareLayers($layer);
                    $sectionData[] = $this->formatSection(static::$SECTION_ITEMS, $layerItems);
                    break;
                }
            }
        }
        return array(
            'metadata' => array(
                'sections' => $sectionData,
            ),
            'prefix' => 'mb.wms.metadata.section.',
        );
    }

    /**
     * @param WmsInstanceLayer $layer
     * @return string[][]
     */
    private function prepareLayers($layer)
    {
        $sourceItem = $layer->getSourceItem();
        $layerData = array(
            'name' => $sourceItem->getName(),
            'title' => $layer->getTitle() ?: $layer->getSourceItem()->getTitle(),
            'abstract' => $sourceItem->getAbstract(),
        );
        $bbox = $sourceItem->getLatlonBounds(true);
        if ($bbox) {
            $layerData['bbox'] = $this->formatBbox($bbox);
        }
        $layerData['srs'] = implode(', ', $layer->getSourceItem()->getSrs(true));

        if($layer->getSublayer()->count() > 0){
            $layerData['subitems'] = array();
            foreach($layer->getSublayer() as $sublayer){
                $layerData['subitems'][] = $this->prepareLayers($sublayer);
            }
        }
        return $layerData;
    }

    /**
     * @param BoundingBox $bbox
     * @return string
     */
    public static function formatBbox($bbox)
    {
        return $bbox->getSrs() . " " . implode(',', array(
            $bbox->getMinx(),
            $bbox->getMiny(),
            $bbox->getMaxx(),
            $bbox->getMaxy(),
        ));
    }
}
