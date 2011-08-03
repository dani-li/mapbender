<?php

namespace MB\WMSBundle\Components;
use MB\WMSBundle\Entity\WMSService;
use MB\WMSBundle\Entity\WMSLayer;
use MB\WMSBundle\Entity\Layer;
use MB\WMSBundle\Entity\GroupLayer;

/**
* Class that Parses WMS GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
class CapabilitiesParser {

    /**
     * The XML representation of the Capabilites Document
     * @var DOMDocument
     */
    protected $doc;
    
    /**
    * @param DOMDocument the document to be parsed
    */
    public function __construct($data){

        $this->doc = new \DOMDocument();
        if(!$this->doc->loadXML($data)){
            throw new \UnexpectedValueException("Could not parse CapabilitiesDocument.");
        }

        if(!@$this->doc->validate()){
            // TODO logging
        };
    }

    /**
    *   @return WMSService
    */
    public function getWMSService(){
        $wms = new WMSService();

        $wms->setVersion((string)$this->doc->documentElement->getAttribute("version"));
        foreach( $this->doc->documentElement->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){
                switch ($node->nodeName) {

                    case "Service":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch ($node->nodeName) {
                                    case "Name":
                                        $wms->setName($node->nodeValue);
                                    break;
                                    case "Title":
                                        $wms->setTitle($node->nodeValue);
                                    break;
                                    case "Abstract":
                                        $wms->setAbstract($node->nodeValue);
                                    break;
                                    case "KeywordList":
                                    break;
                                    case "OnlineResource":
                                        $onlineResource = $node->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                        $wms->setOnlineResource($onlineResource);
                                    break;
                                    case "ContactInformation":
                                        $wms = $this->ContactInformationFromNode($wms,$node);
                                    break;
                                    case "Fees":
                                        $wms->setFees($node->nodeValue);
                                    break;
                                    case "AccessConstraints":
                                        $wms->setAccessConstraints($node->nodeValue);
                                    break;
                                }
                            } 
                        }
                    break;
                    case "Capability":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch($node->nodeName){
                                    case "Request":
                                        foreach ($node->childNodes as $node){
                                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                                 $this->requestDescriptionFromNode($wms,$node);
                                            }
                                        }
                                    break;
                                    case "Exception":
                                    break;
                                    case "VendorSpecificCapabilities":
                                    case "UserDefinedSymbolization":
                                    break;
                                    case "Layer":
                                        $sublayer = $this->WMSLayerFromLayerNode($node);
                                        $wms->getLayer()->add($sublayer);
                                    break;
                                }
                            }
                        }
                    break;
                }
            }
        }

        // check for mandatory elements
        if($wms->getName() === null){
            throw new \Exception("Mandatory Element Name not defined on Service");
        }
        return $wms;
    }

    /**
     *  @param MB\WMSBundle\WMSService The WMS that needs the contact information
     *  @param \DOMNode the <contactInformation> node of the WMS
     *  @return the wms
     */
    protected function ContactInformationFromNode($wms,\DOMElement $contactNode){
        foreach($contactNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){  
                switch ($node->nodeName) {
                    case "ContactPersonPrimary":
                        foreach($node->childNodes as $subnode){
                            if($subnode->nodeType == XML_ELEMENT_NODE){  
                                switch ($subnode->nodeName) {
                                    case "ContactPerson":
                                        $wms->setContactPerson($subnode->nodeValue);
                                    break;
                                    case "ContactOrganization":
                                        $wms->setContactOrganization($subnode->nodeValue);
                                    break;
                                }
                            }
                        }
                    break;
                    case "ContactPosition":
                        $wms->setContactPosition($node->nodeValue);
                    break;
                    case "ContactAddress":
                        foreach($node->childNodes as $subnode){
                            if($subnode->nodeType == XML_ELEMENT_NODE){  
                                switch ($subnode->nodeName) {
                                    case "Address":
                                        $wms->setContactAddress($subnode->nodeValue);
                                    break;
                                    case "AddressType":
                                        $wms->setContactAddressType($subnode->nodeValue);
                                    break;
                                    case "City":
                                        $wms->setContactAddressCity($subnode->nodeValue);
                                    break;
                                    case "StateOrProvince":
                                        $wms->setContactAddressStateOrProvince($subnode->nodeValue);
                                    break;
                                    case "PostCode":
                                        $wms->setContactAddressPostCode($subnode->nodeValue);
                                    break;
                                    case "Country":
                                        $wms->setContactAddressCountry($subnode->nodeValue);
                                    break;
                                }
                            }
                        }
                    break;
                    case "ContactVoiceTelephone":
                        $wms->setContactVoiceTelephone($node->nodeValue);
                    break;
                    case "ContactFacsimileTelephone":
                        $wms->setContactFacsimileTelephone($node->nodeValue);
                    break;
                    case "ContactElectronicMailAddress":
                        $wms->setContactElectronicMailAddress($node->nodeValue);
                    break;
                }
            }
        }
        return $wms;
    }

    /**
     * @param DOMNode a WMS layernode "<Layer>" to be converted to a Layer Objject
     * @return WMSLayer 
     */
    protected function WMSLayerFromLayerNode(\DOMNode $layerNode){

        $layer = new WMSLayer();
        $srs = array();

        foreach($layerNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){  
                switch ($node->nodeName) {
                    case "Name":
                        $layer->setName($node->nodeValue);
                    break;
                    
                    case "Title":
                        $layer->setTitle($node->nodeValue);
                    break;

                    case "Abstract":
                        $layer->setAbstract($node->nodeValue);
                    break;
                    
                    case "SRS":
                        $srs[] = $node->nodeValue;
                    break;

                    case "LatLonBoundingBox":   
                        $bounds = array(4);
                        $bounds[0] = trim($node->getAttribute("minx"));
                        $bounds[1] = trim($node->getAttribute("miny"));
                        $bounds[2] = trim($node->getAttribute("maxx"));
                        $bounds[3] = trim($node->getAttribute("maxy"));
                        $layer->setLatLonBounds(implode(' ',$bounds));
                    break;

                    case "BoundingBox":
                    break;

                    case "KeywordList":
                        # $layer->addKeyword();
                    break;

                    case "Style":
                        # $layer->setStyle();
                    break;

                    case "ScaleHint":
                    break;

                    case "Layer":
                        $sublayer = $this->WMSLayerFromLayerNode($node);
                        $layer->getLayer()->add($sublayer);
                    break;
                    
                }
            }
        }

        $srs = implode(',',$srs);
        $layer->setSRS($srs);
        // check for manadory elements
        if($layer->getTitle() === null){
            throw new \Exception("Invalid Layer definition, mandatory Field 'Title' not defined");
        }
        return $layer;
    }

    /**
     *  @param MB\WMSBundle\WMSService The WMS that needs the request information
     *  @param \DOMNode a childElement of the <Request> element
     *  @return the wms
     */
    public function requestDescriptionFromNode($wms,\DomElement $RequestNode){
        $formats = array();
        $get  = "";
        $post ="";
        foreach ($RequestNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){ 
                switch ($node->nodeName) {
                    case "Format":
                        $formats[] = $node->nodeValue;
                    break;
                    case "DCPType":
                        try{
                            foreach ($node->childNodes as $httpnode){
                                if($httpnode->nodeType == XML_ELEMENT_NODE){ 
                                    foreach ($httpnode->childNodes as $methodnode){
                                        if($methodnode->nodeType == XML_ELEMENT_NODE){ 
                                            switch ($methodnode->nodeName) {
                                                case "Get":
                                                    foreach ($methodnode->childNodes as $resnode){
                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
                                                            $get = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                                        }
                                                    }
                                                break;
                                                case "Post":
                                                    foreach ($methodnode->childNodes as $resnode){
                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
                                                            $post = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                                        }
                                                    }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }catch(\Exception $E){
                            throw $E;
                        }
                    break;
                }
            }
        }

        switch($RequestNode->nodeName){
            case "GetCapabilities":
                $wms->setRequestGetCapabilitiesGET($get);
                $wms->setRequestGetCapabilitiesPOST($post);
                $wms->setRequestGetCapabilitiesFormats(implode(',',$formats));
            break;
            case "GetMap":
                $wms->setRequestGetMapGET($get);
                $wms->setRequestGetMapPOST($post);
                $wms->setRequestGetMapFormats(implode(',',$formats));
            break;
            case "GetFeatureInfo":
                $wms->setRequestGetFeatureInfoGET($get);
                $wms->setRequestGetFeatureInfoPOST($post);
                $wms->setRequestGetFeatureInfoFormats(implode(',',$formats));
            break;
            case "DescribeLayer":
                $wms->setRequestDescribeLayerGET($get);
                $wms->setRequestDescribeLayerPOST($post);
                $wms->setRequestDescribeLayerFormats(implode(',',$formats));
            break;
            case "GetLegendGraphic":
                $wms->setRequestGetLegendGraphicGET($get);
                $wms->setRequestGetLegendGraphicPOST($post);
                $wms->setRequestGetLegendGraphicFormats(implode(',',$formats));
            break;
            case "GetStyles":
                $wms->setRequestGetStylesGET($get);
                $wms->setRequestGetStylesPOST($post);
                $wms->setRequestGetStylesFormats(implode(',',$formats));
            break;
            case "PutStyles":
                $wms->setRequestPutStylesGET($get);
                $wms->setRequestPutStylesPOST($post);
                $wms->setRequestPutStylesFormats(implode(',',$formats));
            break;
        }

    }
}
