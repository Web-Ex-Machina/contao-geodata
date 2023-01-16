function initMap() {
  objMarkers = {};
  $.each(objMapData,function(index,location){
    objMarkers[location.country.code+'-'+location.title.toLowerCase().replace(/\s/g,'_')]={
      country: location.country.code,
      continent: location.continent.code,
      title: location.title,
      latLng: [location.lat,location.lng]
    };
  });

  // ------------------------------------------------------------------------------------------------------------------------------
  // MAP CONFIG
  mapConfig = {
    map: $map.data('map')+'_mill',
    container: $map,
    backgroundColor: getMapData('mapBackground','#fff'),
    zoomOnScroll: getMapData('zoomOnScroll',false),
    panOnDrag: getMapData('panOnDrag',false),
    regionsSelectable: getMapData('regionsSelectable',true),
    regionsSelectableOne: getMapData('regionsSelectableOne',true),
    markersSelectable: getMapData('markersSelectable',true),
    markersSelectableOne: getMapData('markersSelectableOne',true),
    regionStyle: {
      initial: {
        fill: getMapData('regionBackground','#ddd'),
        "fill-opacity": 1,
        stroke: 'none',
        "stroke-width": 0,
        "stroke-opacity": 1
      },
      hover: {
        "fill-opacity": 1,
        cursor: "pointer"
      },
      selected: {
        fill: getMapData('regionBackgroundSelected','#666'),
        "fill-opacity": 1,
      },
      selectedHover: {
        fill: getMapData('regionBackgroundSelectedHover','#666'),
        "fill-opacity": 1,
      }
    },
    markerStyle:{
      initial: {
        fill: getMapData('markerBackground','#666'),
        stroke: '#333',
        "fill-opacity": 1,
        "stroke-width": 2,
        "stroke-opacity": 1,
        r: 10
      },
      hover: {
        fill: getMapData('markerBackgroundHover','#999'),
        stroke: '#fff',
        "stroke-width": 2,
        cursor: 'pointer'
      },
      selected: {
        fill: getMapData('markerBackgroundHover','#999'),
        "stroke-width": 2,
        stroke: '#fff',
      },
      selectedHover: {}
    },
    series:{
      regions: [{
        attribute: 'fill',
        scale:{
          '0': getMapData('regionBackground','#ddd'),
          '1': getMapData('regionBackgroundActive','#999')
        },
      }]
    },
    onRegionClick: function(e,code){},
    onRegionSelected: function(e, code, selected, selectedRegions){
      if(selected && objMapData.length != 0){
        if(arrCountriesAvailable.indexOf(code) != -1){
          if(!objMapConfig.map.lockZoom){
            objMap.setFocus({
              region: code,
              animate: true
            });
          }

          $reset.addClass('active');
          $list.find('.map__list__title .location').html(objCountries[code].name);
          setMapMarkers([code]);
        }
        else{
          resetMap();
        }
      }
    },
    onRegionTipShow: function(e,tip,code){
      tip.unwrap();
      if(arrCountriesAvailable.indexOf(code) != -1)
        tip.html(objCountries[code].name)
      else{
        if(getMapData('regionLock',true))
          tip.wrap('<div style="display:none"></div>');
      }
    },
    onRegionOver: function(e,code){},
    onRegionOut: function(e,code){},
    onMarkerClick: function(e,code){},
    onMarkerOver: function(e,code){
      $list.find('.map__list__item').filterByData('marker',code).addClass('hover');
    },
    onMarkerOut: function(e,code){
      $list.find('.map__list__item').filterByData('marker',code).removeClass('hover');
    },
    onMarkerSelected: function(e,code){
      $list.find('.map__list__item').removeClass('selected');
      $list.find('.map__list__item').filterByData('marker',code).addClass('selected');
    },
  };

  // override config when there is active regions or not
  if(getMapData('regionLock',true) && objMapData.length != 0){
    mapConfig.regionStyle.hover.cursor = 'default';
  }
  else{
    mapConfig.regionStyle.hover.fill = getMapData('regionBackgroundHover','#999');
  }

  // init the map
  objMap = new jvm.Map(mapConfig);

  if(!getMapData('zoomOnScroll',false))
    $map.addClass('no-buttons');

  // set the active regions and a cursor on them
  objMap.series.regions[0].setValues(getMapSeries());
  $map.find('path[fill*="'+getMapData('regionBackgroundActive','#999')+'"]').css('cursor','pointer');

  objMap.updateSize();
  setMapCountriesAvailable(true,false);

  // ------------------------------------------------------------------------------------------------------------------------------
  // MAP FUNCTIONS
  function getMapData(label,defaultValue){
    // label = label.toLowerCase();
    if(objMapConfig[label] !== undefined && objMapConfig[label] !== "")
      return objMapConfig[label];
    else
      return defaultValue;
  }
  function getMapSeries(){
    var result = {};
    $.each(arrCountries,function(key,code){
      if(arrCountriesAvailable.indexOf(code) != -1)
        result[code] = '1';
      else
        result[code] = '0';
    });
    return result;
  }
  function setMapMarkers(arrCodes){
    objMap.removeAllMarkers();
    $.each(objMarkers,function(key,marker){
      if(arrCodes.indexOf(marker.country) != -1)
        objMap.addMarker(key,marker);
    });

    $list.find('.map__list__item').removeClass('active selected hover');
    $.each(arrCodes,function(index,code){
      $list.find('.map__list__item').filterByData('country',code).addClass('active');
    });
    $list.addClass('active');
  };

  function setMapCountriesAvailable(){
    var zoom = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
    var animate = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;

    objMap.series.regions[0].setValues(getMapSeries());
    objMap.updateSize();
    if(zoom){
      var hasOneRegion = arrCountriesAvailable.some(function(key){return Object.keys(objMap.regions).includes(key); });
      if(!objMapConfig.map.lockZoom){
        objMap.setFocus({
          regions: arrCountriesAvailable,
          animate: animate
        });
      }
    }
  };

  function resetMap(){
    $reset.removeClass('active');
    $list.removeClass('active');
    $list.find('.map__list__item').removeClass('active selected hover');
    objMap.removeAllMarkers();
    objMap.clearSelectedRegions();

    arrCountriesAvailable = arrCountries.slice();
    setMapCountriesAvailable();
  };

  // ------------------------------------------------------------------------------------------------------------------------------
  // DOM EVENTS
  $reset.on('click',function(){
    resetMap();
  });

  $list.find('.map__list__item').on('click',function(e){
    objMap.clearSelectedMarkers();
    objMap.setSelectedMarkers($(this).data('marker'));
  }).on('mouseenter',function(e){
    $(objMap.markers[$(this).data('marker')].element.shape.node).trigger('mouseenter');
  }).on('mouseleave',function(e){
    $(objMap.markers[$(this).data('marker')].element.shape.node).trigger('mouseleave');
  });

  var bufferWheel = 0;
  var timerWheel;
  $map.find('.jvectormap-container').on('mousewheel', function(event) {
    clearTimeout(timerWheel);
    if(event.deltaY == -1 && $reset.hasClass('active')){
      if(bufferWheel == 3)
        resetMap();
      else
        bufferWheel++;
    }
    timerWheel = setTimeout(function(){
      bufferWheel = 0;
    },300);
  });
}