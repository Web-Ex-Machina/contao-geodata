// ------------------------------------------------------------------------------------------------------------------------------
// DATA SETTINGS
var arrCountries = [];
var arrCountriesAvailable = [];
var objContinents = {};
var objCountries = {};
var objMarkers = {};
var objMap;
var objMapCenter;
var objMapBounds;
var $map = $('.map__container');
var $list = $('.map__list');
var $reset = $('.map__reset');
var $toggleList = $('.map__toggleList');
//var $dropdowns = $list.next('.map__dropdowns');

window.addEventListener('load', (event) => {
	$map = $('.map__container');
	$list = $('.map__list');
	$reset = $('.map__reset');
	$toggleList = $('.map__toggleList');

	// ------------------------------------------------------------------------------------------------------------------------------
	// RESIZE EVENT
	$(window).resize(function(){
		var mapHeight = window.innerHeight;
		if($('#header').length)
			mapHeight -= $('#header').outerHeight();
		if($('#footer').length)
			mapHeight -= $('#footer').outerHeight();
		if($('.topbar').length)
			mapHeight -= $('.topbar').outerHeight();
		$map.parent().outerHeight(0).outerHeight(mapHeight);
	}).trigger('resize');

	$toggleList.bind('click', function(){
		$(this).toggleClass('active');
		$list.toggleClass('active');
		$map.toggleClass('full');
	});
	$list.find('.map__list__item').on('click', function(e) {
		selectMapItem($(this).data('id'));
	});

	$.each(objMapData,function(index,location){
		if(!Object.hasKey(objContinents, location.continent.code)){
			objContinents[location.continent.code] = location.continent;
			objContinents[location.continent.code].countries = {};
		}
		if(!Object.hasKey(objContinents[location.continent.code].countries, location.country.code)){
			objContinents[location.continent.code].countries[location.country.code] = location.country;
			objCountries[location.country.code] = location.country;
			arrCountries.push(location.country.code);
			arrCountriesAvailable.push(location.country.code);
		}
		objMarkers[location.id]=location;
	});

	// Define a default value for zoom
	if(!objMapConfig.map.zoom)
		objMapConfig.map.zoom = 7;
	// Define a default value for lockZoom
	if(!objMapConfig.map.lockZoom)
		objMapConfig.map.lockZoom = false;

	initMap();
});

function selectMapItem(itemID){
	$('.map__list .map__list__item').removeClass('selected');
	$('.map__list .map__list__item[data-id="'+itemID+'"]').addClass('selected');
	var offset = ($('.map__list .map__list__item[data-id="'+itemID+'"]').position().top >= 0)?$('.map__list .map__list__item[data-id="'+itemID+'"]').position().top:0;
	offset -= $('.map__list .map__list__item[data-id="'+itemID+'"]').outerHeight();
	$('.map__list .map__list__wrapper').stop().animate({
      scrollTop: offset
  },400);
}

// ------------------------------------------------------------------------------------------------------------------------------
// UTILITIES
$.fn.filterByData = function(prop, val) {
  return this.filter(
      function() { return $(this).data(prop)==val; }
  );
}

Object.hasKey = function(obj,key){
  if(Object.keys(obj).indexOf(key) != -1)
    return true;
  else
    return false;
}