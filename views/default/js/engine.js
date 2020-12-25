$(document).ready(function(){

	function select_account_type($this){
		if($this.val()=='Employer'){
			$('.account_employer').show().find('input').prop("disabled", false);
		}else{
			$('.account_employer').hide().find('input').prop("disabled", true);
		}
	}
	$('.select_account_type').change(function(){
		select_account_type($(this));
	})
	$('.select_account_type').each(function(){select_account_type($(this))});

	$('#button_clasifieds_show_map').click(function(){
		$('#google_maps').show();
		displayMapClassifieds();
		$(this).remove();
	})
	
	$('#button_search_show_mobile').click(function(){
		$('#search_box').find('.d-none').removeClass('d-none');
		$(this).parents('.d-block').remove();
	})

	$('.lazy').Lazy();

	$('[data-toggle="collapse"]').click(function(){
		$(this).blur();
	})

	$('.select_state').change(function(){
		let $this = $(this);
		$('.substates').hide().find('select').prop("disabled", true);
		$('.substate_'+$this.val()).show().find('select').prop("disabled", false);
	})

	$('.form-search').submit(function(){
		let $form = $(this), $address = $form.find(':input[name="address"]'), flag = true;
		if(!$address.length || $address.val()==''){
			$form.find(':input[name="distance"]').prop( "disabled",true);
		}
		$form.find(':input:enabled').not(':input[type=submit], [name=search]').each(function(){
			$this = $(this);
			if($this.val()=='' && $this.prop("defaultValue")==''){
				$this.prop( "disabled",true);
			}else{
				flag = false;
			}
		})
		if(flag){
			$form.find(':input[name="search"]').prop( "disabled",true);
		}
	})

	let $back_to_top = $('#back_to_top');
	function scroll() {
		if($(window).scrollTop() > 150){
			$back_to_top.removeClass('back_to_top_hidden');
		}else{
			$back_to_top.addClass('back_to_top_hidden');
		}
	}
	scroll();
	document.onscroll = scroll;

	function resize(){
		if($(window).width()<992){
			if($('#show_form_search_classifieds').hasClass('collapsed')){
				$("#form_search_classifieds").removeClass("show");
			}
		}else{
			$("#form_search_classifieds").addClass("show");
		}
	}
	resize();
	window.onresize = resize;

	$('.return_false a').click(function(){
		$(this).blur();
		return false;
	})

	$('#back_to_top').on("click", function(){
		$('html, body').stop().animate({scrollTop: 0}, 300);
		$(this).blur();
		return false;
	})

	$('.show_hidden_data').on("click", function(){
		let $this = $(this);
		if($this.hasClass('show_hidden_data_active')){
			let type = $this.data('type'), data = href = '';
			if(type=='phone'){
				data = $this.data('data')
				href = 'tel:'+data;
			}else if(type=='email'){
				data = $this.data('data_0')+'@'+$this.data('data_1');
				href = 'mailto:'+data;
			}else{
				href = data = $this.data('data');
			}
			$this.attr("href", href).removeClass('show_hidden_data_active').blur().find('.show_hidden_data_target').text(data);
			return false;
		}
	})

	$("#facebook_side").hover(function(){
			$(this).stop(true,false).animate({right: "0px"}, 500 );},
		function(){
			$(this).stop(true,false).animate({right: "-300px"}, 500 );
		}
	);

	$('.reset_form').click(function(){
		let $form = $(this).parents('form');
		$form.find('input').each(function(){
			let $this = $(this);
			switch ($this.attr('type')) {
				case 'text':
				case 'number':
					$this.val('');
					break;
				case 'radio':
				case 'checkbox':
					$this.prop('checked',false);
			}
		});
		$form.find('select').each(function(){
			$(this).prop("selectedIndex", 0);
		})
		$(this).blur();
		return false;
	})

	$('.index_show_subcategories').click(function(){
		let $this = $(this), active = $this.hasClass('active'), eq = 0, $subcategories = $('#index_subcategory_'+$this.data('id'));
		$('.index_subcategories').hide();
		$('.index_show_subcategories').removeClass('active');
		if(!active){
			index = $this.data('index');
			eq = index;
			window_width = $(window).width();
			if(window_width<540){
				if(index%2==0){
					eq = index-1;
				}
			}else if(window_width<992 && window_width>=768){
				mod = index%3;
				if(mod==0){
					eq = index-1;
				}else if(mod==1){
					eq = index+1;
				}
			}else{
				mod = index%4;
				if(mod==0){
					eq = index-1;
				}else if(mod==1){
					eq = index+2;
				}else if(mod==2){
					eq = index+1;
				}
			}
			$subcategories.insertAfter('.index_categories:eq('+eq+')');
			$subcategories.show();
			$this.addClass('active');
		}
		$this.blur();
		return false;
	})

	if(!localStorage.rodo_accepted) {
		$("#rodo-message").modal('show');
	}
});

function closeRodoWindow(){
	localStorage.rodo_accepted = true;
	$("#rodo-message").modal('hide');
}

if (window.location.href.indexOf('#_=_') > 0) {
	window.location = window.location.href.replace(/#.*/, '');
}

$(function(){
	let options = {
		url: function(phrase) {
			return links.classifieds + "?action=classifieds_sugested_keywords&keywords=" + encodeURI(phrase);
		},
	  list: {
		match: {
		  enabled: true
		}
	  },
	  theme: "square"
	};
	$("#search_keywords").easyAutocomplete(options);
})

$(window).on("load", function (){
	let $js_scroll_page = $('#js_scroll_page')
  	if($js_scroll_page.length>0){
		position = $js_scroll_page.offset().top;
		if($(window).scrollTop()+$(window).height()<position){
			$('html, body').stop().animate({scrollTop: (position-110)}, 300);
		}
	}
});

function initGoogleMap() {
	if(typeof displayMap == 'function') {
		displayMap();
	}else{
		input = document.getElementById('search_main_address');
		if(input){
			new google.maps.places.Autocomplete(input, {types: ['geocode']});
		}
	}
}

function checkCookies(){
	if(!localStorage.cookies_accepted) {
		cookies_message = document.getElementById("cookies-message").style.display="block"
	}
}

function closeCookiesWindow(){
	localStorage.cookies_accepted = true;
	var cookie_window = document.getElementById("cookies-message");
  cookie_window.parentNode.removeChild(cookie_window);
}

window.onload = checkCookies;
