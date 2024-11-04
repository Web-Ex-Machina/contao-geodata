// ------------------------------------------------------------------------------------------------------------------------------
// DATA SETTINGS
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
var filters = {};
var mapModuleId;
var rt;
var limit = 100;
var blnLoadInAjax = false;

// providers functions, needs to be overriden in the proper dedicated file (eg. leaflet.js)
var applyFilters_callback = function(){};
var initMap = function(){
	return new Promise(function(resolve,reject){
	    resolve()
	});
}

// ONLAD
window.addEventListener('load', (event) => {
	if(blnLoadInAjax){
		var loadingOverlay = $('.map__loading__overlay');
		if(loadingOverlay){
			loadingOverlay.toggleClass('hidden');
		}
		countLocationsAjax().then(r => {
			var nbElementsToManage = r.count; // total number of elements to manage
			if(nbElementsToManage > 0){
				loopGetLocationsItemsPagined(nbElementsToManage, 0, limit)
				.then(function(r){

					getFiltersAjax().then(r => {
						if(r.html.length > 0){
							$('.map__filters__container').html(r.html);
						}
						objMapFilters = JSON.parse(r.json);
						initMapGlobal();
						if(loadingOverlay){
							loadingOverlay.toggleClass('hidden');
						}
					});
				})
				.catch(function(msg){
					alert(msg);
				});
			}
		})
		.catch(function(msg){
			alert(msg);
		});
	}else{
		initMapGlobal();
	}
});

var initMapGlobal = function(){
	$map           = $('.map__container');
	$legend        = $('.map__legend');
	$toggleLegend  = $('.map__legend__toggler');
	$list          = $('.map__list');
	$toggleList    = $('.map__list__toggler');
	$filters       = $('.map__filters');
	$toggleFilters = $('.map__filters__toggler');


	// LIST events
	$toggleList.bind('click', function(){
		$list.toggleClass('active');
		// $legend.removeClass('active');
	});
	$toggleFilters.bind('click', function(){
		$filters.toggleClass('active');
	});
	$list.find('.map__list__item').on('click', function(e) {
		selectMapItem($(this).data('id'));
	});

	// FILTERS events
	$('.locations__filters, .map__filters').find('[id^=filter_]').on('change keyup', function(){
		$('.locations__filters, .map__filters').find('[id^=filter_]').each(function(){
			filters[this.name] = this.value;
		});
		applyFilters();
	});

	initMap().then((r) => {
		// set legend after map init
		if (categories) {
			for(var c in categories) {
		    	var category = categories[c];
		    	for(var i in categories){
		    		if(categories[i].id === category.value){
		    			category = categories[i];
		    			break;
		    		}
		    	}
				// add marker to legend
				$legend.append(`
					<div class="map__legend__item">
						<img src="${objMarkersConfig[category.marker?category.alias:'default'].options.iconUrl}" width="${objMarkersConfig[category.marker?category.alias:'default'].options.iconSize[0]}" height="${objMarkersConfig[category.marker?category.alias:'default'].options.iconSize[1]}" alt="Icon for ${category.title} category"><span>${category.title}</span>
					</div>
				`);
		    }
		    $toggleLegend.on('click',()=>{
		    	$legend.addClass('active');
		    });
		    $legend.find('.close').on('click',()=>{
		    	$legend.removeClass('active');
		    });
		    if ($legend.find('.map__legend__item').length && categories.length>1)
		    	$toggleLegend.removeClass('hidden');
		    
		}

		// manually trigger filters
		$('.locations__filters, .map__filters').find('[id^=filter_]').first().trigger('change');
		// console.log('objMapFilters',objMapFilters);
		// console.log('arrMarkersInListAll',arrMarkersInListAll);
		// console.log('arrMarkersInListCurrent',arrMarkersInListCurrent);
		// console.log('arrMarkersAll',arrMarkersAll);
		// console.log('arrMarkersCurrent',arrMarkersCurrent);
	});
};

var applyFilters = function(){
	// console.log(filters);
	arrMarkersCurrent = arrMarkersAll.filter( item => {
		var match = true;
		// console.log(item);
		for(var f in filters){
			if (f == "search" || f == "category") {
				if (filters[f] !== '' && item['filter_'+f].search(new RegExp(filters[f],'i')) == -1)
					match = false;
			} else { // input search code
				if (filters[f] !== '' && item['filter_'+f] !== filters[f]){
					if("undefined" !== typeof item['filter_'+f] && -1 != item['filter_'+f].indexOf(',')){
						var values = item['filter_'+f].split(',');
						match = values.includes(filters[f]);
					}else{
						match = false;
					}
				}
			}
		}
		return match;
	});
	// console.log("==========");
	// console.log(arrMarkersInListAll);
	arrMarkersInListCurrent = arrMarkersInListAll.filter( item => {
		var match = true;
		for(var f in filters){
			if (f == "search" || f == "category") {
				if (filters[f] !== '' && item['filter_'+f].search(new RegExp(filters[f],'i')) == -1){
					match = false;
					return false;
				}
			} else { // input search code
				if (filters[f] !== '' && item['filter_'+f] !== filters[f]){
					if("undefined" !== typeof item['filter_'+f] && -1 != item['filter_'+f].indexOf(',')){
						var values = item['filter_'+f].split(',');
						match = values.includes(filters[f]);
						return match;
					}else{
						match = false;
						return false;
					}
				}
			}
		}
		return match;
	});
	// console.log(arrMarkersInListCurrent);

	arrMarkersInListAll.forEach(item=>{
		var item1 = $('.location[data-id="'+item.id+'"]');
		var item2 = $('.map__list__item[data-id="'+item.id+'"]');
		if(-1 === arrMarkersInListCurrent.indexOf(item)){
			if(item1)
				item1.addClass('hidden');
			if(item2)
				item2.addClass('hidden');
		}else{
			if(item1)
				item1.removeClass('hidden');
			if(item2)
				item2.removeClass('hidden');
		}
	});
	
	applyFilters_callback();
}

