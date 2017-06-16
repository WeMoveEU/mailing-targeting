
{foreach from=$setting_descriptions key=elementName item=description}
    <div class="crm-section">
        <div>{$form.$elementName.label}</div>
        <div>
          {$form.$elementName.html}<br>
          <span class="description">{$description}</span>
        </div>
    </div>
{/foreach}

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

