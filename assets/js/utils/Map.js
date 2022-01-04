/* global L */
import 'leaflet';
import 'leaflet-groupedlayercontrol';
import 'leaflet-control-geocoder';
import 'leaflet-draw';
import 'leaflet-easybutton';

import ThemeColors from '../constant/theme-colors';
import Locales from '../constant/locales';
import './LeafletOsmData';

class Map {

  /**
   *
   * @param mapId
   * @param center
   * @param target
   * @param options
   */
  constructor(mapId = 'map', center, target, options) {
    this.locale = Locales.map['it'];

    this.target = target;
    this.targetName = $(target.name);
    this.targetElement = $(target.element);

    // Map settings
    this.settings = {
      center: [41.9027835, 12.4963655],
      controlsPosition: 'topleft',
      defaults: {
        zoom: 10,
        minZoom: 0,
        maxZoom: 20,
        maxNativeZoom: 18,
        zoomControl: false,
        scrollWheelZoom: false,
        attributionControl: true,
      }
    };
    this.settings.mapId = mapId;

    // Map center
    const mapCenter = typeof center === 'object' && center.length === 2 ? center : this.settings.center;
    this.settings.defaults.center = L.latLng(mapCenter[0], mapCenter[1]);
    this.editableLayers = L.featureGroup();

    // Base layers
    this.basemaps = {
      Base: L.tileLayer(
        'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png',
        {
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://cartodb.com/attributions">CartoDB</a>',
        }
      )
    };

    // Init overlays
    this.overlays = {};

    // Init map
    this.map = L.map(mapId, { ...this.settings.defaults, ...options });
    this.map.addLayer(this.basemaps.Base);

    // Add zoom controls
    this.map.addControl(
      // eslint-disable-next-line new-cap
      new L.control.zoom({
        position: this.settings.controlsPosition,
      })
    );

    $('.leaflet-control-layers').hide();

    this.geocoder = L.Control.Geocoder.nominatim();
    this.geoCoderControl = new L.Control.Geocoder({
      position: this.settings.controlsPosition,
      geocoder: this.geocoder,
    });
  }

  addLocationMarker(lat = 0, lng = 0, callback) {
    let point;

    if (lat === 0 && lng === 0) {
      point = this.map.getCenter();
    } else {
      point = L.latLng(lat, lng);
    }

    this.locationMarker = this.addMarker(point, {
      draggable: true,
      autoPan: true,
      icon: {
        borderWidth: 0,
        // iconShape: 'marker',
        backgroundColor: ThemeColors.volcano,
        borderColor: ThemeColors.white,
      },
    })
      .addTo(this.map)
      .bindPopup(this.locale.locationMarker.popupContent);

    this.locationMarker.on('dragend', (e) => {
      if (callback && typeof callback === 'function') {
        callback(e.target.getLatLng());
      }
    });
  }

  setLocationMarker(lat, lng) {
    this.locationMarker.setLatLng(L.latLng(lat, lng));
  }

  /**
   *
   * @param address
   */
  geocodeLocation(address, callback) {
    return this.geocoder.geocode(address, (results) => {
      const r = results[0];
      if (r) {
        this.map.flyTo(r.center, 12);
        if (callback && typeof callback === 'function') {
          callback(r);
        }
      }
    });
  }

  /**
   *
   */
  addLayerControl(basemaps = true, overlays = true) {
    // Add layer control layer
    this.layerControl = new L.Control.GroupedLayers(
      basemaps ? this.basemaps : {},
      overlays ? this.overlays : {},
      {
        position: 'topright',
        collapsed: false,
      }
    ).addTo(this.map);
  }

  /**
   *
   * @param options
   * @returns {*}
   */
  setMarkerIcon(options) {

    return L.BeautifyIcon.icon({
      icon: '',
      iconShape: 'marker',
      borderWidth: 3,
      backgroundColor: 'white',
      borderColor: 'blue',
    });
  }

