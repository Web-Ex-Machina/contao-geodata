// LEAFLET
geodata.markers.cluster    = new L.MarkerClusterGroup({});
geodata.markers.bounds  = L.latLngBounds();
var mapDefaultConfig  = {
	zoom                 : 8,
	minZoom              : 3,
	maxZoom              : 13,
	zoomControl          : true,
	zoomControlPosition  : 'bottomleft',
	gestureHandling  	 : true,
	fitBounds  	 		 : true,
	doubleClickZoom	     : false,
	mapUrl               : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	mapAttribution       : '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
	marker               :  
	{
		iconUrl          : 'bundles/wemgeodata/img/Icon_default.png',
		iconSize         : [60,60],
	}
};

geodata.functions.initMap = function(){return new Promise(function(resolve,reject){
	console.log('leaflet initMap start');
	if (geodata.config.map                	   === undefined ) geodata.config.map               	  = {};
	if (geodata.config.map.zoom                === undefined ) geodata.config.map.zoom                = mapDefaultConfig.zoom;
	if (geodata.config.map.zoomControl         === undefined ) geodata.config.map.zoomControl         = mapDefaultConfig.zoomControl;
	if (geodata.config.map.zoomControlPosition === undefined ) geodata.config.map.zoomControlPosition = mapDefaultConfig.zoomControlPosition;
	if (geodata.config.map.gestureHandling     === undefined ) geodata.config.map.gestureHandling     = mapDefaultConfig.gestureHandling;
	if (geodata.config.map.fitBounds           === undefined ) geodata.config.map.fitBounds           = mapDefaultConfig.fitBounds;
	if (geodata.config.map.doubleClickZoom     === undefined ) geodata.config.map.doubleClickZoom     = mapDefaultConfig.doubleClickZoom;

	if (geodata.config.tileLayer               === undefined) geodata.config.tileLayer                = {};
	if (geodata.config.tileLayer.url           === undefined) geodata.config.tileLayer.url            = mapDefaultConfig.mapUrl;
	if (geodata.config.tileLayer.attribution   === undefined) geodata.config.tileLayer.attribution    = mapDefaultConfig.mapAttribution;
	if (geodata.config.tileLayer.minZoom       === undefined) geodata.config.tileLayer.minZoom        = mapDefaultConfig.minZoom;
	if (geodata.config.tileLayer.maxZoom       === undefined) geodata.config.tileLayer.maxZoom        = mapDefaultConfig.maxZoom;

	if (parseInt(geodata.config.map.zoom)<parseInt(geodata.config.tileLayer.minZoom)) geodata.config.map.zoom = geodata.config.tileLayer.minZoom;
	if (parseInt(geodata.config.map.zoom)>parseInt(geodata.config.tileLayer.maxZoom)) geodata.config.map.zoom = geodata.config.tileLayer.maxZoom;

	let refSize = geodata.config.icon?.iconSize?.split(',').map(Number) || mapDefaultConfig.marker.iconSize;
	geodata.markers.config = {
		'default': L.icon({
			iconUrl: 	   	geodata.config.icon?.iconUrl 								|| mapDefaultConfig.marker.iconUrl,
		    iconSize:      	refSize, // taille de l'icone
		    iconAnchor:    	geodata.config.icon?.iconAnchor?.split(',').map(Number) 	|| [refSize[0]/2,refSize[1]], // point de l'icone qui correspondra à la position du marker
		    popupAnchor:   	geodata.config.icon?.popupAnchor?.split(',').map(Number) 	|| [0,refSize[1]*-1], // point depuis lequel la popup doit s'ouvrir relativement à l'iconAnchor
		    tooltipAnchor: 	geodata.config.icon?.popupAnchor?.split(',').map(Number) 	|| [refSize[0]/3,refSize[1]*-0.5],
		})
	}


	if (geodata.markers.categories) {
	    for(var c in geodata.markers.categories) {
	    	var category = geodata.markers.categories[c];
	    	// find selected category in categories list
	    	for(var i in geodata.markers.categories){
	    		if(geodata.markers.categories[i].id === category.value){
	    			category = geodata.markers.categories[i];
	    			break;
	    		}
	    	}
	    	category.alias = geodata.utils.normalize(category.title);
	    	if (category.marker) {
	    		// console.log(category.marker);
	    		geodata.markers.config[category.alias] = L.icon({
					iconUrl: 		 (category.marker.icon.iconUrl       !== undefined)                                                       ? category.marker.icon.iconUrl                   : geodata.markers.config.default.options.iconUrl,
				    iconSize:     	 (category.marker.icon.iconSize      !== undefined && Array.isArray(category.marker.icon.iconSize))	      ? category.marker.icon.iconSize.map(Number)  	   : geodata.markers.config.default.options.iconSize,
				    iconAnchor:   	 (category.marker.icon.iconAnchor    !== undefined && Array.isArray(category.marker.icon.iconAnchor))     ? category.marker.icon.iconAnchor.map(Number)    : geodata.markers.config.default.options.iconAnchor,
				    popupAnchor:  	 (category.marker.icon.popupAnchor   !== undefined && Array.isArray(category.marker.icon.popupAnchor))	  ? category.marker.icon.popupAnchor.map(Number)   : geodata.markers.config.default.options.popupAnchor,
				    tooltipAnchor: 	 (category.marker.icon.tooltipAnchor !== undefined && Array.isArray(category.marker.icon.tooltipAnchor))  ? category.marker.icon.tooltipAnchor.map(Number) : geodata.markers.config.default.options.tooltipAnchor,
				});
				// console.log(geodata.markers.config[category.alias]);
	    	}
	    }
	}


    geodata.map = L.map('map',{
		zoomControl : geodata.config.map.zoomControl,
		gestureHandling: geodata.config.map.gestureHandling,
		doubleClickZoom: geodata.config.map.doubleClickZoom,
    }).setView([48.833, 2.333], geodata.config.map.zoom);
	geodata.map.attributionControl.setPosition('bottomleft');
	if (geodata.config.map.zoomControl)
		geodata.map.zoomControl.setPosition(mapDefaultConfig.zoomControlPosition);

    let layer = L.tileLayer(geodata.config.tileLayer.url,
    	{
			attribution: geodata.config.tileLayer.attribution,
			subdomains: 'abc',
			minZoom: (geodata.config.tileLayer.minZoom?geodata.config.tileLayer.minZoom:3),
			maxZoom: (geodata.config.tileLayer.maxZoom?geodata.config.tileLayer.maxZoom:13),
			ext: 'png',
			noWrap: true,
		}
	);
	geodata.map.addLayer(layer);


	let southWest = geodata.config.map.southWestBound ? L.latLng(parseFloat(geodata.config.map.southWestBound.split(',')[0]), parseFloat(geodata.config.map.southWestBound.split(',')[1])) : L.latLng(-65, -180);
	let northEast = geodata.config.map.northEastBound ? L.latLng(parseFloat(geodata.config.map.northEastBound.split(',')[0]), parseFloat(geodata.config.map.northEastBound.split(',')[1])) : L.latLng(88, 180);
	let bounds = L.latLngBounds(southWest, northEast);
	geodata.map.setMaxBounds(bounds);

	// make sure we have some pertinent boundaries to rely on
	if (!geodata.markers.bounds.hasOwnProperty('_southWest') || !geodata.markers.bounds.hasOwnProperty('_northEast')) 
		geodata.markers.bounds = bounds;

	if (geodata.config.map.center)
		geodata.map.setView(L.latLng({lat: parseFloat(geodata.config.map.center.split(',')[0]), lng: parseFloat(geodata.config.map.center.split(',')[1])}), geodata.config.map.zoom);
	else
		geodata.map.setView(geodata.markers.bounds.getCenter(), geodata.config.map.zoom);
		// geodata.map.fitWorld();

	// MAP EVENTS
	geodata.map.on('drag', function() {geodata.map.panInsideBounds(bounds, { animate: false }); });
	geodata.map.on('click', function() {geodata.functions.selectMapItem(false) });
	geodata.map.on('dblclick', function() {
		geodata.functions.selectMapItem(false); 
		$legend.removeClass('active'); 
		$list.removeClass('active'); 
		$filters.removeClass('active'); 
	});
	    
	resolve('leaflet initMap done');
});}


