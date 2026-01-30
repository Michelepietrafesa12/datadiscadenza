<div class="product-expiration-date{if $is_expired} product-expired{/if}">
    <span class="expiration-label">
        <i class="material-icons">event</i>
        {l s='Data di scadenza:' mod='datadiscadenza'}
    </span>
    <span class="expiration-value">{$expiration_date|escape:'html':'UTF-8'}</span>
    {if $is_expired}
        <span class="expiration-warning">
            {l s='Prodotto scaduto' mod='datadiscadenza'}
        </span>
    {/if}
</div>
