// // ------------------------------------------------------------------------------------------------------------------------------
// // DATA SETTINGS
// var arrCountries = [];
// var arrCountriesAvailable = [];
// var objContinents = {};
// var objCountries = {};
// var objMarkers = {};
// var objMap;
// var objMapCenter;
// var objMapBounds;
// var $map = $('.map__container');
// var $list = $('.map__list');
// var $reset = $('.map__reset');
// var $toggleList = $('.map__toggleList');
// //var $dropdowns = $list.next('.map__dropdowns');

// // ------------------------------------------------------------------------------------------------------------------------------
// // DATA SETTINGS
var map;
var categories;
var objMapData;
var objMapConfig;
var objMapFilters;
var objMarkersConfig;
var arrMarkersInListAll = [];
var arrMarkersInListCurrent = [];
var arrMarkersAll= [];
var arrMarkersCurrent = [];

window.addEventListener('load', (event) => {
	$map           = $('.map__container');
	$legend        = $('.map__legend');
	$toggleLegend  = $('.map__legend__toggler');
	$list          = $('.map__list');
	$toggleList    = $('.map__list__toggler');
	$filters       = $('.map__filters');
	$toggleFilters = $('.map__filters__toggler');

// 	// ------------------------------------------------------------------------------------------------------------------------------
// 	// RESIZE EVENT
// 	$(window).resize(function(){
// 		var mapHeight = window.innerHeight;
// 		if($('#header').length)
// 			mapHeight -= $('#header').outerHeight();
// 		if($('#footer').length)
// 			mapHeight -= $('#footer').outerHeight();
// 		if($('.topbar').length)
// 			mapHeight -= $('.topbar').outerHeight();
// 		$map.parent().outerHeight(0).outerHeight(mapHeight);
// 	}).trigger('resize');

	$toggleList.bind('click', function(){
		$(this).toggleClass('active');
		$list.toggleClass('active');
		$legend.removeClass('active');
	});
	$toggleFilters.bind('click', function(){
		$(this).toggleClass('active');
		$filters.toggleClass('active');
	});
	$list.find('.map__list__item').on('click', function(e) {
		selectMapItem($(this).data('id'));
	});

// 	$.each(objMapData,function(index,location){
// 		if(!Object.hasKey(objContinents, location.continent.code)){
// 			objContinents[location.continent.code] = location.continent;
// 			objContinents[location.continent.code].countries = {};
// 		}
// 		if(!Object.hasKey(objContinents[location.continent.code].countries, location.country.code)){
// 			objContinents[location.continent.code].countries[location.country.code] = location.country;
// 			objCountries[location.country.code] = location.country;
// 			arrCountries.push(location.country.code);
// 			arrCountriesAvailable.push(location.country.code);
// 		}
// 		objMarkers[location.id]=location;
// 	});

// 	// Define a default value for zoom
// 	if(!objMapConfig.map.zoom)
// 		objMapConfig.map.zoom = 7;
// 	// Define a default value for lockZoom
// 	if(!objMapConfig.map.lockZoom)
// 		objMapConfig.map.lockZoom = false;
	initMap();

	// set legend
	if (objMapFilters.category) {
		for(var c in objMapFilters.category.options) {
	    	var category = objMapFilters.category.options[c];
	    	for(var i in categories){
	    		if(categories[i].id === category.value){
	    			category = categories[i];
	    			break;
	    		}
	    	}
	    	if (category.marker) {
				// add marker to legend
				$('.map__legend').append(`
					<div class="map__legend__item">
						<img src="${objMarkersConfig[category.alias].options.iconUrl}" width="${objMarkersConfig[category.alias].options.iconSize[0]}" height="${objMarkersConfig[category.alias].options.iconSize[1]}" alt="Icon for ${category.title} category"><span>${category.title}</span>
					</div>
				`);
	    	}
	    }
	    $toggleLegend.on('click',()=>{
	    	$legend.addClass('active');
	    });
	    $legend.find('.close').on('click',()=>{
	    	$legend.removeClass('active');
	    });
	    if ($legend.find('.map__legend__item').length)
	    	$toggleLegend.removeClass('hidden');
	    
	}

	// console.log('arrMarkersInListAll',arrMarkersInListAll);
	// console.log('arrMarkersInListCurrent',arrMarkersInListCurrent);
	// console.log('arrMarkersAll',arrMarkersAll);
	// console.log('arrMarkersCurrent',arrMarkersCurrent);
});
var getPopupHTML = function(obj){
	return `
		<div class="map__popup ">
			<div class="map__popup__title map__list__item__title">${obj.title}</div>
			${obj.picture ? `<div class="map__popup__picture"><img src="${obj.picture.path}" alt="${obj.title}" /></div>` :''}
			<div class="map__popup__infos map__list__item__text">
				${obj.category.title ? '<div class="map__popup__infos__line"><i class="fa fa-list"></i> '+obj.category.title+'</div>':''} 
				${obj.address ?'<div class="map__popup__infos__line "><i class="fa fa-map-marker-alt"></i> <span itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">'+obj.address+'</span></div>':''}
				${obj.phone	?'<div class="map__popup__infos__line"><i class="fa fa-phone"></i> <a href="tel:'+obj.phone+'">'+obj.phone+'</a></div>':''}
				${obj.fax		?'<div class="map__popup__infos__line"><i class="fa fa-fax"></i> '+obj.fax+'</div>':''}
				${obj.email	?'<div class="map__popup__infos__line"><i class="fa fa-envelope"></i> <a href="mailto:'+obj.email+'">'+obj.email+'</a></div>':''}
				${obj.website	?'<div class="map__popup__infos__line"><i class="fa fa-globe"></i> <a href="'+obj.website+'" target="_blank">'+obj.website+'</a></div>':''}
			</div>
			${obj.url	? `
				<div class="map__popup__actions map__list__item__link">
					<a title="<?= $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['BUTTON']['READMORE'] ?>" href="${obj.url}"></a>
				</div>
			`:''}
		</div>
	`;
}

var selectMapItem = function(itemID){
	$list.removeClass('has-selected');
	$list.find('.map__list__item').removeClass('selected');
	if (itemID) {
		$list.addClass('has-selected');
		$list.find('.map__list__item[data-id="'+itemID+'"]').addClass('selected').get(0).scrollIntoView({behavior: "smooth", block: 'center', inline: "nearest"});
	}
}

// // ------------------------------------------------------------------------------------------------------------------------------
// // UTILITIES
var normalize = function(str){return str.toLowerCase().replace(/ |\./g,'_'); }

window.addEventListener("load", function(e) {
	$.fn.filterByData = function(prop, val) {
	  return this.filter(
	      function() { return $(this).data(prop)==val; }
	  );
	}
});

// Object.hasKey = function(obj,key){
//   if(Object.keys(obj).indexOf(key) != -1)
//     return true;
//   else
//     return false;
// }