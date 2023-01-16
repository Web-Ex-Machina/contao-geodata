// Make a custom leaflet marker to add important property
var LocationMarker = L.Marker.extend({
	options: {
		locationID : 1
	},
});

function initMap() {
	objMapData.forEach(function(location,index){
		objMarkers[location.id].latLng = L.latLng({lat: parseFloat(location.lat), lng: parseFloat(location.lng)});
	});

	objMap = L.map($map[0]);
	objMapBounds = L.latLngBounds();
	var options = {};

	for(var i in objMarkers){
		objMapBounds.extend(objMarkers[i].latLng);
		options = {};
		options.title = objMarkers[i].title;
		options.locationID = objMarkers[i].id;

		console.log(objMarkers[i]);

		if(objMarkers[i].category.marker && objMarkers[i].category.marker.icon)
			options.icon = L.icon(objMarkers[i].category.marker.icon);

		objMarkers[i].marker = new LocationMarker(objMarkers[i].latLng, options).addTo(objMap);

		if(0 < $('.map__list').length){
			objMarkers[i].marker.on('click', function(e) {
				selectMapItem(this.options.locationID);
			});
		}
	}

	objMap.setView(objMapBounds.getCenter(), objMapConfig.map.zoom);
	L.tileLayer(objMapConfig.tileLayer.url, objMapConfig.tileLayer).addTo(objMap);

	objMap.fitBounds(objMapBounds);
	objMap.zoomControl.setPosition('bottomleft');


	$toggleList.bind('click', function(){
		objMap.invalidateSize();
	});
}