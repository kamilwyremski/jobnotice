
{% if classifieds %}
	<form method="get" class="float-right">
		{% for key,item in pagination.page_url.sort %}
			{% if item is iterable %}
				{% for key2,item2 in item %}
					{% if item2 is iterable %}
						{% for key3,item3 in item2 %}
							<input type="hidden" name="{{ key }}[{{ key2 }}][{{ key3 }}]" value="{{ item3 }}">
						{% endfor %}
					{% else %}
						<input type="hidden" name="{{ key }}[{{ key2 }}]" value="{{ item2 }}">
					{% endif %}
				{% endfor %}
			{% else %}
				<input type="hidden" name="{{ key }}" value="{{ item }}">
			{% endif %}
		{% endfor %}
		<select name="sort" onchange="this.form.submit()" title="{{ 'Select sort method'|trans }}" class="form-control" style="width: auto">
			<option value="most_accurate" {% if get.sort=="most_accurate" %}selected{% endif %}>{{ 'Most accurate'|trans }}</option>
			<option value="newest" {% if get.sort=="newest" %}selected{% endif %}>{{ 'Newest'|trans }}</option>
			<option value="oldest" {% if get.sort=="oldest" %}selected{% endif %}>{{ 'Oldest'|trans }}</option>
			<option value="cheapest" {% if get.sort=="cheapest" %}selected{% endif %}>{{ 'Cheapest'|trans }}</option>
			<option value="most_expensive" {% if get.sort=="most_expensive" %}selected{% endif %}>{{ 'Most expensive'|trans }}</option>
			<option value="a-z" {% if get.sort=="a-z" %}selected{% endif %}>{{ 'A - Z'|trans }}</option>
			<option value="z-a" {% if get.sort=="z-a" %}selected{% endif %}>{{ 'Z - A'|trans }}</option>
			{% if get.distance>0 %}
				<option value="nearest" {% if get.sort=="nearest" %}selected{% endif %}>{{ 'Nearest'|trans }}</option>
				<option value="farthest" {% if get.sort=="farthest" %}selected{% endif %}>{{ 'Farthest'|trans }}</option>
			{% endif %}
		</select>
	</form>
	<div class="clearfix"></div>
	<br>
	{% for classified in classifieds %}
		<div class="classifieds {% if classified.promoted %}promoted{% endif %}">
			<div class="row">
				<div class="col-md-2 col-sm-3 text-center">
					{% if classified.promoted %}<div class="bar_promoted">{{ 'Promoted'|trans }}</div>{% endif %}
					<a href="{{ path('classified',classified.id,classified.slug) }}" title="{{ classified.name }}">
						<img src="{% if classified.thumb %}upload/photos/{{ classified.thumb }}{% else %}views/{{ settings.template }}/images/no_image.png{% endif %}" alt="{{ classified.name }}" onerror="this.src='views/{{ settings.template }}/images/no_image.png'" loading="lazy">
					</a>
				</div>
				<div class="col-md-7 col-sm-5 overflow_hidden">
					<h4><a href="{{ path('classified',classified.id,classified.slug) }}" title="{{ classified.name }}" class="main-color-2">{{ classified.name }}</a></h4>
					<p class="text-muted">{{ classified.description|striptags|slice(0,150) }}{% if classified.description|striptags %}...{% endif %}</p>
					{% if classified.distance %}<p>{{ 'Distance'|trans }}: {{ classified.distance|number_format(2, '.', ',') }} {{ 'km'|trans }}</p>{% endif %}
					{% if classified.type_name or classified.state_name %}<p class="main-color-1">
						{% if classified.type_name %}<a href="{{ path('classifieds') }}?type={{ classified.type_slug }}" title="{{ 'Classified type'|trans }}: {{ classified.type_name }}" class="main-color-1">{{ classified.type_name }}</a>{% endif %}
						{% if classified.type_name and classified.state_name %} | {% endif %}
						{% if classified.state_name %}<a href="{{ path('classifieds') }}?state={{ classified.state_slug }}" title="{{ 'State'|trans }}: {{ classified.state_name }}" class="main-color-1">{{ classified.state_name }}</a>{% endif %}
						{% if classified.state2_name %} | <a href="{{ path('classifieds') }}?state={{ classified.state_slug }}&state2={{ classified.state2_slug }}" title="{{ classified.state2_name }}" class="main-color-1">{{ classified.state2_name }}</a>{% endif %}
					</p>{% endif %}
				</div>
				<div class="col-md-3 col-sm-4">
					<div class="classifieds_date text-center small"><p>{{ classified.date_start|date("d-m-Y") }}</p></div>
					{% if classified.category_name %}<div class="classifieds_category text-center"><p><a href="{{ path('classifieds') }}?category={{ classified.category_id }}" title="{{ 'Category'|trans }}: {{ classified.category_name }}"><strong>{{ classified.category_name }}</strong></a></p></div>{% endif %}
					{% if classified.salary>0 %}
						<div class="classifieds_salary text-center"><p><strong>{{ classified.salary|showCurrency }}{% if classified.salary_net %} {{ classified.salary_net|trans }}{% endif %}</strong></p>{% if classified.salary_negotiate %}<p class="small">({{ 'to negotiate'|trans }})</p>{% endif %}</div>
					{% endif %}
				</div>
			</div>

			{% if controller=='my_classifieds' %}
				<a href="{{ path('add') }}/?add_similar={{ classified.id }}" title="{{ 'Add similar'|trans }}: {{ classified.name }}" class="btn btn-sm btn-success">{{ 'Add similar'|trans }}</a>

				<a href="{{ path('edit',classified.id,classified.slug) }}" title="{{ 'Edit classified'|trans }}: {{ classified.name }}" class="btn btn-sm btn-info">{{ 'Edit'|trans }}</a>

				<button class="btn btn-sm btn-warning {% if not classified.active %}disabled{% endif %}" data-toggle="modal" data-target="#finish_classified_{{ classified.id }}">{{ 'Finish'|trans }}</button>

				<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#remove_classified_{{ classified.id }}"
				>{{ 'Delete'|trans }}</button>

				{% if settings.allow_refresh_classifieds %}
					<button class="btn btn-sm btn-primary {% if not classified.refresh.active %}disabled{% endif %}"  data-toggle="modal" data-target="#refresh_classified_{{ classified.id }}">{{ 'Refresh classified'|trans }}</button>
					{% if not classified.refresh.active %}
						{% if classified.refresh.not_confirmed %}
							<span class="text-danger text-nowrap">{{ 'Classified is not approved'|trans }}</span>
						{% elseif classified.refresh.must_payed %}
							<span class="text-danger text-nowrap">{{ 'You must pay for classified'|trans }}</span>
						{% else %}
							<span class="text-nowrap">{{ 'Available for'|trans }} {{ classified.refresh.days }} {{ 'days'|trans }}</span>
						{% endif %}
					{% elseif not classified.active %}
						<span class="text-danger text-nowrap">{{ 'Classified is inactive'|trans }}</span>
					{% endif %}
				{% endif %}

				{% if classified.active %}
					<div class="modal fade" id="finish_classified_{{ classified.id }}">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title">{{ 'Finish classified'|trans }}: {{ classified.name }}</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								</div>
								<form method="post" class="form">
									<input type="hidden" name="action" value="finish_classified">
									<input type="hidden" name="id" value="{{ classified.id }}">
									<input type="hidden" name="token" value="{{ generateToken('finish_classified') }}">
									<div class="modal-body">
										<p>{{ 'Do you really want to finish classified'|trans }} {{ classified.name }}?</p>
									</div>
									<div class="modal-footer">
										<input type="button" class="btn btn-secondary" data-dismiss="modal" value="{{ 'Cancel'|trans }}">
										<input type="submit" class="btn btn-warning" value="{{ 'Finish'|trans }}">
									</div>
								</form>
							</div>
						</div>
					</div>
				{% endif %}
				<div class="modal fade" id="remove_classified_{{ classified.id }}">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">{{ 'Delete classified'|trans }}: {{ classified.name }}</h5>
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							</div>
							<form method="post" class="form">
								<input type="hidden" name="action" value="remove_classified">
								<input type="hidden" name="id" value="{{ classified.id }}">
								<input type="hidden" name="token" value="{{ generateToken('remove_classified') }}">
								<div class="modal-body">
									<p>{{ 'Do you really want to delete'|trans }} {{ classified.name }}?</p>
								</div>
								<div class="modal-footer">
									<input type="button" class="btn btn-secondary" data-dismiss="modal" value="{{ 'Cancel'|trans }}">
									<input type="submit" class="btn btn-danger" value="{{ 'Delete'|trans }}">
								</div>
							</form>
						</div>
					</div>
				</div>
				{% if classified.refresh.active %}
					<div class="modal fade" id="refresh_classified_{{ classified.id }}">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title">{{ 'Refresh classified'|trans }}: {{ classified.name }}</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								</div>
								<form method="post" class="form">
									<input type="hidden" name="action" value="refresh_classified">
									<input type="hidden" name="id" value="{{ classified.id }}">
									<input type="hidden" name="token" value="{{ generateToken('refresh_classified') }}">
									<div class="modal-body">
										<p>{{ 'Are you sure to refresh classified'|trans }} {{ classified.name }}?</p>
									</div>
									<div class="modal-footer">
										<input type="button" class="btn btn-secondary" data-dismiss="modal" value="{{ 'Cancel'|trans }}">
										<input type="submit" class="btn btn-primary" value="{{ 'Refresh classified'|trans }}">
									</div>
								</form>
							</div>
						</div>
					</div>
				{% endif %}

			{% elseif controller=='clipboard' %}
				<form method="post">
					<input type="hidden" name="action" value="clipboard_remove">
					<input type="hidden" name="id" value="{{ classified.id }}">
					<input type="hidden" name="token" value="{{ generateToken('clipboard_remove') }}">
					<input type="submit" value="{{ 'Delete from clipboard'|trans }}" class="btn btn-danger btn-sm">
				</form>
			{% endif %}
		</div>
	{% endfor %}
	<br>
	{% include 'partial/pagination.tpl' %}
{% else %}
	<h3 class="text-danger">{{ 'Nothing found'|trans }}</h3>
{% endif %}
