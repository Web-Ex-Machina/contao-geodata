var j = jQuery.noConflict();
var ajaxLoaderSrc = 'bundles/wemgeodata/backend/ajax-loader_16_blue.gif';
var objStatusClasses = {'success':'tl_confirm', 'error':'tl_error'};
j(document).ready(function(){
	j('.geocode').bind('click',function(e){
		e.preventDefault();
		var objIcon = j(this);
		var objRow = objIcon.closest('.tl_content').find('.ajax-results').first();
		var originalImg = objIcon.find('img').first().attr('src');
		objIcon.find('img').first().attr('src', ajaxLoaderSrc);
		j.get(objIcon.attr('href')+'&src=ajax', function(data){
			objRow.addClass(objStatusClasses[data.status]).html(data.response).slideDown(200);
		}).always(function(){
			setTimeout(function(){ objRow.slideUp(200, function() { objRow.attr('class', 'ajax-results').html(''); }); }, 5000);
			objIcon.find('img').first().attr('src', originalImg);
		});
	});

	j('.header_geocodeAll').bind('click',function(e){
		e.preventDefault();
		var objIcon = j(this);
		if(confirm(objIcon.data('confirm'))){
			j('.tl_content .geocode').each(function(i){
				var objButton = j(this);
				setTimeout(function(){ objButton.trigger('click'); }, i * 1000);
			});
		}
	});
});