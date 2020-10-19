
<div class="account_employer">
  {% if error.account_employer %}
    <div class="alert alert-danger" role="alert"><i class="fas fa-exclamation"></i> {{ error.account_employer }}</div>
  {% endif %}
  <div class="form-group">
    <label for="account_name">{{ 'Company name'|trans }}</label>
    <input type="text" class="form-control" id="account_name" name="name" placeholder="{{ 'Company name'|trans }}" required value="{{ input.name }}" title="{{ 'Company name'|trans }}" maxlength="64">
  </div>
  <div class="form-group">
    <label for="account_nip">{{ 'Tax ID number'|trans }}</label>
    <input type="text" class="form-control" id="account_nip" name="nip" placeholder="{{ 'Tax ID number'|trans }}" required value="{{ input.nip }}" title="{{ 'Tax ID number'|trans }}" maxlength="64">
  </div>
  <div class="form-group">
    <label for="add_address">{{ 'Address'|trans }}:</label>
    <input type="text" class="form-control" name="address" placeholder="{{ '1600 Pennsylvania Avenue NW, Washington, D.C. 20500'|trans }}" id="add_address" maxlength="512" value="{{ input.address }}" title="{{ 'Enter the address'|trans }}">
  </div>
</div>
