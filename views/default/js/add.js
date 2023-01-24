$(function(){

	CKEDITOR.replace("description",{height: "300px"});

	$("#preview_photos").sortable({
		containerSelector : "#preview_photos",
		itemSelector : ".img-thumbnail",
		handle: "img",
		placeholder	: "<div class='placeholder'></div>"
	});

});

new Vue({
	el: '#add_classified',
	data: {
	  list_categories: [],
	  list_options: [],
	  inputName: document.getElementById("input_name").value,
	  inputSalary: document.getElementById("input_salary").value,
	  waiting_for_load_categories: false,
	  photos: list_photos,
	  photos_is_loading: false,
	  photos_progress: 0,
	  photos_alert: '',
	  button_load_photos: '',
	  photos_length: 0,
	  inputAddressLat: document.getElementById("input_address_lat").value,
	  inputAddressLong: document.getElementById("input_address_long").value,
	},
	methods: {
		loadCategories: function (category_id, next_category = [], load_options = true) {
			const vm = this;
			vm.waiting_for_load_categories = true;
			const params = new URLSearchParams();
			params.append('action', 'get_categories_and_options' );
			params.append('category_id', category_id );
			params.append('load_options', load_options );
			axios.post(
				location.href, params
			)
			.then((response) => {
				const last_list_options = vm.list_options;
				vm.list_options = [];
				if(response.data.options !== undefined && response.data.options.length){
					let options = [];
					response.data.options.forEach(function (option) {
						option.value = "";
						last_list_options.forEach(function (option_last) {
							if(option_last.id == option.id){
								option.value = option_last.value;
							}
						})
						if(!option.value && list_options[option.id] != undefined){
							if(option.kind=="number"){
								option.value = parseInt(list_options[option.id][0]);
							}else if(option.kind=="checkbox"){
								option.value = list_options[option.id];
							}else{
								option.value = list_options[option.id][0];
							}	
						}
						options.push(option);
					});
					vm.list_options = options;
				}
				if(response.data.categories !== undefined && response.data.categories.length){
					const d = {
						'categories':response.data.categories, 
						'parent_id': category_id, 
						'selected_id':category_id, 
						'required': (category_id) ? required_subcategory : required_category
					};
					if(vm.list_categories){
						vm.list_categories.push(d);
					}else{
						vm.list_categories = d;
					}
					if(next_category.length){
						const next_c = next_category.shift();
						vm.list_categories[vm.list_categories.length-1].selected_id = next_c;
						const load_options = (next_category.length) ? 0 : 1;
						vm.loadCategories(next_c, next_category, load_options);
					}
				}
				vm.waiting_for_load_categories = false;
			})
			.catch( () =>   {
				vm.waiting_for_load_categories = false;
			} ) ;
		},
		selectCategories: function (parent_index) {
			const category_id = this.list_categories[parent_index].selected_id;
			if(category_id != this.list_categories[parent_index].parent_id){
				this.list_categories.splice(parent_index+1)
				this.loadCategories(category_id, parent_index)
			}else if(category_id==0){
				this.list_categories = [];
				this.loadCategories(0)
			}else{
				this.list_categories.splice(parent_index)
				this.loadCategories(category_id)
			}
		},
		loadPhotos: function (event) {
			const vm = this;
			const files = event.target.files;
			const files_length = files.length;
			if(files_length){
				vm.photos_progress = 0;
				vm.photos_length = vm.photos.length;
				let photo_index = 0;
				Array.from(files).forEach(file => {
					vm.photos_length++;
					if(vm.photos_length <= photo_max){
						vm.photos_is_loading = true;
						const params = new FormData();
						params.append('action', 'add_photo' );
						params.append('file', file);
						axios.post(
							location.href, params, {
								headers: {
									'Content-Type': 'multipart/form-data'
								}
							}
						)
						.then((response) => {
							photo_index++;
							vm.photos_progress += Math.round(100 / files_length);
							if(response.data.status){
								if(vm.photos){
									vm.photos.push(response.data);
								}else{
									vm.photos = response.data;
								}
							}else{
								vm.photos_alert = response.data.info;
							}
							if(photo_index == files_length || vm.photos_length >= photo_max){
								vm.photos_is_loading = false;
							}
						})
					}else{
						vm.photos_alert = trans["Photo limit exceeded"];
					}
				})
			}
			vm.button_load_photos = '';
		},
		removePhoto: function (index) {
			this.photos.splice(index, 1);
			this.photos_alert = '';
		},
		getCoordinates: function () {
			const vm = this;
			let address = document.getElementById('add_address').value;
			if(!address){
				const input_state_id = document.querySelector("#state_id");
				if(input_state_id && input_state_id.selectedIndex){
					address += " " + input_state_id.options[input_state_id.selectedIndex].text;
					const input_state2_id = document.querySelector(".substate_"+input_state_id.value+" select.form-control");
					if(input_state2_id && input_state2_id.selectedIndex){
						address += " " + input_state2_id.options[input_state2_id.selectedIndex].text;
					}
				}
			}
			if(address){
				const params = new FormData();
				params.append('action', 'get_coordinates' );
				params.append('address', address);
				axios.post(
					location.href, params
				)
				.then((response) => {
					if(response.data.lat && response.data.long){
						const latlng = new google.maps.LatLng(response.data.lat, response.data.long);
						google_maps_marker.setPosition(latlng);
						google_maps.setCenter(latlng);
						vm.inputAddressLat = response.data.lat;
						vm.inputAddressLong = response.data.long;
					}
				})
			}
		}		
	},
	beforeMount(){
		const load_options = (list_categories.length) ? 0 : 1;
		this.loadCategories(0, list_categories, load_options);
	}
})