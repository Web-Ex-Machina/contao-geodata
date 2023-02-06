// LEAFLET
var markersCluster    = new L.MarkerClusterGroup({});
var objMarkersBounds  = L.latLngBounds();
var mapDefaultConfig  = {
	zoom                 : 3,
	minZoom              : 3,
	maxZoom              : 13,
	zoomControl          : true,
	zoomControlPosition  : 'bottomleft',
	gestureHandling  	 : true,
	fitBounds  	 		 : true,
	mapUrl               : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	mapAttribution       : '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
	marker               :  
	{
		iconUrl          : 'bundles/wemgeodata/img/Icon_default.png',
		iconSize         : [60,60],
	}
};


initMap = function() {
	return new Promise(function(resolve,reject){
		if (objMapConfig.map                	 === undefined ) objMapConfig.map               	  = {};
		if (objMapConfig.map.zoom                === undefined ) objMapConfig.map.zoom                = mapDefaultConfig.zoom;
		if (objMapConfig.map.zoomControl         === undefined ) objMapConfig.map.zoomControl         = mapDefaultConfig.zoomControl;
		if (objMapConfig.map.zoomControlPosition === undefined ) objMapConfig.map.zoomControlPosition = mapDefaultConfig.zoomControlPosition;
		if (objMapConfig.map.gestureHandling     === undefined ) objMapConfig.map.gestureHandling     = mapDefaultConfig.gestureHandling;
		if (objMapConfig.map.fitBounds           === undefined ) objMapConfig.map.fitBounds           = mapDefaultConfig.fitBounds;

		if (objMapConfig.tileLayer               === undefined) objMapConfig.tileLayer                = {};
		if (objMapConfig.tileLayer.url           === undefined) objMapConfig.tileLayer.url            = mapDefaultConfig.mapUrl;
		if (objMapConfig.tileLayer.attribution   === undefined) objMapConfig.tileLayer.attribution    = mapDefaultConfig.mapAttribution;
		if (objMapConfig.tileLayer.minZoom       === undefined) objMapConfig.tileLayer.minZoom        = mapDefaultConfig.minZoom;
		if (objMapConfig.tileLayer.maxZoom       === undefined) objMapConfig.tileLayer.maxZoom        = mapDefaultConfig.maxZoom;

		if (parseInt(objMapConfig.map.zoom)<parseInt(objMapConfig.tileLayer.minZoom)) objMapConfig.map.zoom = objMapConfig.tileLayer.minZoom;
		if (parseInt(objMapConfig.map.zoom)>parseInt(objMapConfig.tileLayer.maxZoom)) objMapConfig.map.zoom = objMapConfig.tileLayer.maxZoom;

		var refSize = objMapConfig.icon?.iconSize?.split(',').map(Number) || mapDefaultConfig.marker.iconSize;
		objMarkersConfig = {
			'default': L.icon({
				iconUrl: 	   	objMapConfig.icon?.iconUrl 								|| mapDefaultConfig.marker.iconUrl,
			    iconSize:      	refSize, // taille de l'icone
			    iconAnchor:    	objMapConfig.icon?.iconAnchor?.split(',').map(Number) 	|| [refSize[0]/2,refSize[1]], // point de l'icone qui correspondra à la position du marker
			    popupAnchor:   	objMapConfig.icon?.popupAnchor?.split(',').map(Number) 	|| [0,refSize[1]*-1], // point depuis lequel la popup doit s'ouvrir relativement à l'iconAnchor
			    tooltipAnchor: 	objMapConfig.icon?.popupAnchor?.split(',').map(Number) 	|| [refSize[0]/3,refSize[1]*-0.5],
			})
		}

		if (objMapFilters.category) {
		    for(var c in objMapFilters.category.options) {
		    	var category = objMapFilters.category.options[c];
		    	// find selected category in categories list
		    	for(var i in categories){
		    		if(categories[i].id === category.value){
		    			category = categories[i];
		    			break;
		    		}
		    	}
		    	category.alias = normalize(category.title);
		    	if (category.marker) {
		    		// console.log(category.marker);
		    		objMarkersConfig[category.alias] = L.icon({
						iconUrl: 		 (category.marker.icon.iconUrl       !== undefined)                                                       ? category.marker.icon.iconUrl                   : objMarkersConfig.default.options.iconUrl,
					    iconSize:     	 (category.marker.icon.iconSize      !== undefined && Array.isArray(category.marker.icon.iconSize))	      ? category.marker.icon.iconSize.map(Number)  	   : objMarkersConfig.default.options.iconSize,
					    iconAnchor:   	 (category.marker.icon.iconAnchor    !== undefined && Array.isArray(category.marker.icon.iconAnchor))     ? category.marker.icon.iconAnchor.map(Number)    : objMarkersConfig.default.options.iconAnchor,
					    popupAnchor:  	 (category.marker.icon.popupAnchor   !== undefined && Array.isArray(category.marker.icon.popupAnchor))	  ? category.marker.icon.popupAnchor.map(Number)   : objMarkersConfig.default.options.popupAnchor,
					    tooltipAnchor: 	 (category.marker.icon.tooltipAnchor !== undefined && Array.isArray(category.marker.icon.tooltipAnchor))  ? category.marker.icon.tooltipAnchor.map(Number) : objMarkersConfig.default.options.tooltipAnchor,
					});
					// console.log(objMarkersConfig[category.alias]);
		    	}
		    }
		}


		// console.log(objMapConfig);
		// console.log(objMarkersConfig);
		// console.log(mapDefaultConfig);
	    map = L.map('map',{
			zoomControl : objMapConfig.map.zoomControl,
			gestureHandling: objMapConfig.map.gestureHandling,
	    }).setView([48.833, 2.333], objMapConfig.map.zoom); // LIGNE 18
		map.attributionControl.setPosition('bottomleft');
		if (objMapConfig.map.zoomControl)
			map.zoomControl.setPosition(mapDefaultConfig.zoomControlPosition);

	    var layer = L.tileLayer(objMapConfig.tileLayer.url,
	    	{
				attribution: objMapConfig.tileLayer.attribution,
				subdomains: 'abc',
				minZoom: (objMapConfig.tileLayer.minZoom?objMapConfig.tileLayer.minZoom:3),
				maxZoom: (objMapConfig.tileLayer.maxZoom?objMapConfig.tileLayer.maxZoom:13),
				ext: 'png',
				noWrap: true,
			}
		);
		map.addLayer(layer);

	    var southWest = objMapConfig.map.southWestBound ? L.latLng(parseFloat(objMapConfig.map.southWestBound.split(',')[0]), parseFloat(objMapConfig.map.southWestBound.split(',')[1])) : L.latLng(-65, -180);
	    var northEast = objMapConfig.map.northEastBound ? L.latLng(parseFloat(objMapConfig.map.northEastBound.split(',')[0]), parseFloat(objMapConfig.map.northEastBound.split(',')[1])) : L.latLng(88, 180);
		var bounds = L.latLngBounds(southWest, northEast);
		map.setMaxBounds(bounds);
		map.on('drag', function() {map.panInsideBounds(bounds, { animate: false }); });
		map.on('click', function() {selectMapItem(false) });
		map.on('dblclick', function() {
			selectMapItem(false); 
			$legend.removeClass('active'); 
			$list.removeClass('active'); 
			$filters.removeClass('active'); 
		});

		// MARKERS
		for(var location of objMapData){
			// setup marker parameters
			if('' != location.lat && '' != location.lng){
				var latLng = L.latLng({lat: parseFloat(location.lat), lng: parseFloat(location.lng)});
			}else{
				var latLng = L.latLng({lat: 0, lng: 0});
			}
			var options = {};
			if(location.category && location.category.title){
				if (objMarkersConfig.hasOwnProperty(normalize(location.category.title)))
					options.icon = objMarkersConfig[normalize(location.category.title)];
				else
					options.icon = objMarkersConfig.default;
			} else {
				options.icon = objMarkersConfig.default;
			}

			// construct marker
			var marker = new L.marker(latLng, options);
			var markerInList  = {id:location.id};
			marker.locationID = location.id
			marker.bindTooltip(location.title);
			marker.bindPopup(getPopupHTML(location));
			marker.on('click',function(){
				selectMapItem(this.locationID);
			});
			
			// setup for filters
			for(var f in objMapFilters) {
				marker['filter_'+f] = '';
				markerInList['filter_'+f] = '';
				markerInList.filter_search = $('.map__list__item[data-id='+location.id+']').text()+' '+$(getPopupHTML(location)).text();
				marker.filter_search       = $('.map__list__item[data-id='+location.id+']').text()+' '+$(getPopupHTML(location)).text();
				// console.log(location);
				if (location.hasOwnProperty(f)) {
					switch(f){
						case 'category': 
							if (Array.isArray(location[f])) {
								for (var category of location[f]){
									// console.log(normalize(category.title));
									// marker['filter_'+f] += normalize(category.title);
									// markerInList['filter_'+f] += normalize(category.title);
								}
								marker['filter_'+f]       = location[f].map(function(category){return normalize(category.title); }).join(',');
								markerInList['filter_'+f] = location[f].map(function(category){return normalize(category.title); }).join(',');
							} else if(location[f].id){
								marker['filter_'+f] = normalize(location[f].id);
								markerInList['filter_'+f] = normalize(location[f].id);
							}
						break;
						case 'country':
							marker['filter_'+f] = normalize(location[f].code);
							markerInList['filter_'+f] = normalize(location[f].code);
						break;
						default: 
							if (typeof location[f] === 'string'){
								marker['filter_'+f] = normalize(location[f]);
								markerInList['filter_'+f] = normalize(location[f]);
							}
						break;
					}
				}
			}	

			// register marker
			objMarkersBounds.extend(latLng); 
			arrMarkersAll.push(marker); 
			arrMarkersInListAll.push(markerInList); 
		};

		arrMarkersCurrent = arrMarkersAll.slice();
		arrMarkersInListCurrent = arrMarkersInListAll.slice();
		markersCluster.addLayers(arrMarkersCurrent);
		map.addLayer(markersCluster);


		// make sure we have some pertinent boundaries to rely on
		if (!objMarkersBounds.hasOwnProperty('_southWest') || !objMarkersBounds.hasOwnProperty('_northEast')) 
			objMarkersBounds = bounds;
		
		if (objMapConfig.map.fitBounds)
			map.fitBounds(objMarkersBounds);
		else{
			if (objMapConfig.map.center)
				map.setView(L.latLng({lat: parseFloat(objMapConfig.map.center.split(',')[0]), lng: parseFloat(objMapConfig.map.center.split(',')[1])}), objMapConfig.map.zoom);
			else
				map.setView(objMarkersBounds.getCenter(), objMapConfig.map.zoom);
		}
		resolve()
	});
}

