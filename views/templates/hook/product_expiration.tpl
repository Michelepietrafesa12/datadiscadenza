<div class="product-expiration-date{if $is_expired && $show_expired} product-expired{/if}">
    {if $show_label}
        <span class="expiration-label">
            <i class="material-icons">event</i>
            {$label_text|escape:'html':'UTF-8'}
        </span>
    {/if}
    <span class="expiration-value">{$expiration_date|escape:'html':'UTF-8'}</span>
    {if $is_expired && $show_expired}
        <span class="expiration-warning">
            {$expired_text|escape:'html':'UTF-8'}
        </span>
    {/if}
</div>
