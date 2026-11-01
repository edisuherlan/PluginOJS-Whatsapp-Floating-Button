<?php

/**
 * @file plugins/generic/waFloat/WaFloatSettingsForm.php
 *
 * Author  : Edi Suherlan
 * Company : audhighasu.com
 * Contact : audhighasu@gmail.com
 *
 * @class WaFloatSettingsForm
 * @brief Settings form for WA-Float plugin.
 */

namespace APP\plugins\generic\waFloat;

use APP\template\TemplateManager;
use PKP\form\Form;

class WaFloatSettingsForm extends Form
{
    /** @var int */
    public $_contextId;

    /** @var WaFloatPlugin */
    public $_plugin;

    public function __construct(WaFloatPlugin $plugin, int $contextId)
    {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'phone', 'required', 'plugins.generic.waFloat.settings.phone.required'));
    }

    public function initData()
    {
        $this->_plugin->ensureDefaults($this->_contextId);
        $p = $this->_plugin;
        $id = $this->_contextId;

        $this->_data = [
            'showFrontend' => (bool) $p->getSetting($id, 'showFrontend'),
            'phone' => (string) $p->getSetting($id, 'phone'),
            'message' => (string) $p->getSetting($id, 'message'),
            'buttonLabel' => (string) $p->getSetting($id, 'buttonLabel'),
            'position' => (string) $p->getSetting($id, 'position'),
            'offsetX' => (string) $p->getSetting($id, 'offsetX'),
            'offsetY' => (string) $p->getSetting($id, 'offsetY'),
            'customTop' => (string) $p->getSetting($id, 'customTop'),
            'customRight' => (string) $p->getSetting($id, 'customRight'),
            'customBottom' => (string) $p->getSetting($id, 'customBottom'),
            'customLeft' => (string) $p->getSetting($id, 'customLeft'),
            'colorStyle' => (string) $p->getSetting($id, 'colorStyle'),
            'showLabel' => (bool) $p->getSetting($id, 'showLabel'),
            'showWatermark' => true,
        ];
    }

    public function readInputData()
    {
        $this->readUserVars([
            'showFrontend',
            'phone',
            'message',
            'buttonLabel',
            'position',
            'offsetX',
            'offsetY',
            'customTop',
            'customRight',
            'customBottom',
            'customLeft',
            'colorStyle',
            'showLabel',
        ]);
    }

    public function fetch($request, $template = null, $display = false)
    {
        $positionOptions = [];
        foreach (WaFloatPlugin::POSITIONS as $pos) {
            $positionOptions[$pos] = __('plugins.generic.waFloat.settings.position.' . str_replace('-', '_', $pos));
        }

        $colorOptions = [];
        foreach (WaFloatPlugin::COLOR_STYLES as $style) {
            $colorOptions[$style] = __('plugins.generic.waFloat.settings.colorStyle.' . $style);
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->_plugin->getName(),
            'colorStyles' => $colorOptions,
            'positions' => $positionOptions,
            'creatorName' => WaFloatPlugin::CREATOR_NAME,
            'creatorUrl' => WaFloatPlugin::CREATOR_URL,
            'creatorEmail' => WaFloatPlugin::CREATOR_EMAIL,
        ]);
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        $id = $this->_contextId;
        $p = $this->_plugin;

        $position = (string) $this->getData('position');
        if (!in_array($position, WaFloatPlugin::POSITIONS, true)) {
            $position = 'bottom-right';
        }

        $colorStyle = (string) $this->getData('colorStyle');
        if (!in_array($colorStyle, WaFloatPlugin::COLOR_STYLES, true)) {
            $colorStyle = 'whatsapp';
        }

        $phone = trim((string) $this->getData('phone'));
        $normalized = $p->normalizePhone($phone);
        if ($normalized !== '') {
            $phone = $normalized;
        }

        $p->updateSetting($id, 'showFrontend', (bool) $this->getData('showFrontend'), 'bool');
        $p->updateSetting($id, 'phone', $phone, 'string');
        $p->updateSetting($id, 'message', trim((string) $this->getData('message')), 'string');
        $p->updateSetting($id, 'buttonLabel', trim((string) $this->getData('buttonLabel')), 'string');
        $p->updateSetting($id, 'position', $position, 'string');
        $p->updateSetting($id, 'offsetX', trim((string) $this->getData('offsetX')), 'string');
        $p->updateSetting($id, 'offsetY', trim((string) $this->getData('offsetY')), 'string');
        $p->updateSetting($id, 'customTop', trim((string) $this->getData('customTop')), 'string');
        $p->updateSetting($id, 'customRight', trim((string) $this->getData('customRight')), 'string');
        $p->updateSetting($id, 'customBottom', trim((string) $this->getData('customBottom')), 'string');
        $p->updateSetting($id, 'customLeft', trim((string) $this->getData('customLeft')), 'string');
        $p->updateSetting($id, 'colorStyle', $colorStyle, 'string');
        $p->updateSetting($id, 'showLabel', (bool) $this->getData('showLabel'), 'bool');
        // Free edition: watermark cannot be disabled via settings.
        $p->updateSetting($id, 'showWatermark', true, 'bool');

        parent::execute(...$functionArgs);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\waFloat\WaFloatSettingsForm', '\WaFloatSettingsForm');
}
