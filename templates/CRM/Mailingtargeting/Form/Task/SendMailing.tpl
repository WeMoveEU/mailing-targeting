
<div class="crm-block crm-form-block">
  <div class="crm-section">
    <div><label>{$form.target.label}</label></div>
    <div>{$form.target.html}</div>
  </div>
  <div class="crm-section">
    <div><label>{$form.targetmid.label}</label></div>
    <div>{$form.targetmid.html}</div>
  </div>
  <div class="crm-section">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
</div>

<script type="text/javascript">{literal}
CRM.$(function($) {
	$('[name=target]').on('click', function() {
		var target = $('[name=target]').filter(':checked').val();
		if (target == 'new') {
			$('[name=targetmid]').attr('disabled', 'disabled');
		} else {
			var options = "";
      var optlist = 'completed';
      if (target == 'existing') {
        optlist = 'drafts';
      }
      CRM.vars.Mailingtargeting[optlist].forEach(function(opt, i) {
				options += "<option value=" + opt.id + ">" + opt.name + "</option>";
			});
			$('[name=targetmid]')
				.html(options)
				.removeAttr('disabled');
		}
	});
});
{/literal}</script>
