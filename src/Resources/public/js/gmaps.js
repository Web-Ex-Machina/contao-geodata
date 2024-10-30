// GMAPS
var objMarkersBounds  = new google.maps.LatLngBounds();
var mapDefaultConfig  = {
	zoom                 : 3,
	minZoom              : 3,
	maxZoom              : 13,
	zoomControl          : true,
	gestureHandling  	 : true,
	fitBounds  	 		 : true,
	disableDefaultUI	 : true,
	gestureHandling	     : true,
	scrollwheel	         : true,
	zoomControl	         : false,
	mapId                : "DEMO_MAP_ID",
	marker               :  
	{
		iconUrl          : 'bundles/wemgeodata/img/Icon_default.png',
		iconSize         : [60,60],
	}
};
initMap =  function() {
	return new Promise(async function(resolve,reject){
		if (objMapConfig.map                	 === undefined ) objMapConfig.map               	  = {};
		if (objMapConfig.map.fitBounds           === undefined ) objMapConfig.map.fitBounds           = mapDefaultConfig.fitBounds;
		if (objMapConfig.map.zoomControl         === undefined ) objMapConfig.map.zoomControl         = mapDefaultConfig.zoomControl;
		if (objMapConfig.map.zoom                === undefined ) objMapConfig.map.zoom                = mapDefaultConfig.zoom;
		if (objMapConfig.map.minZoom       		 === undefined ) objMapConfig.map.minZoom        	  = mapDefaultConfig.minZoom;
		if (objMapConfig.map.maxZoom       		 === undefined ) objMapConfig.map.maxZoom        	  = mapDefaultConfig.maxZoom;
		if (objMapConfig.map.mapId       		 === undefined ) objMapConfig.map.mapId        		  = mapDefaultConfig.mapId;
		if (objMapConfig.map.gestureHandling     === undefined ) objMapConfig.map.gestureHandling     = mapDefaultConfig.gestureHandling;
		if (objMapConfig.map.disableDefaultUI	 === undefined ) objMapConfig.map.disableDefaultUI	  = mapDefaultConfig.disableDefaultUI;
		if (objMapConfig.map.scrollwheel	     === undefined ) objMapConfig.map.scrollwheel	  	  = mapDefaultConfig.scrollwheel;
		if (objMapConfig.map.zoomControl	     === undefined ) objMapConfig.map.zoomControl	  	  = mapDefaultConfig.zoomControl;

		var refSize = objMapConfig.icon?.iconSize?.split(',').map(Number) || mapDefaultConfig.marker.iconSize;
		objMarkersConfig = {
			'default': {
				options: {
					iconUrl: 	   	objMapConfig.icon?.iconUrl 								|| mapDefaultConfig.marker.iconUrl,
				    iconSize:      	refSize, // taille de l'icone
				    iconAnchor:    	objMapConfig.icon?.iconAnchor?.split(',').map(Number) 	|| [refSize[0]/2,refSize[1]], // point de l'icone qui correspondra à la position du marker
				    popupAnchor:   	objMapConfig.icon?.popupAnchor?.split(',').map(Number) 	|| [0,refSize[1]*-1], // point depuis lequel la popup doit s'ouvrir relativement à l'iconAnchor
				    tooltipAnchor: 	objMapConfig.icon?.popupAnchor?.split(',').map(Number) 	|| [refSize[0]/3,refSize[1]*-0.5],
				}
			}
		}
		if (categories) {
		    for(var c in categories) {
		    	var category = categories[c];
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
		    		objMarkersConfig[category.alias] = {
		    			options:{
							iconUrl: 		 (category.marker.icon.iconUrl       !== undefined)                                                       ? category.marker.icon.iconUrl                   : objMarkersConfig.default.options.iconUrl,
						    iconSize:     	 (category.marker.icon.iconSize      !== undefined && Array.isArray(category.marker.icon.iconSize))	      ? category.marker.icon.iconSize.map(Number)  	   : objMarkersConfig.default.options.iconSize,
						    iconAnchor:   	 (category.marker.icon.iconAnchor    !== undefined && Array.isArray(category.marker.icon.iconAnchor))     ? category.marker.icon.iconAnchor.map(Number)    : objMarkersConfig.default.options.iconAnchor,
						    popupAnchor:  	 (category.marker.icon.popupAnchor   !== undefined && Array.isArray(category.marker.icon.popupAnchor))	  ? category.marker.icon.popupAnchor.map(Number)   : objMarkersConfig.default.options.popupAnchor,
						    tooltipAnchor: 	 (category.marker.icon.tooltipAnchor !== undefined && Array.isArray(category.marker.icon.tooltipAnchor))  ? category.marker.icon.tooltipAnchor.map(Number) : objMarkersConfig.default.options.tooltipAnchor,
		    			}
					};
					// console.log(objMarkersConfig[category.alias]);
		    	}
		    }
		}


		const { Map, InfoWindow } = await google.maps.importLibrary("maps");
		const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
		const infoWindow = new InfoWindow();

		map = new Map($map[0], {
			center: { lat: 37.4239163, lng: -122.0947209 },
			zoom: parseInt(objMapConfig.map.zoom),
			minZoom: parseInt(objMapConfig.map.minZoom),
			maxZoom: parseInt(objMapConfig.map.maxZoom),
			mapId: objMapConfig.map.mapId,
			disableDefaultUI: objMapConfig.map.disableDefaultUI,
			gestureHandling: objMapConfig.map.gestureHandling,
			scrollwheel: objMapConfig.map.scrollwheel,
			zoomControl: objMapConfig.map.zoomControl,
		});
		map.addListener('dblclick', function() {
			selectMapItem(false); 
			$legend.removeClass('active'); 
			$list.removeClass('active'); 
			$filters.removeClass('active'); 
		});
		map.addListener('click', function() {
			selectMapItem(false) 
		    infoWindow.close();
		});
		

		// MARKERS
		for(var location of objMapData){
			// setup marker parameters
			let latLng = new google.maps.LatLng({lat: 0, lng: 0});
			if('' != location.lat && '' != location.lng)
				latLng = new google.maps.LatLng({lat: parseFloat(location.lat), lng: parseFloat(location.lng)});

			let icon = {};
			if(Array.isArray(location.category)){
				if (location.category.length > 0 && location.category[0].title && objMarkersConfig.hasOwnProperty(normalize(location.category[0].title)))
					icon = objMarkersConfig[normalize(location.category[0].title)].options;
				else
					icon = objMarkersConfig.default.options;
			} else {
				if (location.category.title && objMarkersConfig.hasOwnProperty(normalize(location.category.title)))
					icon = objMarkersConfig[normalize(location.category.title)].options;
				else
					icon = objMarkersConfig.default.options;
			}

			let options = {
				map,
				position: latLng,
				title: location.title,
			}
			if (icon.iconUrl) {
				let img = document.createElement('img');
				img.src = icon.iconUrl;
				img.width = icon.iconSize[0];
				img.height = icon.iconSize[1];
				options.content = img;
			}
			// construct marker
			let marker = new AdvancedMarkerElement(options);

			// marker.bindPopup(getPopupHTML(location));
			marker.addListener('click',function({ domEvent, latLng }){
				const { target } = domEvent;

			    infoWindow.close();
			    infoWindow.setContent(getPopupHTML(location));
			    infoWindow.open(marker.map, marker);
				selectMapItem(this.locationID);
			});

			// setup for filters
			let markerInList  = {id:location.id};
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
		}

		arrMarkersCurrent = arrMarkersAll.slice();
		arrMarkersInListCurrent = arrMarkersInListAll.slice();

		
		if (objMapConfig.map.fitBounds)
			map.fitBounds(objMarkersBounds);
		else{
			if (objMapConfig.map.center){
				map.panTo(
					new google.maps.latLng({
						lat: parseFloat(objMapConfig.map.center.split(',')[0]), 
						lng: parseFloat(objMapConfig.map.center.split(',')[1])
					})
				)
				map.setZoom(parseInt(objMapConfig.map.zoom));	
			} else {
				map.panTo(objMarkersBounds.getCenter());
				map.setZoom(parseInt(objMapConfig.map.zoom));	
			}
		}

		resolve()
	})
};