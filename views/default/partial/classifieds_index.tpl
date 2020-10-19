
<div class="row">
{% for classified in classifieds %}
	<div class="col-xl-3 col-lg-4 col-sm-6">
		<div class="classifieds_index overflow_hidden {% if classified.promoted %}classifieds_index_promoted{% endif %}">
			{% if classified.promoted %}<div class="bar_promoted">{{ 'Promoted'|trans }}</div>{% endif %}
			<h5 class="name"><a href="{{ path('classified',classified.id,classified.slug) }}" title="{{ classified.name }}" class="main-color-2">{{ classified.name }}</a></h5>
			{% if classified.salary>0 %}
				<h6><strong>{{ classified.salary|showCurrency }}{% if classified.salary_net %} {{ classified.salary_net|trans }}{% endif %}{% if classified.salary_negotiate %} <span class="small">({{ 'to negotiate'|trans }})</span>{% endif %}</strong></h6>
			{% else %}
				<h6>&nbsp;</h6>
			{% endif %}
			<div class="row no-gutters">
				<div class="col-5">
					<a href="{{ path('classified',classified.id,classified.slug) }}" title="{{ classified.name }}">
						<img {% if classified.thumb %}class="lazy" data-src="upload/photos/{{ classified.thumb }}"{% endif %} src="views/{{ settings.template }}/images/no_image.png"
						alt="{{ classified.name }}" onerror="this.src='views/{{ settings.template }}/images/no_image.png'">
					</a>
				</div>
				<div class="col-7 classifieds_index_right">
					{% if classified.company_name %}<h6><a href="{{ path('profile',0,classified.username) }}" title="{{ 'Profile of'|trans }}: {{ classified.username }}" class="main-color-2">{{ classified.company_name }}</a></h6>{% endif %}
					{% if classified.state_name %}
							<h6><a href="{{ path('classifieds') }}?state={{ classified.state_slug }}" title="{{ 'State'|trans }}: {{ classified.state_name }}" class="main-color-2">{{ classified.state_name }}</a></h6>
							{% if classified.state2_name %}
								<h6><a href="{{ path('classifieds') }}?state={{ classified.state_slug }}&state2={{ classified.state2_slug }}" title="{{ 'State'|trans }}: {{ classified.state2.name }}" class="main-color-2">{{ classified.state2_name }}</a></h6>
							{% endif %}
					{% endif %}
				</div>
			</div>
		</div>
	</div>
{% endfor %}
</div>
