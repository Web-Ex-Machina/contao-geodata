var geodata               = geodata || {};
geodata.map               = {};
geodata.locations         = {};
geodata.config            = {};
geodata.filters           = {};
geodata.markers           = {
	all: [],
	current: [],
	config:{},
	categories:{}
};
geodata.markersInList     = {
	all: [],
	current: [],
};
geodata.blnLoadInAjax     = false;
geodata.loaded = {
	map     : false,
	markers : false,
	list    : false,
	filters : false,
}
geodata.nbItems           = 0;
geodata.nbItemsPerRequest = 100;
geodata.functions         = {};
geodata.callbacks         = {};
geodata.ajax              = {};
geodata.utils             = {};


const delay_check = 100;
var mapModuleID;
var rt;

window.addEventListener("load", function(e) {
    console.log(geodata);
    geodata.functions.init().then(r=>{
    	if ($list) $list.addClass('active');
    	if ($filters) $filters.addClass('active');
    });
});

geodata.functions.init = function(){
	return new Promise(function(resolve,reject){
	    $map            = $('.map__container');
	    $legend         = $('.map__legend');
	    $toggleLegend   = $('.map__legend__toggler');
	    $list           = $('.map__list');
	    $listToggler    = $('.map__list__toggler');
	    $filters	    = null;
	    $filtersToggler	= null;
	    

	    // MAP
	    geodata.functions.initMap().then((r)=>{
	    	console.log(r);
	    	geodata.loaded.map = true;
	    })

	    // LIST
	    let loop_list = setInterval(function(){
	    	// console.log('checkin list loading');
	    	if (geodata.loaded.list === true) {
				clearInterval(loop_list);
		    	$list           = $('.map__list');
		    	$listToggler    = $('.map__list__toggler');
			    if ($list.length) {
			    	$listToggler.bind('click', function(){
			    		$list.toggleClass('active');
			    		if ($legend.length) 
			    	if ($listToggler.length) {
				    	$listToggler.bind('click', function(){
				    		$list.toggleClass('active');
				    		if ($legend.length) 
				    			$legend.removeClass('active');
				    	});
			    	}
			    	$list.find('.map__list__item').on('click', function(e) {
			    		geodata.functions.selectMapItem($(this).data('id'));
			    	});
			    }
				console.log('list init done');
			}
	    },delay_check);

	    // MARKERS
	    let loop_markers = setInterval(function(){
	    	// console.log('checkin markers loaded');
	    	if (geodata.loaded.markers === true) {
	    		clearInterval(loop_markers);

	    		console.log('markers init done');
	    	} else if (geodata.loaded.map && geodata.loaded.filters && geodata.loaded.markers === false && geodata.loaded.list === false){
		    	if (geodata.blnLoadInAjax) {
		    		geodata.loaded.list = 'pending';
		    		geodata.loaded.markers = 'pending';
		    		geodata.functions.getLocations().then((r)=>{
		    			console.log(r);
		    			geodata.loaded.list = true;
		    			geodata.loaded.markers = true;
		    		}).catch((r)=>{
		    			console.log(r);
		    			geodata.loaded.list = 'failed';
		    			geodata.loaded.markers = 'failed';
		    		});
		    	} else {
		    		geodata.loaded.list	= true;
		    		geodata.loaded.markers = 'pending';
		    		geodata.functions.addMarkers(geodata.locations,true,true).then(r=>{
			    		geodata.loaded.markers	= true;
		    		});
		    	}
	    	}
	    },delay_check);

	    // FILTERS
	    let loop_filters = setInterval(function(){
	    	// console.log('checkin filters loading');
	    	if (geodata.loaded.filters === true) {
				clearInterval(loop_filters);
    			$filters        = $('.map__filters');
    			$filtersToggler = $('.map__filters__toggler');
			    if ($filters.length) {
			    	if (typeof FontAwesome == 'undefined')
			    		$filtersToggler.html('<svg fill="currentColor" viewBox="0 0 80 90" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>');

			    	$filtersToggler.bind('click', function(){
			    		$filters.toggleClass('active');
			    	});

	    			geodata.filters = $filters.find('.map__filter [id^=filter_]').map((i,e)=>{return e.name}).toArray().reduce((a,v)=>({...a,[v]:''}),{})
	    			if (geodata.filters.country === undefined) 
			    		geodata.filters.country = '';
	    			if (geodata.filters.city === undefined) 
			    		geodata.filters.city = '';
			    	
			    	$('body').on('change keyup', '.map__filters [id^=filter_]', function(e) {
			    		geodata.functions.applyFilters();
			    	});
			    }
	    		console.log('filters init done');
	    	} else if(geodata.loaded.map && geodata.loaded.filters === false){
	    		geodata.loaded.filters = 'pending';
	    		geodata.functions.getFilters().then((r)=>{
	    			console.log('success');
	    			console.log(r);
	    			geodata.loaded.filters = true;
	    		}).catch((r)=>{
	    			console.log(r);
	    			geodata.loaded.filters = 'failed';
	    		});
	    	} else if (geodata.loaded.filters == 'failed'){
				clearInterval(loop_filters);
	    	}
	    },delay_check);

	    // MODULE
	    let loop_module = setInterval(function(){
	    	// console.log('checkin module loaded');
	    	if (Object.values(geodata.loaded).every((e)=>{return e != false && e != 'pending'})) {
	    		clearInterval(loop_module);
	    		if ($filters) 
	    			geodata.functions.applyFilters()
	    		else
	    			geodata.functions.fitBounds()
	    		console.log('module init done');
    			resolve()
	    	}
	    },delay_check); 
	});
};
geodata.functions.initMap = function(){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (initMap) in map\'s provider own js file.');
		resolve();
	});
};
geodata.functions.addMarkers = function(locations=[],doUpdateLayers=false,doFitBounds=false){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (addMarkers) in map\'s provider own js file.');
		resolve();
	});
};
geodata.functions.addMarker = function(){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (addMarker) in map\'s provider own js file.');
		resolve();
	});
};
geodata.functions.fitBounds = function(){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (fitBounds) in map\'s provider own js file.');
		resolve();
	});
};
geodata.functions.updateLayers = function(){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (updateLayers) in map\'s provider own js file.');
		resolve();
	});
};
geodata.functions.getFilters = function(){
	return new Promise(function(resolve,reject){
	    console.log('functions.getFilters start');
	    geodata.ajax.getFilters().then((r)=>{
	    	if(r.html.length > 0){
	    		$('.map__filters__container').html(r.html);
	    	}
	    	resolve('functions.getFilters done');
	    }).catch(err=>{
	    	reject(err)
	    });
	});
}
geodata.functions.getLocations = function(){
	return new Promise(function(resolve,reject){
	    console.log('functions.getLocations start');
	    let arrPromises = [];
	    let offset = 0;
	    while(offset < geodata.nbItems){
		    arrPromises.push(geodata.ajax.getLocations(offset,geodata.nbItemsPerRequest).then((r)=>{
		    	// console.log(r);
		    	// append list item
				if(r.html.length > 0){
					for(var item of r.html)
						$('.map__list__wrapper').append(item);
				}
				// append markers to map
				geodata.locations = geodata.locations.concat(JSON.parse(r.json));
				geodata.functions.addMarkers(JSON.parse(r.json),true,false);
				return true;
		    }).catch(err=>{
		    	reject(err)
		    }));
		    offset = offset+geodata.nbItemsPerRequest;
	    }


	    Promise.all(arrPromises).then((r)=>{
	    	resolve('functions.getLocations done');
	    })
	});
};
geodata.functions.getPopupHTML = function(obj){
	return `
		<div class="map__popup ">
			<div class="map__popup__title map__list__item__title"> ${obj.title} </div>
        	${obj.category.title && geodata.markers.categories.length>1 ? '<p class="opa-4 ft-l m-top-0">'+obj.category.title.toUpperCase()+'</p>':''}
        	${Array.isArray(obj.category) && geodata.markers.categories.length>1 ? '<p class="opa-4 ft-l m-top-0">'+(obj.category.map(function(c){return c.title})).join(', ').toUpperCase()+'</p>':''}
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
};
geodata.functions.selectMapItem = function(itemID){
	$list.removeClass('has-selected');
	$list.find('.map__list__item').removeClass('selected');
	if (itemID) {
		$list.addClass('has-selected');
		$list.find('.map__list__item[data-id="'+itemID+'"]').addClass('selected').get(0).scrollIntoView({behavior: "smooth", block: 'center', inline: "nearest"});
	}
};
geodata.functions.applyFilters = function(){
	console.log('geodata.functions.applyFilters');
	$filters.find('[id^=filter_]').each(function(){
		geodata.filters[this.name] = this.value;
	});
	// console.log(geodata.filters);
	geodata.markers.current = geodata.markers.all.filter( item => {
		var match = true;
		// console.log(item);
		for(var f in geodata.filters){
			if (f == "search" || f == "category") {
				if (geodata.filters[f] !== '' && item['filter_'+f].search(new RegExp(geodata.filters[f],'i')) == -1)
					match = false;
			} else { // input search code
				if (geodata.filters[f] !== '' && item['filter_'+f] !== geodata.filters[f]){
					if("undefined" !== typeof item['filter_'+f] && -1 != item['filter_'+f].indexOf(',')){
						var values = item['filter_'+f].split(',');
						match = values.includes(geodata.filters[f]);
					}else{
						match = false;
					}
				}
			}
		}
		return match;
	});
	// console.log("==========");
	// console.log(geodata.markersInList.all);
	geodata.markersInList.current = geodata.markersInList.all.filter( item => {
		var match = true;
		for(var f in geodata.filters){
			if (f == "search" || f == "category") {
				if (geodata.filters[f] !== '' && item['filter_'+f].search(new RegExp(geodata.filters[f],'i')) == -1){
					match = false;
					return false;
				}
			} else { // input search code
				if (geodata.filters[f] !== '' && item['filter_'+f] !== geodata.filters[f]){
					if("undefined" !== typeof item['filter_'+f] && -1 != item['filter_'+f].indexOf(',')){
						var values = item['filter_'+f].split(',');
						match = values.includes(geodata.filters[f]);
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
	// console.log(geodata.markersInList.current);

	geodata.markersInList.all.forEach(item=>{
		var item1 = $('.location[data-id="'+item.id+'"]');
		var item2 = $('.map__list__item[data-id="'+item.id+'"]');
		if(-1 === geodata.markersInList.current.indexOf(item)){
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
	
	geodata.callbacks.applyFilters().then((r)=>{
		// geodata.loaded.filters = false;
		// geodata.functions.check_filters();
		geodata.ajax.getFilters()
	});
};

geodata.callbacks.applyFilters = function(){
	return new Promise(function(resolve,reject){
		console.log('Please override this function (applyFilters) in map\'s provider own js file.');
		resolve()
	});
}

geodata.ajax.getFilters = function(){
	console.log('ajax.getFilters start');
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
			console.log('ajax.getFilters done',json);
			if (json) 
		  		resolve(json);
		  	else
		  		reject(new Error('failed to retrieve filters'))
		})
		.catch((error) => {
		    reject(error);
		});
	});
};
geodata.ajax.getLocations = function(offset = 0, limit = 0){
	console.log('ajax.getLocations start');
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
			console.log('ajax.getLocations done',json);
	  		if (json) 
	  	  		resolve(json);
	  	  	else
	  	  		reject(new Error('failed to retrieve locations'))
    	})
    	.catch((error) => {
    	    reject(error);
    	});
	});
};

geodata.utils.normalize = function(str = ''){return str.toLowerCase().replace(/ |\.|\'/g,'_'); }