geodata.functions.addMarkers = function(locations = [],doUpdateLayers=true,doFitBounds=false){return new Promise(function(resolve,reject){
	console.log('leaflet addMarkers start');
	var arrPromises = [];
	for(var location of locations){
		// setup marker parameters
		if('' != location.lat && '' != location.lng){
			var latLng = L.latLng({lat: parseFloat(location.lat), lng: parseFloat(location.lng)});
		}else{
			var latLng = L.latLng({lat: 0, lng: 0});
		}
		var options = {};
		if(Array.isArray(location.category)){
			if (location.category.length > 0 && location.category[0].title && geodata.markers.config.hasOwnProperty(geodata.utils.normalize(location.category[0].title)))
				options.icon = geodata.markers.config[geodata.utils.normalize(location.category[0].title)];
			else
				options.icon = geodata.markers.config.default;
		} else {
			if (location.category.title && geodata.markers.config.hasOwnProperty(geodata.utils.normalize(location.category.title)))
				options.icon = geodata.markers.config[geodata.utils.normalize(location.category.title)];
			else
				options.icon = geodata.markers.config.default;
		}

		// construct marker
		arrPromises.push(geodata.functions.addMarker(latLng,options,location,false))
	};

	Promise.all(arrPromises).then(r=>{
		if (doUpdateLayers)
			geodata.functions.updateLayers(doFitBounds);
		resolve('leaflet addMarkers done')
	})
})}

