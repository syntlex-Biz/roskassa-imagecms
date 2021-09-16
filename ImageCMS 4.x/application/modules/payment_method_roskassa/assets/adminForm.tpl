<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Main currency', 'payment_method_roskassa')} :</label>
    <div class="controls maincurrText">
        <span>{echo \Currency\Currency::create()->getMainCurrency()->getName()}</span>
        <span>({echo \Currency\Currency::create()->getMainCurrency()->getCode()})</span>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Currency payment service', 'payment_method_roskassa')} :</label> {/*}Валюта оплаты услуг{ */}
    <div class="controls">
        {foreach \Currency\Currency::create()->getCurrencies() as $currency}
                <label>
                    <input type="radio" name="payment_method_roskassa[merchant_currency]"
                           {if $data['merchant_currency']}
                               {if $data['merchant_currency'] == $currency->getId()}
                                   checked="checked"
                               {/if}    
                           {else:}
                               {if \Currency\Currency::create()->getMainCurrency()->getId() == $currency->getId()}
                                   checked="checked"
                               {/if} 
                           {/if}
                           value="{echo $currency->getId()}"
                           />
                    <span>{echo $currency->getName()}({echo $currency->getCode()})</span>
                </label>

        {/foreach}
    </div>
</div>

<br/>
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Merchant ID', 'payment_method_roskassa')}:</label>
    <div class="controls">
        <input type="text" name="payment_method_roskassa[login]" value="{echo $data['login']}"  />
    </div>
</div>          
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Password', 'payment_method_roskassa')}:</label>
    <div class="controls">
        <input type="text" name="payment_method_roskassa[password]" value="{echo $data['password']}"  />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Test mode', 'payment_method_roskassa')}:</label>
    <div class="controls">
        <label>
            <input type="radio" name="payment_method_roskassa[test]" value="0"
              {if $data['test'] == '0'}
                checked="checked"
              {/if}   
            />
            <span>{lang('Off', 'payment_method_roskassa')}</span>
        </label>
        <label>
            <input type="radio" name="payment_method_roskassa[test]" value="1"
              {if $data['test'] == '1'}
                checked="checked"
              {/if}   
            />
            <span>{lang('On', 'payment_method_roskassa')}</span>
        </label>
    </div>
</div>        
<div class="control-group">
    <label class="control-label" for="inputRecCount">{lang('Merchant settings', 'payment_method_roskassa')}:</label>
    <div class="controls">
        Result URL: {echo site_url('payment_method_roskassa/callback')}<br/>
        Success URL: {echo shop_url('profile/')}<br/>
        Fail URL: {echo shop_url('profile/')}<br/><br/>
    </div>
</div>
