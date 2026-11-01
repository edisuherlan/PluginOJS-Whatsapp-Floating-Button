{**
 * WA-Float — settings
 * Author : Edi Suherlan | audhighasu.com
 *}
<script>
	$(function() {ldelim}
		$('#waFloatSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		function syncCustom() {ldelim}
			var pos = $('#position').val();
			var isCustom = pos === 'custom';
			$('#waFloatCustomCoords').toggle(isCustom);
			$('#waFloatOffsetCoords').toggle(!isCustom);
		{rdelim}
		$('#position').on('change', syncCustom);
		syncCustom();
	{rdelim});
</script>

<form class="pkp_form" id="waFloatSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="waFloatSettingsFormNotification"}

	<div id="description" class="description">{translate key="plugins.generic.waFloat.settings.description"}</div>

	<div class="pkp_notification pkp_notification_notice" style="margin:0.75rem 0 1rem;">
		{translate key="plugins.generic.waFloat.settings.watermarkNotice" creatorName=$creatorName creatorUrl=$creatorUrl}
	</div>

	{fbvFormArea id="waFloatSettingsArea"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showFrontend" name="showFrontend" checked=$showFrontend label="plugins.generic.waFloat.settings.showFrontend" translate=true}
			{fbvElement type="checkbox" id="showLabel" name="showLabel" checked=$showLabel label="plugins.generic.waFloat.settings.showLabel" translate=true}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.waFloat.settings.phone" for="phone" description="plugins.generic.waFloat.settings.phone.description"}
			{fbvElement type="text" id="phone" value=$phone maxlength=32 required=true}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.waFloat.settings.message" for="message" description="plugins.generic.waFloat.settings.message.description"}
			{fbvElement type="textarea" id="message" value=$message rich=false rows=3}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.waFloat.settings.buttonLabel" for="buttonLabel"}
			{fbvElement type="text" id="buttonLabel" value=$buttonLabel maxlength=60}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.waFloat.settings.position" for="position" description="plugins.generic.waFloat.settings.position.description"}
			{fbvElement type="select" id="position" from=$positions selected=$position translate=false}
		{/fbvFormSection}

		<div id="waFloatOffsetCoords">
			{fbvFormSection title="plugins.generic.waFloat.settings.offsetX" for="offsetX" description="plugins.generic.waFloat.settings.offset.description"}
				{fbvElement type="text" id="offsetX" value=$offsetX maxlength=16}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.waFloat.settings.offsetY" for="offsetY"}
				{fbvElement type="text" id="offsetY" value=$offsetY maxlength=16}
			{/fbvFormSection}
		</div>

		<div id="waFloatCustomCoords" style="display:none;">
			{fbvFormSection title="plugins.generic.waFloat.settings.customTop" for="customTop" description="plugins.generic.waFloat.settings.custom.description"}
				{fbvElement type="text" id="customTop" value=$customTop maxlength=16}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.waFloat.settings.customRight" for="customRight"}
				{fbvElement type="text" id="customRight" value=$customRight maxlength=16}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.waFloat.settings.customBottom" for="customBottom"}
				{fbvElement type="text" id="customBottom" value=$customBottom maxlength=16}
			{/fbvFormSection}
			{fbvFormSection title="plugins.generic.waFloat.settings.customLeft" for="customLeft"}
				{fbvElement type="text" id="customLeft" value=$customLeft maxlength=16}
			{/fbvFormSection}
		</div>

		{fbvFormSection title="plugins.generic.waFloat.settings.colorStyle" for="colorStyle" description="plugins.generic.waFloat.settings.colorStyle.description"}
			{fbvElement type="select" id="colorStyle" from=$colorStyles selected=$colorStyle translate=false}
		{/fbvFormSection}
	{/fbvFormArea}

	<p class="description" style="margin-top:0.5rem;">
		{translate key="plugins.generic.waFloat.settings.footerCredit" creatorName=$creatorName creatorEmail=$creatorEmail creatorUrl=$creatorUrl}
	</p>

	{fbvFormButtons}
</form>
