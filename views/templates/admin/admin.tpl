<fieldset>
    <h2>Sync customer lists</h2>
    <p>Here you can choose to sync your existing Customer lists with Sendy.</p>
    <p>Clicking any language button below will sync all customers with the corresponding CUSTOMER list above for that language.</p>
    <p>The setting above to respect the customer opt-in to your newsletter will be respected. So that if respecting the users opt-in is enabled, only customers
    that have actively opted-in to your newsletter will be added.</p>
    <p>This is normally only needed once, after the first installation but you can run it anytime, for instance if you change lists above etc.
    If a customer is already subscribed to a list, nothing happens.
    </p>
    <p></p>

    <form action="" method="post">
        {foreach from=$sendyBack.availableLanguages item=lang}
        <div>
            <input style="width:100px" class="btn btn-default" type="submit" name="sendy_integration_customers_sync_form" value="{$lang.iso_code}" />
        </div>
        {/foreach}
    </form>
</fieldset>