// override selectMapItem to add some spice on the map as we browse throught the list
selectMapItem = function(itemID){
	$list.removeClass('has-selected');
	$map.find('.leaflet-marker-pane').removeClass('has-selected');
	$list.find('.map__list__item').removeClass('selected');
	$map.find('.leaflet-marker-pane .leaflet-marker-icon').removeClass('selected');
	if (itemID) {
		$list.addClass('has-selected');
		$list.find('.map__list__item[data-id="'+itemID+'"]').addClass('selected').get(0).scrollIntoView({behavior: "smooth", block: 'center', inline: "nearest"});
		var marker = arrMarkersAll.filter(function(marker){
		    return marker.locationID == itemID
		});
		if (marker[0]._icon) {
			$map.find('.leaflet-marker-pane').addClass('has-selected');
			$(marker[0]._icon).addClass('selected')
		} else{
		 	markersCluster.zoomToShowLayer(marker[0],()=>{selectMapItem(itemID)})
		}
	}
}
markersCluster.on('clusterclick', function (a) {
	selectMapItem(false);
});

applyFilters_callback = function(){
	markersCluster.removeLayers(arrMarkersAll);
	markersCluster.addLayers(arrMarkersCurrent);
	if (objMapConfig.map.fitBounds)
		map.fitBounds(markersCluster.getBounds());
}