geodata.functions.updateLayers = function(doFitBounds=false){
	geodata.markers.current = geodata.markers.all.slice();
	geodata.markersInList.current = geodata.markersInList.all.slice();
	geodata.markers.cluster.addLayers(geodata.markers.current);
	geodata.markers.bounds = geodata.markers.cluster.getBounds();
	geodata.map.addLayer(geodata.markers.cluster);
	if (doFitBounds){
		geodata.functions.fitBounds()
	}
}

geodata.functions.fitBounds = function(){
	geodata.map.fitBounds(geodata.markers.cluster.getBounds())
}

geodata.functions.addMarker = function(latLng, options={'icon':geodata.markers.config.default}, location=false, updateLayers=true){
	return new Promise(function(resolve,reject){
		var marker = new L.marker(latLng, options);
		if (location) {
			var markerInList  = {id:location.id};
			marker.locationID = location.id
			marker.bindTooltip(location.title);
			marker.bindPopup(geodata.functions.getPopupHTML(location));
			marker.on('click',function(){
				geodata.functions.selectMapItem(this.locationID);
			});
			// setup for filters
				// console.log(geodata.filters);
			for(var f in geodata.filters) {
				marker['filter_'+f] = '';
				markerInList['filter_'+f] = '';
				markerInList.filter_search = $('.map__list__item[data-id='+location.id+']').text()+' '+$(geodata.functions.getPopupHTML(location)).text();
				marker.filter_search       = $('.map__list__item[data-id='+location.id+']').text()+' '+$(geodata.functions.getPopupHTML(location)).text();
				if (location.hasOwnProperty(f)) {
					switch(f){
						case 'category': 
							if (Array.isArray(location[f])) {
								for (var category of location[f]){
									// console.log(normalize(category.title));
									// marker['filter_'+f] += geodata.utils.normalize(category.title);
									// markerInList['filter_'+f] += geodata.utils.normalize(category.title);
								}
								marker['filter_'+f]       = location[f].map(function(category){return geodata.utils.normalize(category.title); }).join(',');
								markerInList['filter_'+f] = location[f].map(function(category){return geodata.utils.normalize(category.title); }).join(',');
							} else if(location[f].id){
								marker['filter_'+f] = geodata.utils.normalize(location[f].id);
								markerInList['filter_'+f] = geodata.utils.normalize(location[f].id);
							}
						break;
						case 'country':
							marker['filter_'+f] = geodata.utils.normalize(location[f].code);
							markerInList['filter_'+f] = geodata.utils.normalize(location[f].code);
						break;
						default: 
							if (typeof location[f] === 'string'){
								marker['filter_'+f] = geodata.utils.normalize(location[f]);
								markerInList['filter_'+f] = geodata.utils.normalize(location[f]);
							}
						break;
					}
				}
			}
		}


		// register marker
		geodata.markers.bounds.extend(latLng); 
		geodata.markers.all.push(marker); 
		geodata.markersInList.all.push(markerInList); 

		if (updateLayers)
			updateLayers();

		resolve()
	});
}

geodata.functions.selectMapItem = function(itemID){
	$list.removeClass('has-selected');
	$map.find('.leaflet-marker-pane').removeClass('has-selected');
	$list.find('.map__list__item').removeClass('selected');
	$map.find('.leaflet-marker-pane .leaflet-marker-icon').removeClass('selected');
	if (itemID) {
		$list.addClass('has-selected');
		$list.find('.map__list__item[data-id="'+itemID+'"]').addClass('selected').get(0).scrollIntoView({behavior: "smooth", block: 'center', inline: "nearest"});
		var marker = geodata.markers.all.filter(function(marker){
		    return marker.locationID == itemID
		});
		if (marker[0]._icon) {
			$map.find('.leaflet-marker-pane').addClass('has-selected');
			$(marker[0]._icon).addClass('selected')
		} else{
		 	geodata.markers.cluster.zoomToShowLayer(marker[0],()=>{geodata.functions.selectMapItem(itemID)})
		}
		geodata.map.panTo(marker[0].getLatLng())
	}
}

geodata.callbacks.applyFilters = function(){
	return new Promise(function(resolve,reject){
		geodata.markers.cluster.removeLayers(geodata.markers.all);
		geodata.markers.cluster.addLayers(geodata.markers.current);
		if (geodata.config.map.fitBounds)
			geodata.functions.fitBounds();
			// geodata.map.fitBounds(geodata.markers.cluster.getBounds());
		resolve()
	});
}

geodata.markers.cluster.on('clusterclick', function (a) {
	geodata.functions.selectMapItem(false);
	if ($list) $list.removeClass('active');
	if ($filters) $filters.removeClass('active');
});