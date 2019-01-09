(function($){
    'use strict';

    /**
     * @typedef {{type:string, opacity:number, geometries: Array<Object>}} VectorLayerData~print
     */
    $.widget('mapbender.mbImageExport', {
        options: {},
        map: null,
        _geometryToGeoJson: null,
        $form: null,

        _create: function() {
            if(!Mapbender.checkTarget('mbImageExport', this.options.target)) {
                return;
            }
            this.$form = $('form', this.element);
            $(this.element).show();

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function() {
            this.model = Mapbender.elementRegistry.listWidgets().mapbenderMbMap.model;
            var olGeoJson = this.model.createOlFormatGeoJSON();
            this._geometryToGeoJson = function(geometry) {
                return olGeoJson.writeGeometryObject.call(olGeoJson, geometry, olGeoJson);
            };

            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 250,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.cancel'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.ok'),
                            cssClass: 'button right',
                            callback: function(){
                                self._exportImage();
                            }
                        }
                    }
                });

                this.popup.$element.on('close', $.proxy(this.close, this));
            }
        },
        close: function(){
            if (this.popup) {
                if (this.popup.$element) {
                    // prevent infinite event handling recursion
                    this.popup.$element.off('close');
                }
                this.popup.close();
                this.popup = null;
            }

            if ( this.callback ) {
                this.callback.call();
            } else {
                this.callback = null;
            }
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true){
                callback();
            }
        },
        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        },
        /**
         *
         * @param {*} sourceDef
         * @param {number} [scale]
         * @returns {{layers: *, styles: *}}
         * @private
         */
        _getRasterVisibilityInfo: function(sourceDef, scale) {
            var layer = this.map.map.layersList[sourceDef.mqlid].olLayer;
            if (scale) {
                var toChangeOpts = {options: {children: {}}, sourceIdx: {mqlid: sourceDef.mqlid}};
                var geoSourceResponse = Mapbender.source[sourceDef.type].changeOptions(sourceDef, scale, toChangeOpts);
                return {
                    layers: geoSourceResponse.layers,
                    styles: geoSourceResponse.styles
                };
            } else {
                return {
                    layers: layer.params.LAYERS,
                    styles: layer.params.STYLES
                };
            }
        },
        /**
         * @returns {Array<Object>} sourceTreeish configuration objects
         * @private
         */
        _getRasterSourceDefs: function() {
            return this.model.getActiveSources();
        },
        _getExportScale: function() {
            return null;
        },
        _getExportExtent: function() {
            return this.model.getMapExtent();
        },
        _collectRasterLayerData: function() {
            var sources = this._getRasterSourceDefs();
            var mapSize = this.model.getMapSize();
            var mapExtent = this._getExportExtent();

            var dataOut = [];

            for (var i = 0; i < sources.length; i++) {
                var source = sources[i];
                if (!source.isVisible()) {
                    continue;
                }
                var printConfig = this.model.getSourcePrintConfig(source, mapExtent, mapSize);
                if (printConfig) {
                    dataOut.push(printConfig);
                }
            }
            return dataOut;
        },
        _collectJobData: function() {
            var mapCenter = this.model.getMapCenter();
            var mapExtent = this._getExportExtent();
            var imageSize = this.model.getMapSize();
            var rasterLayers = this._collectRasterLayerData();
            var geometryLayers = this._collectGeometryLayers();
            return {
                layers: rasterLayers.concat(geometryLayers),
                width: imageSize[0],
                height: imageSize[1],
                center: {
                    x: mapCenter[0],
                    y: mapCenter[1]
                },
                extent: {
                    width: this.model.getWidthOfExtent(mapExtent),
                    height: this.model.getWidthOfExtent(mapExtent)
                }
            };
        },
        _exportImage: function() {
            var jobData = this._collectJobData();
            if (!jobData.layers.length) {
                Mapbender.info(Mapbender.trans("mb.print.imageexport.info.noactivelayer"));
            } else {
                this._submitJob(jobData);
                this.close();
            }
        },
        _submitJob: function(jobData) {
            var $hiddenArea = $('.-fn-hidden-fields', this.$form);
            $hiddenArea.empty();
            var submitValue = JSON.stringify(jobData);
            var $input = $('<input/>').attr('type', 'hidden').attr('name', 'data');
            $input.val(submitValue);
            $input.appendTo($hiddenArea);
            $('input[type="submit"]', this.$form).click();
        },
        /**
         * Should return true if the given layer needs to be included in export
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterGeometryLayer: function(layer) {
            if ('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || layer.visibility === false || this.layer === layer) {
                return false;
            }
            if (!(layer.features && layer.features.length)) {
                return false;
            }
            return true;
        },
        /**
         * Should return true if the given feature should be included in export.
         *
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {boolean}
         * @private
         */
        _filterFeature: function(feature) {
            // onScreen throws an error if geometry is not populated, see
            // https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Feature/Vector.js#L198
            if (!feature.geometry || !feature.onScreen(true)) {
                return false;
            }
            return true;
        },
        /**
         * Extracts and preprocesses the geometry from a feature for export backend consumption.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {Object} geojsonish, with (non-conformant) "style" entry bolted on (native Openlayers format!)
         * @private
         */
        _extractFeatureGeometry: function(layer, feature) {
            var geometry = this._geometryToGeoJson(feature.geometry);
            if (feature.style !== null) {
                // stringify => decode: makes a deep copy of the style at the moment of capture
                geometry.style = JSON.parse(JSON.stringify(feature.style));
            } else {
                geometry.style = layer.styleMap.createSymbolizer(feature, feature.renderIntent);
            }
            return geometry;
        },
        /**
         * Should return true if the given feature geometry should be included in export.
         *
         * @param geometry
         * @returns {boolean}
         * @private
         */
        _filterFeatureGeometry: function(geometry) {
            if (geometry.style.fillOpacity > 0 || geometry.style.strokeOpacity > 0) {
                return true;
            }
            if (geometry.style.label !== undefined) {
                return true;
            }
            return false;
        },
        /**
         * Should return export data (sent to backend) for the given geometry layer. Given layer is guaranteed
         * to have passsed through the _filterGeometryLayer check positively.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns VectorLayerData~export
         * @private
         */
        _extractGeometryLayerData: function(layer) {
            var geometries = layer.features
                .filter(this._filterFeature.bind(this))
                .map(this._extractFeatureGeometry.bind(this, layer))
                .filter(this._filterFeatureGeometry.bind(this))
            ;
            return {
                type: 'GeoJSON+Style',
                opacity: 1,
                geometries: geometries
            };
        },
        _collectGeometryLayers: function() {
            var printStyleOptions = this.model.getVectorLayerPrintStyleOptions();

            var vectorLayers = [];
            var allFeatures = this.model.getVectorLayerFeatures();
            for (var owner in allFeatures) {
                for (var uuid in allFeatures[owner]) {
                    var features = allFeatures[owner][uuid];
                    if (!features) {
                        continue;
                    }
                    var geometries = [];
                    for (var idx = 0; idx < features.length; idx++) {
                        var geometry = this._geometryToGeoJson(features[ idx ].getGeometry());
                        if (geometry) {
                            var styleOptions = {};
                            if (printStyleOptions.hasOwnProperty(owner) && printStyleOptions[owner].hasOwnProperty(uuid)) {
                                styleOptions = printStyleOptions[owner][uuid];
                            }

                            geometry.style = styleOptions;
                            geometries.push(geometry);
                        }
                    }

                    var layerOpacity = 1;
                    if (this.model.vectorLayer.hasOwnProperty(owner)
                        && this.model.vectorLayer[owner].hasOwnProperty(uuid )
                    ) {
                        layerOpacity = this.model.vectorLayer[owner][uuid].getOpacity()
                    }

                    vectorLayers.push({
                        "type": "GeoJSON+Style",
                        "opacity": layerOpacity,
                        "geometries": geometries
                    });
                }
            }
            return vectorLayers;
        },
        /**
         * Check BBOX format inversion
         *
         * @param {OpenLayers.Layer.HTTPRequest} layer
         * @returns {boolean}
         * @private
         */
        _changeAxis: function(layer) {
            var projCode = (layer.map.displayProjection || layer.map.projection).projCode;

            if (layer.params.VERSION === '1.3.0') {
                if (OpenLayers.Projection.defaults.hasOwnProperty(projCode) && OpenLayers.Projection.defaults[projCode].yx) {
                    return true;
                }
            }

            return false;
        },

        _noDanglingCommaDummy: null
    });

})(jQuery);
