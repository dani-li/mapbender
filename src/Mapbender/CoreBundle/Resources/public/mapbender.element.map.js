(function($){
    $.widget("mapbender.mbMap", {
        options: {
            poiIcon: {
                image: 'bundles/mapbendercore/image/pin_red.png',
                width: 32,
                height: 41,
                xoffset: 0,//-6,
                yoffset: 0,//-38
            },
            targetscale: null,
            layersets: []
        },
        srsDefinitions: [],
        poiLayerId: null,
        elementUrl: null,
        model: null,
        map: null,
        readyState: false,
        state_: {
            srs: undefined
        },
        /**
         * Creates the map widget
         */
        _create: function(){
            //OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';
            var self = this,
                    me = $(this.element);
            //Todo: Move to a seperate file. ADD ALL THE EPSGCODES!!!!111
            //jQuery.extend(OpenLayers.Projection.defaults, {'EPSG:31466': {yx : true}});
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';

            this.srsDefinitions = this.options.srsDefs || [];
            // Patch missing SRS definitions into proj4
            // This avoids errors when initializing the OL4 view with
            // "exotic" / non-geodesic projections such as EPSG:25832
            Mapbender.Projection.extendSrsDefintions(this.srsDefinitions, true, true);

            this.engineCode = Mapbender.configuration.application.mapEngineCode;

            var modelOptions = {
                srs: this.options.srs,
                maxExtent: Mapbender.Model.sanitizeExtent(this.options.extents.max),
                startExtent: Mapbender.Model.sanitizeExtent(this.options.extents.start),
                scales : this.options.scales,
                dpi: this.options.dpi,
                tileSize: this.options.tileSize
            };


            this.model = new Mapbender.Model(this.element.attr('id'), modelOptions);
            this.state_.srs = this.options.srs;
            _.forEach(this.options.layersets.reverse(), function(layerSetId) {
                this.model.addLayerSetById(layerSetId);
            }.bind(this));


            $.extend(this.options, {
                layerDefs: [],
                poiIcon: this.options.poiIcon
            });

            // NOTE: startExtent is technically mutually exclusive with
            //    center and / or targetscale
            //    However, ol may not properly initialize the view if we don't
            //    go to a defined extent (which we always have) first
            if (this.options.center) {
                this.model.setCenter(this.options.center);
            }
            if (this.options.targetscale) {
                this.model.setScale(this.options.targetscale);
            }


            this.map = me.data('mapQuery');

            this.initializePois();

            self._trigger('ready');
            this._ready();
        },
        getMapState: function(){
            return this.model.getMapState();
        },
        /**
         *
         */
        addSource: function(sourceDef, mangleIds) {
            // legacy support: callers that do not know about the mangleIds argument most certainly want ids mangled
            var doMangle = !!mangleIds || typeof mangleIds === 'undefined';
            var source =this.model.addSourceFromConfig(sourceDef, doMangle);
            if (this.engineCode === 'ol4') {
                // @todo 3.1.0: only the map widget itself should ever fire mbmap* events
                //      The old code does this in the Mapbender.Model
                //      Fix that, then remove the if
                this.fireModelEvent({
                    name: 'sourceadded',
                    value:{
                        added:{
                            source: source
                        }
                    }
                });
            }
        },
        /**
         *
         */
        removeSource: function(toChangeObj){
            if(toChangeObj && toChangeObj.remove && toChangeObj.remove.sourceIdx) {
                this.model.removeSource(toChangeObj);
            }
        },
        /**
         *
         */
        removeSources: function(keepSources){
            this.model.removeSources(keepSources);
        },
        /**
         *
         */
        changeSource: function(toChangeObj){
            if(toChangeObj && toChangeObj.source && toChangeObj.type) {
                this.model.changeSource(toChangeObj);
            }
        },
        /**
         * Triggers an event
         * options.name - name of the event (mbmap prefix will be added, result lowercased)
         * options.value - will be passed into the event handler callables as the second argument
         *
         * @see https://github.com/jquery/jquery-ui/blob/1.12.1/ui/widget.js#L659
         */
        fireModelEvent: function(options) {
            this._trigger(options.name, null, options.value);
        },
        /**
         * Returns a sourceTree from model.
         **/
        getSourceTree: function(){
            return this.model.sourceTree;
        },
        /**
         * Returns all defined srs
         */
        getAllSrs: function(){
            return this.srsDefinitions;
        },
        /**
         * Reterns the model
         */
        getModel: function(){
            return this.model;
        },
        setCenter: function(options){
            if(typeof options.box !== 'undefined' && typeof options.position !== 'undefined' && typeof options.zoom !== 'undefined')
                this.map.center(options);
            else if(typeof options.center !== 'undefined' && typeof options.zoom !== 'undefined') {
                this.map.olMap.updateSize();
                this.map.olMap.setCenter(options.center, options.zoom);
            }
        },
        /*
         * Changes the map's projection.
         */
        changeProjection: function(srs) {
            if (!srs || typeof srs !== 'string') {
                console.error("Invalid srs argument", srs);
                throw new Error("Invalid srs argument");
            }
            if (this.state_.srs !== srs) {
                var previousSrs = this.state_.srs;
                console.log("mbMap switching srs", {from: previousSrs, to: srs});
                this.model.updateMapViewForProjection(srs);
                var newProjection = this.model.getCurrentProjectionObject();
                var axisOrientation = newProjection.getAxisOrientation();
                this.fireModelEvent({
                    name: 'srschanged',
                    value: {
                        // @todo: emulate / shim projection object with OL2-compatible signature
                        projection: newProjection,
                        // this will stay engine-native
                        nativeProjection: newProjection,
                        // following attribs added to event data in OL4 initiative
                        oldCode: previousSrs,
                        newCode: srs,
                        units: newProjection.getUnits(),
                        axisOrientation: axisOrientation,
                        yx: ((axisOrientation || "enu").slice(0,2)) !== 'en',
                        extent: newProjection.getExtent()
                    }
                });
                this.state_.srs = srs;
            } else {
                // console.log("mbMap skipping srs switch", srs);
            }
        },
        /**
         * Zooms the map in
         */
        zoomIn: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomIn();
        },
        /**
         * Zooms the map out
         */
        zoomOut: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomOut();
        },
        /**
         * Zooms the map to max extent
         */
        zoomToFullExtent: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomToMaxExtent();
        },
        /**
         * Zooms the map to extent
         */
        zoomToExtent: function(extent, closest){
            if(typeof closest === 'undefined')
                closest = true;
            this.map.olMap.zoomToExtent(extent, closest);
        },
        /**
         * Zooms the map to scale
         */
        zoomToScale: function(scale, closest){
            if(typeof closest === 'undefined')
                closest = false;
            this.map.olMap.zoomToScale(scale, closest);
        },
        /**
         * Adds the popup
         */
        addPopup: function(popup){
            //TODO: MapQuery
            this.map.olMap.addPopup(popup);
        },
        /**
         * Removes the popup
         */
        removePopup: function(popup){
            //TODO: MapQuery
            this.map.olMap.removePopup(popup);
        },
        /**
         * Returns the scale list
         * @deprecated, just get options.scales yourself
         */
        scales: function(){
            return this.options.scales;
        },
        /**
         * Sets opacity to source
         * @param {spource} source
         * @param {float} opacity
         */
        setOpacity: function(source, opacity){
            this.model.setOpacity(source, opacity);
        },
        /**
         * Zooms to layer
         * @param {object} options of form { sourceId: XXX, layerId: XXX }
         */
        zoomToLayer: function(options){
            this.model.zoomToLayer(options);
        },
        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true) {
                callback();
            }
        },
        /**
         *
         */
        _ready: function(){
            this.readyState = true;
        },
        /**
         * Turns on the highlight layer at map
         */
        highlightOn: function(features, options){
            this.model.highlightOn(features, options);
        },
        /**
         * Turns off the highlight layer at map
         */
        highlightOff: function(features){
            this.model.highlightOff(features);
        },
        /**
         * Loads the srs definitions from server
         */
        loadSrs: function(srslist){
            var self = this;
            $.ajax({
                url: self.elementUrl + 'loadsrs',
                type: 'POST',
                data: {
                    srs: srslist
                },
                dataType: 'json',
                contetnType: 'json',
                context: this,
                success: this._loadSrsSuccess,
                error: this._loadSrsError
            });
            return false;
        },
        /**
         * Loads the srs definitions from server
         */
        _loadSrsSuccess: function(response, textStatus, jqXHR){
            if(response.data) {
                for(var i = 0; i < response.data.length; i++) {
                    proj4.defs(response.data[i].name, response.data[i].definition);
                    this.model.srsDefs.push(response.data[i]);
                    this.fireModelEvent({
                        name: 'srsadded',
                        value: response.data[i]
                    });
                }
            } else if(response.error) {
                Mapbender.error(Mapbender.trans(response.error));
            }
        },
        setSourceLayerOrder: function(sourceId, layerIdOrder) {
            this.model.setSourceLayerOrder(sourceId, layerIdOrder);
            this.fireModelEvent({
                name: 'sourcemoved',
                // no receiver uses the bizarre "changeOptions" return value
                // on this event
                value: null
            });
        },
        setSourceState: function(source, visible) {
            this.model.setSourceState(source, !!visible);
            this.fireModelEvent({
                name: 'sourcestatechanged',
                value: null
            });
            // @todo: legacy 'sourcechanged' event with legacy data payload
        },
        reorderSources: function(sources) {
            this.model.reorderSources(sources);
            this.fireModelEvent({
                name: 'sourcemoved',
                value: null
            });
        },
        /**
         * Loads the srs definitions from server
         */
        _loadSrsError: function(response){
            Mapbender.error(Mapbender.trans(response));
        },

        /**
         * Initialize POIs
         */
        initializePois: function () {
            var self = this,
                poiOptionsList = (this.options && this.options.extra && this.options.extra['pois']) || [];

            if (!poiOptionsList.length) {
                return;
            }

            var pois = poiOptionsList.map(function(poi) {
                var coordinates = [poi.x, poi.y];

                if (poi.srs) {
                    coordinates = Mapbender.Projection.transform(poi.srs, self.model.getCurrentProjectionCode(), coordinates);
                }

                return {
                    position: coordinates,
                    label: poi.label
                };
            });

            var size = [
                this.options.poiIcon.width,
                this.options.poiIcon.height
            ];

            var offset = [
                this.options.poiIcon.xoffset,
                this.options.poiIcon.yoffset
            ];

            var iconStyle = this.model.createIconStyle({
                src: Mapbender.configuration.application.urls.asset + this.options.poiIcon.image,
                size: size,
                offset: offset
            });

            $.each(pois, function(idx, poi) {
                self.poiLayerId = self.model.setMarkerOnCoordinates(poi.position, self.element.attr('id'), self.poiLayerId, iconStyle);

                if (poi.label) {
                    var popupOverlay = new Mapbender.Model.MapPopup(undefined, self.model);
                    popupOverlay.$markup.addClass('flipped');
                    popupOverlay.openPopupOnXYWithCustomContent(poi.position, poi.label);
                }
            });
        },
    });

})(jQuery);

$('body').delegate(':input', 'keydown', function(event){
    event.stopPropagation();
});