  /**
   *
   */
  addDrawingLayer(params) {
    L.drawLocal = this.locale.drawLocales;

    const drawControlFull = new L.Control.Draw({
      position: this.settings.controlsPosition,
      draw: {
        polygon: {
          allowIntersection: false, // Restricts shapes to simple polygons
          drawError: {
            color: ThemeColors.red,
            message: 'Intersezioni non permesse..', // Message that will show when intersect
          },
          shapeOptions: {
            color: ThemeColors.cyan,
          },
        },
        marker: false,
        polyline: false,
        rectangle: false,
        circle: false,
        circlemarker: false,
        toolbar: {
          buttons: {
            polygon: 'Disegna!',
          },
        },
      },
      edit: {
        featureGroup: this.editableLayers,
        poly: {
          allowIntersection: false,
        },
      },
    });

    const drawControlEditOnly = new L.Control.Draw({
      position: this.settings.controlsPosition,
      draw: {
        polygon: false,
        marker: false,
        polyline: false,
        rectangle: false,
        circle: false,
        circlemarker: false,
        toolbar: {
          buttons: {
            polygon: 'Disegna',
          },
        },
      },
      edit: {
        featureGroup: this.editableLayers,
        poly: {
          allowIntersection: false,
        },
      },
    });

    const self = this;


    // Add overlay to layer control
    if (this.layerControl) {
      this.layerControl.addOverlay(this.editableLayers, 'Disegno');
    }

    this.map.addLayer(this.editableLayers);

    if (this.editableLayers.getLayers().length === 0) {
      this.map.addControl(drawControlFull);
    } else {
      this.map.addControl(drawControlEditOnly);
    }

    this.map.on(L.Draw.Event.CREATED, (e) => {
      this.editableLayers.addLayer(e.layer);
      drawControlFull.remove(this.map);
      drawControlEditOnly.addTo(this.map);
      self.saveGeoJson(this.editableLayers.toGeoJSON());
    });

    this.map.on(L.Draw.Event.DELETED, () => {
      if (this.editableLayers.getLayers().length === 0) {
        drawControlEditOnly.remove(this.map);
        drawControlFull.addTo(this.map);
        self.targetElement.val('');
      }
    });

    this.map.on(L.Draw.Event.EDITSTOP, () => {
      self.saveGeoJson(this.editableLayers.toGeoJSON());
    });

    this.map.on('draw:edited', () => {
      self.saveGeoJson(this.editableLayers.toGeoJSON());
    });
  }

  /**
   *
   * @param point
   * @param options
   * @return boolean|L.Marker
   */
  addMarker(point, options) {
    if (point === undefined || Number.isNaN(point[0]) || Number.isNaN(point[1])) {
      return false;
    }

    const markerOptions = { ...this.settings.markerOptions, ...options };
    markerOptions.icon = this.setMarkerIcon(markerOptions.icon);

    return L.marker(point, markerOptions);
  }


  /**
   *
   * @param geojsonFeature
   * @param styleColor
   * @param fitBounds
   */
  addGeojsonFeatures(geojsonFeature, styleColor, fitBounds = true) {
    const color = styleColor || 'cyan';
    const style = {
      color: ThemeColors[color],
      weight: 5,
      opacity: 0.65,
    };

    const self = this;

    const geofences = L.geoJSON(geojsonFeature, {
      style(feature) {
        if (feature.properties.color) {
          return $.extend({}, style, {
            color: ThemeColors[feature.properties.color],
          });
        }

        return style;
      },
      onEachFeature(f, l) {
        if (f.geometry.type != 'Point') {
          self.editableLayers.addLayer(l);
        }
      },
    });

    const layerBounds = geofences.getBounds();
    if (fitBounds && layerBounds !== undefined && Object.keys(layerBounds).length > 0) {
      this.map.fitBounds(layerBounds, { maxZoom: 16 });
    }
  }

  loadGeoJsonFromUrl(url) {

    if (!url.endsWith("/full")) {
      url = url + '/full';
    }

    const self = this;
    $.ajax({
      url: url,
      dataType: "xml",
      success: function (xml) {
        var layer = new L.OSMData.DataLayer(xml);
        var geoJSON = layer.toGeoJSON();
        self.removeLayers();
        self.addGeojsonFeatures(geoJSON);
        self.saveGeoJson(geoJSON);
      }
    });
  }

  saveGeoJson(geoJSON) {
    this.targetElement.val(JSON.stringify(geoJSON));
  };

  removeLayers() {
    const self = this;
    this.editableLayers.eachLayer(function (layer) {
      self.editableLayers.removeLayer(layer);
    });
  }
}

export default Map;
