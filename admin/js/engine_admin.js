﻿$(document).ready(function(){

	function set_required(object){
		const $target = $('.'+object.data('target'));
		if (object.is(':checked')) {
			$target.prop('required', true);
		}else{
			$target.prop('required', false);
		}
	}

	$('.set_required').click(function(){
		set_required($(this));
	})
	$('.set_required').each(function(){
		set_required($(this));
	})

	$('.select_checkbox').click(function(){
		const $this = $(this);
		if ($this.is(':checked')) {
			$this.parents('.parent_select_checkbox').find('input[type=checkbox]').prop('checked', true);
		}else{
			$this.parents('.parent_select_checkbox').find('input[type=checkbox]').prop('checked', false);
		}
	})

	$('.option_select').change(function(){
		const $this = $(this);
		if($this.val()=='select'){
			$this.parents('form').find('.option_label').show().find('textarea').attr("disabled", false);
			$this.parents('form').find('.option_label_required').show().find('input[type=checkbox]').attr("disabled", false);
		}else if($this.val()=='checkbox'){
			$this.parents('form').find('.option_label').show().find('textarea').attr("disabled", false);
			$this.parents('form').find('.option_label_required').hide().find('input[type=checkbox]').attr("disabled", true);
		}else{
			$this.parents('form').find('.option_label').hide().find('textarea').attr("disabled", true);
			$this.parents('form').find('.option_label_required').show().find('input[type=checkbox]').attr("disabled", false);
		}
	})
	$('.option_select').change();

	$('.option_all_categories').click(function(){
		const $this = $(this);
		if ($this.is(':checked')) {
			$this.parents('form').find('.option_all_categories_div').slideToggle().find('input[type=checkbox]').prop('checked', true);
		}else{
			$this.parents('form').find('.option_all_categories_div').slideToggle().find('input[type=checkbox]').prop('checked', false);
		}
	})

	$('.link_remove_slide').click(function(){
		$('#'+$(this).data('id')).remove();
		return false;
	})

	$('.inactive').click(function(){
		return false;
	})

	$('.select_option').click(function(){
		const $this = $(this);
		const depth = $this.data('depth');
		if($this.next().data('depth') >= depth){
			const $checkbox = $this.find('input[type=checkbox]');
			if($checkbox.is(':checked')) {
				$this.nextUntil(".depth_"+depth).not(".depth_"+(depth-1)).find('input[type=checkbox]').prop('checked', true);
			}else{
				$this.nextUntil(".depth_"+depth).not(".depth_"+(depth-1)).find('input[type=checkbox]').prop('checked', false);
			}
		}
	})

	$('.link_to_hidden_option').click(function(){
		const $this = $(this);
		if($this.hasClass('active')){
			$('.hidden_option_'+$this.data('id')).hide();
			$this.removeClass('active').find('.span_inactive').hide().end().find('.span_active').show();
		}else{
			const $hidden_options = $('.hidden_option_'+$this.data('id'));
			if($hidden_options.length){
				$hidden_options.show();
			}else{
				$this.remove();
			}
			$this.addClass('active').find('.span_active').hide().end().find('.span_inactive').show();
		}
		return false;
	})
})

$(document).on('click', '.open_roxy', function(){
	$('.roxy_target').removeClass('roxy_target');
	$(this).find('img').addClass('roxy_target');
	$('#roxySelectFile').modal('show').find('iframe').attr("src",'js/ckeditor/fileman/index.php?integration=custom');
	return false;
})

$(document).on('hidden.bs.modal', '.modal', function () {
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});

function closeRoxySelectFile(){
	const $roxy_target = $('.roxy_target');
	$("[name='"+$roxy_target.data('roxy_name')+"']").val($roxy_target.attr('src'));
	$('#roxySelectFile').modal('hide');
}

function run_ckeditor(id,height=200){
	const roxyFileman = 'js/ckeditor/fileman/index.php';
	$(function(){
		CKEDITOR.replace( id,{height: height,
			filebrowserBrowseUrl: roxyFileman,
			filebrowserImageBrowseUrl: roxyFileman+'?type=image',
			removeDialogTabs: 'link:upload;image:upload'});
	});
}

$(document).on({'show.bs.modal': function () {
	$(this).removeAttr('tabindex');
} }, '.modal');