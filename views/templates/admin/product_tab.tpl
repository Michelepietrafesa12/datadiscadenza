<div class="panel" id="datadiscadenza-panel">
    <h3><i class="icon-calendar"></i> {l s='Data di Scadenza' mod='datadiscadenza'}</h3>
    <div class="form-group">
        <label class="control-label col-lg-3" for="expiration_date">
            {l s='Data di scadenza' mod='datadiscadenza'}
        </label>
        <div class="col-lg-4">
            <input type="date"
                   id="expiration_date"
                   name="expiration_date"
                   class="form-control"
                   value="{$expiration_date|escape:'html':'UTF-8'}" />
            <p class="help-block">
                {l s='Inserisci la data di scadenza del prodotto. Lascia vuoto se non applicabile.' mod='datadiscadenza'}
            </p>
        </div>
    </div>
</div>
