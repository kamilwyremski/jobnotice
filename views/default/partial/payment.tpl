<form method="post">
    <input type="hidden" name="action" value="new_payment">
    <input type="hidden" name="item_id" value="{{ payment_item_id }}">
    <input type="hidden" name="type" value="{{ payment_type }}">
    {% if settings.pay_by_dotpay %}
        <input type="submit" value="{{ 'Pay by Dotpay'|trans }}" class="btn btn-2" formaction="{{ settings.base_url }}/php/payment_dotpay.php">
    {% endif %}
    {% if settings.pay_by_paypal %}
        <input type="submit" value="{{ 'Pay by PayPal'|trans }}" class="btn btn-2" formaction="{{ settings.base_url }}/php/payment_paypal.php">
    {% endif %}
    {% if settings.pay_by_p24 %}
        <input type="submit" value="{{ 'Pay by Przelewy24'|trans }}" class="btn btn-2" formaction="{{ settings.base_url }}/php/payment_p24.php">
    {% endif %}
</form>