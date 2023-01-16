function initMap() {
	objMapData.forEach(function(location,index){
		objMarkers[location.id].latLng = new google.maps.LatLng(parseFloat(location.lat), parseFloat(location.lng));
	});

	objMap = new google.maps.Map($map[0]);
	objMapBounds = new google.maps.LatLngBounds();
	var options = {};

	for(var i in objMarkers){
		objMapBounds.extend(objMarkers[i].latLng);
		options = {};

		options.position = objMarkers[i].latLng;
		options.map = objMap;
		options.title = objMarkers[i].title;
		options.locationID = objMarkers[i].id;

		var iconSize = new google.maps.Size(objMarkers[i].category.marker.icon.iconSize[0],objMarkers[i].category.marker.icon.iconSize[1]);
		if(objMapConfig.map.iconSize)
			iconSize = new google.maps.Size(objMapConfig.map.iconSize,objMapConfig.map.iconSize);
		if(objMarkers[i].category.marker.iconSize)
			iconSize = new google.maps.Size(objMarkers[i].category.marker.iconSize,objMarkers[i].category.marker.iconSize);

		if(objMarkers[i].category.marker && objMarkers[i].category.marker.icon)
			options.icon = {
				url : objMarkers[i].category.marker.icon.iconUrl,
				scaledSize: iconSize,
			}
		var contentStr = '<h4>'+objMarkers[i].title+'</h4>';
		if(objMarkers[i].street || objMarkers[i].postal || objMarkers[i].city){
			contentStr += '<p>';
			if(objMarkers[i].street) contentStr += objMarkers[i].street+'<br />';
			if(objMarkers[i].postal) contentStr += objMarkers[i].postal+'<br />';
			if(objMarkers[i].city)   contentStr += objMarkers[i].city+'<br />';
			contentStr += '</p>';
		}
		if(objMarkers[i].website || objMarkers[i].email || objMarkers[i].phone){
			contentStr += '<div class="flex-wrap-justifycontent--center">';
			if(objMarkers[i].website) contentStr += '<a class="btn-sm" target="_blank" href="'+objMarkers[i].website+'">'+objMarkers[i].website+'</a>&nbsp;';
			if(objMarkers[i].email) contentStr += '<a class="btn-sm" href="mailto:'+objMarkers[i].email+'">'+objMarkers[i].email+'</a>&nbsp;';
			if(objMarkers[i].phone)   contentStr += '<a class="btn-sm" href="tel:'+objMarkers[i].phone+'">'+objMarkers[i].phone+'</a>&nbsp;';
			contentStr += '</div>';
		}


		objMarkers[i].marker = new google.maps.Marker(options);
		objMarkers[i].infoWindow = new google.maps.InfoWindow({content: contentStr});

		if(0 < $('.map__list').length){
			objMarkers[i].marker.addListener('click', function() {
				selectMapItem(this.locationID);
	    });
		} else {
			objMarkers[i].listener_click = objMarkers[i].marker.addListener('click', function() {
				objMarkers[i].infoWindow.open(objMap,this);
	    });
		}
	};

	objMap.setCenter(objMapBounds.getCenter());
	objMap.fitBounds(objMapBounds);
}
