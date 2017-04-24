var L = require('leaflet');
var M = require('mustache');
require('leaflet.markercluster');
require('leaflet-loading');
var qwest = require('qwest');

var NeonMap = L.Class.extend({
  options: {
    geojson: null,
    geojsonurl: null,
    tile_url: 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
    min_zoom: 5,
    max_zoom: 18,
    initial_zoom: 12,
    image_path: '../dist/images',
    center: [51.505, -0.09],
    clustering: {
      showCoverageOnHover: false
    },
    popups: {
      template: '<h3>{{title}}</h3>{{image.alt}}',
      options: {
        className: 'neonmap-popup'
      }
    }
  },

  getAttributeData: function(el){
    return [].reduce.call(el.attributes, function(acc, attr){
      var dattr = attr.name.split(/^data-/)[1];
      if (dattr) {
          acc[dattr] = attr.value;
      }
      return acc;
    }, {});
  },

  initialize: function (el, options) {
    if (el.neonmap) {
      // Cleanup ready to reinitialize
      el.neonmap.remove();
    }
    var self = this;
    //programmatic options trump data-attributes
    L.setOptions(this, L.extend(this.getAttributeData(el), options));

    L.Icon.Default.imagePath = this.options.image_path;

    el.neonmap = this.map = L.map(el, {loadingControl: true});
    this.map.setView(this.options.center, this.options.initial_zoom);
  
    this.tiles = L.tileLayer(this.options.tile_url, {
      minZoom: this.options.min_zoom, 
      maxZoom: this.options.max_zoom, 
      attribution: this.options.attribution
    });

    this.map.addLayer(this.tiles);

    if (this.options.geojson || this.options.geojsonurl) {
      this.markers = L.markerClusterGroup(this.options.clustering);
      this.map.addLayer(this.markers);
      if (this.options.geojson) {
        if (typeof this.options.geojson === "string") {
          this.options.geojson = JSON.parse(this.options.geojson);
        }
        this.geoJsonLayer = L.geoJson(this.options.geojson, {
          onEachFeature: function(feature, layer){
            layer.bindPopup(M.render(self.options.popups.template, feature.properties), self.options.popups.options);
          }
        });
        this.markers.addLayer(this.geoJsonLayer);
        this.map.fitBounds(this.markers.getBounds(), {maxZoom: this.options.initial_zoom});
      }
      
      if (this.options.geojsonurl) {
        this.map.fire('dataloading');
        qwest.get(this.options.geojsonurl, null, {responseType: 'json'})
          .then(function(res) {
            self.geoJsonUrlLayer = L.geoJson(res, {
              onEachFeature: function(feature, layer){
                layer.bindPopup(M.render(self.options.popups.template, feature.properties), self.options.popups.options);
              }
            });
            self.markers.addLayer(self.geoJsonUrlLayer);
            self.map.fitBounds(self.markers.getBounds(), {maxZoom: self.options.initial_zoom});
          })
          .complete(function(){
            self.map.fire('dataload');
          });
      }
    }
  }
});

var neonmap = function(el, options){
  return new NeonMap(el, options);
};

// Expose our libraries quietly for the convenience of others
neonmap.Leaflet = L;
neonmap.Mustache = M;

module.exports = neonmap;

/**
 * Produce a jQuery plugin facade if we find jQuery in the env
 */
if (window.jQuery) {
  (function( $ ) {
    $.fn.neonmap = function(options) {
      this.each(function() {
        return neonmap(this, options);
      });
      return this;
    };
  }( window.jQuery ));
}