var getPopupHTML = function(obj){
	return `
		<div class="map__popup ">
			<div class="map__popup__title map__list__item__title"> ${obj.title} </div>
        	${obj.category.title && categories.length>1 ? '<p class="opa-4 ft-l m-top-0">'+obj.category.title.toUpperCase()+'</p>':''}
        	${Array.isArray(obj.category) && categories.length>1 ? '<p class="opa-4 ft-l m-top-0">'+(obj.category.map(function(c){return c.title})).join(', ').toUpperCase()+'</p>':''}
			${obj.picture ? `<div class="map__popup__picture"><img src="${obj.picture.path}" alt="${obj.title}" /></div>` :''}
			<div class="map__popup__infos map__list__item__text">
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

var loopGetLocationsItemsPagined = function(nbElementsToManage, offset, limit){
	return new Promise(function (resolve, reject) {
		getLocationsItemsPaginedAjax(offset, limit)
		.then(function(r){
			if("error" == r.status) {
				reject(r.msg);
			} else {
				offset = offset+limit;

				// append results in list
				if(r.html.length > 0){
					for(var item of r.html){
						$('.map__list__wrapper').append(item);
					}
				}
				// append results in JS list
				objMapData = objMapData.concat(JSON.parse(r.json));
				if(offset < nbElementsToManage){
					loopGetLocationsItemsPagined(nbElementsToManage, offset, limit)
					.then(r=>resolve(r))
					.catch(msg=>reject(msg))
					;
				}else{
					resolve(r);
				}
			}
		})
		.catch(function(msg){
			reject(msg);
		});
	});
}

var getLocationsListAjax = function(){
	return new Promise(function(resolve,reject){
		var request = new FormData();

		request.append('TL_AJAX', 1);
	    request.append('REQUEST_TOKEN', rt);
	    request.append('module', mapModuleId);
	    request.append('action', 'getLocationsList');

		fetch(window.location,{
			method: 'POST',
			mode: 'same-origin',
			cache: 'no-cache',
			body: request
		})
		.then((response) => response.json())
		.then((json) => {
		  resolve(json);
		})
		.catch((error) => {
		    reject(error);
		});
	});
};
var countLocationsAjax = function(){
	return new Promise(function(resolve,reject){
		var request = new FormData();

		request.append('TL_AJAX', 1);
	    request.append('REQUEST_TOKEN', rt);
	    request.append('module', mapModuleId);
	    request.append('action', 'countLocations');

		fetch(window.location,{
			method: 'POST',
			mode: 'same-origin',
			cache: 'no-cache',
			body: request
		})
		.then((response) => response.json())
		.then((json) => {
		  resolve(json);
		})
		.catch((error) => {
		    reject(error);
		});
	});
};

var getLocationsItemsPaginedAjax = function(offset, limit){
	return new Promise(function(resolve,reject){
		var request = new FormData();

		request.append('TL_AJAX', 1);
	    request.append('REQUEST_TOKEN', rt);
	    request.append('module', mapModuleId);
	    request.append('offset', offset);
	    request.append('limit', limit);
	    request.append('action', 'getLocationsItemsPagined');

		fetch(window.location,{
			method: 'POST',
			mode: 'same-origin',
			cache: 'no-cache',
			body: request
		})
		.then((response) => response.json())
		.then((json) => {
		  resolve(json);
		})
		.catch((error) => {
		    reject(error);
		});
	});
};

var getFiltersAjax = function(){
	return new Promise(function(resolve,reject){
		var request = new FormData();

		request.append('TL_AJAX', 1);
	    request.append('REQUEST_TOKEN', rt);
	    request.append('module', mapModuleId);
	    request.append('action', 'getFilters');

		fetch(window.location,{
			method: 'POST',
			mode: 'same-origin',
			cache: 'no-cache',
			body: request
		})
		.then((response) => response.json())
		.then((json) => {
		  resolve(json);
		})
		.catch((error) => {
		    reject(error);
		});
	});
};
// ------------------------------------------------------------------------------------------------------------------------------
// UTILITIES
var normalize = function(str = ''){return str.toLowerCase().replace(/ |\.|\'/g,'_'); }

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
