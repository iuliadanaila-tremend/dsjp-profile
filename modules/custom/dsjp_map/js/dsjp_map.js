(function ($, Drupal, drupalSettings) {
  var tooltipData = drupalSettings.dsjp_map.mapData.tooltip;
  var mapColor = drupalSettings.dsjp_map.mapData.color;
  var threeCode = drupalSettings.dsjp_map.mapData.threeCode;
  $wt.map
    .render("world-map", {
      map: {
        center: [51, 16],
        zoom: 4.3,
        minZoom: 3,
        maxZoom: 10,
        background: false,
        // height: 500,
        // epsg: 3035,
        // maxBounds: [
        //   [45.80335250387521, 75.95315876396032],
        //   [25.192146759129614, -32.18334520833237]
        // ],
      },
      config: {
        menu: false,
      },
    })
    .ready(function (map) {
      $('.wt-map.wtWaiting').css('display', 'none');
      var prevLayerClicked = null;

      function originalStyle(layer, CNTR_ID) {
        var excludedCountries = ['RU', 'TR'];
        var layerColor = "#ffffff";
        if (typeof mapColor[CNTR_ID] !== "undefined") {
          layerColor = mapColor[CNTR_ID];
        }
        else if ($.inArray(CNTR_ID, excludedCountries) !== -1) {
          layerColor = '#E0ECFD';
        }
        else if (CNTR_ID === 'KS') {
          layerColor = '#F8F8F8';
        }
        return {
          weight: 3,
          fillColor: layerColor,
          color: "#E0ECFD",
          fillOpacity: 1,
          dashArray: "3",
          stroke: true,
        };
      }
      map
        .countries(drupalSettings.dsjp_map.mapData.countries, {
          style: function (layer) {
            var excludedCountries = ['RU', 'TR'];
            var layerColor = "#ffffff";
            if (typeof mapColor[layer.properties.CNTR_ID] !== "undefined") {
              layerColor = mapColor[layer.properties.CNTR_ID];
            }
            else if ($.inArray(layer.properties.CNTR_ID, excludedCountries) !== -1) {
              layerColor = '#E0ECFD';
            }
            else if (layer.properties.CNTR_ID === 'KS') {
              layerColor = '#F8F8F8';
            }
            return {
              weight: 3,
              fillColor: layerColor,
              color: "#E0ECFD",
              fillOpacity: 1,
              dashArray: "3",
              stroke: true,
            };
          },
          events: {
            click: function (layer) {
              // Restyle the small countries - Malta, Cyprus
              $('div.active-country svg').each(function(){
                $(this).find("path").css({ fill: $(this).attr('originalfill') });
              });
              $("active-country").removeClass("active-country");
              if (prevLayerClicked !== null) {
                prevLayerClicked.setStyle(originalStyle(prevLayerClicked, prevLayerClicked.feature.properties.CNTR_ID));
              }
              // zoom in only if we have data for the given country.
              if (typeof mapColor[layer.feature.properties.CNTR_ID] !== 'undefined') {
                map.fitBounds(layer.getBounds());
              }
              $(".world-map-container").addClass("info-opened");
              $("div.country-container:not(.hidden)").toggleClass("hidden");
              $("div.country-container.code-" + layer.feature.properties.CNTR_ID).toggleClass("hidden");

              $("div.country-name--" + layer.feature.properties.CNTR_ID).addClass("active-country");
              layer.setStyle({
                weight: 5,
                fillColor: '#B13E65',
              });
              prevLayerClicked = layer;
              $('#world-map').attr({
                "data-active-country": layer._path.id,
                "data-active-color": mapColor[layer.feature.properties.CNTR_ID],
              });
            },
            tooltip: function (layer) {
              var tpString = tooltipData[layer.feature.properties.CNTR_ID];
              // Add the country label at the same time as the tooltip.
              if (tpString && tpString.length) {
                var marker = L.marker(layer.feature.properties.CENTROID, {
                  icon: L.divIcon({
                    className: 'map-country-names country-name--' + layer.feature.properties.CNTR_ID,
                    html: layer.feature.properties.CNTR_NAME,
                  }),
                  zIndexOffset: 1000
                });
                // When the user clicks on the country name, trigger the
                // description box.
                marker.on('click', function (e) {
                  layer.fireEvent('click');
                  layer.closeTooltip();
                });
                // When the user hovers over the country name, make sure the
                // country tooltip is displayed instead of the marker one.
                marker.on('tooltipopen', function (e) {
                  this.closeTooltip();
                  layer.openTooltip();
                });
                marker.on('mouseout', function () {
                  layer.closeTooltip();
                });
                marker.addTo(map);
                var threeCodeName = (threeCode[layer.feature.properties.CNTR_ID]).toLowerCase();
                var tooltip = '<div class="map-tooltip">' +
                  '<div class="country-name-tooltip">' +
                  '<span class="flag-icon flag-icon-' + threeCodeName +'"></span>' +
                  layer.feature.properties.CNTR_NAME +
                  "</div><div>" +
                  tpString +
                  "</div></div>";
                // Set the direction based on the map center. Bottom if the
                // country is above center, and top if it is below.
                layer.bindTooltip(tooltip, {
                  direction: layer.getBounds().getNorth() > 50 ? 'bottom' : 'top',
                  opacity: 1
                });
                // Bind an empty tooltip for the marker.
                if (marker) {
                  marker.bindTooltip('');
                }
              }

            },
          },
        })
        .addTo(map);
  });

  $(".map-toggler").on("click", function (event) {
    $(".map-wrapper").addClass("map-opened");
  });

  $(".map-close").on("click", function (event) {
    $(".map-wrapper").removeClass("map-opened");
  });

  $(".country-close").on("click", function (event) {
    $(".world-map-container").removeClass("info-opened");
  });

})(jQuery, Drupal, drupalSettings);
