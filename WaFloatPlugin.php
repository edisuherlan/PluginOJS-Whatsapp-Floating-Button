<?php

/**
 * @file plugins/generic/waFloat/WaFloatPlugin.php
 *
 * Author  : Edi Suherlan
 * Company : audhighasu.com
 * Contact : audhighasu@gmail.com
 *
 * @class WaFloatPlugin
 * @brief Floating WhatsApp button for OJS journals (free / watermarked).
 */

namespace APP\plugins\generic\waFloat;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\core\PKPPageRouter;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class WaFloatPlugin extends GenericPlugin
{
    public const CREATOR_NAME = 'Edi Suherlan';
    public const CREATOR_URL = 'https://audhighasu.com';
    public const CREATOR_EMAIL = 'audhighasu@gmail.com';

    /** @var list<string> */
    public const COLOR_STYLES = [
        'whatsapp',
        'emerald',
        'ocean',
        'sunset',
        'midnight',
        'orchid',
    ];

    /** @var list<string> */
    public const POSITIONS = [
        'bottom-right',
        'bottom-left',
        'top-right',
        'top-left',
        'custom',
    ];

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!$success || Application::isUnderMaintenance()) {
            return $success;
        }

        if ($this->getEnabled($mainContextId)) {
            $this->addLocaleData();
            Hook::add('TemplateManager::display', $this->registerAssets(...));
            Hook::add('Templates::Common::Footer::PageFooter', $this->renderFloat(...));
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.waFloat.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.waFloat.description');
    }

    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, [
                            'verb' => 'settings',
                            'plugin' => $this->getName(),
                            'category' => 'generic',
                        ]),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb)
        );
    }

    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', $this->smartyPluginUrl(...));

                $form = new WaFloatSettingsForm($this, $context ? $context->getId() : Application::CONTEXT_SITE);

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    public function ensureDefaults(int $contextId): void
    {
        $defaults = [
            'showFrontend' => ['value' => true, 'type' => 'bool'],
            'phone' => ['value' => '6281234567890', 'type' => 'string'],
            'message' => ['value' => (string) __('plugins.generic.waFloat.settings.message.default'), 'type' => 'string'],
            'buttonLabel' => ['value' => (string) __('plugins.generic.waFloat.settings.buttonLabel.default'), 'type' => 'string'],
            'position' => ['value' => 'bottom-right', 'type' => 'string'],
            'offsetX' => ['value' => '24', 'type' => 'string'],
            'offsetY' => ['value' => '24', 'type' => 'string'],
            'customTop' => ['value' => '', 'type' => 'string'],
            'customRight' => ['value' => '24', 'type' => 'string'],
            'customBottom' => ['value' => '24', 'type' => 'string'],
            'customLeft' => ['value' => '', 'type' => 'string'],
            'colorStyle' => ['value' => 'whatsapp', 'type' => 'string'],
            'showLabel' => ['value' => true, 'type' => 'bool'],
            'showWatermark' => ['value' => true, 'type' => 'bool'],
        ];

        foreach ($defaults as $name => $meta) {
            $current = $this->getSetting($contextId, $name);
            if ($current === null || $current === '') {
                $this->updateSetting($contextId, $name, $meta['value'], $meta['type']);
            }
        }
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        // Convert leading 0 (ID local) to 62
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    public function buildWhatsAppUrl(string $phone, string $message): string
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return '#';
        }
        $url = 'https://wa.me/' . $phone;
        $message = trim($message);
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }

    public function registerAssets(string $hookName, array $params): bool
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return false;
        }

        $router = $request->getRouter();
        if (!$router instanceof PKPPageRouter) {
            return false;
        }

        $contextId = (int) $context->getId();
        $this->ensureDefaults($contextId);

        if (!$this->getSetting($contextId, 'showFrontend')) {
            return false;
        }

        $phone = $this->normalizePhone((string) $this->getSetting($contextId, 'phone'));
        if ($phone === '') {
            return false;
        }

        $templateMgr = TemplateManager::getManager($request);
        $cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/wa-float.css?v=1.0.1';
        $templateMgr->addStyleSheet('waFloat', $cssUrl, [
            'contexts' => ['frontend'],
        ]);

        return false;
    }

    public function renderFloat(string $hookName, array $params): bool
    {
        $output = &$params[2];

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return false;
        }

        $contextId = (int) $context->getId();
        $this->ensureDefaults($contextId);

        if (!$this->getSetting($contextId, 'showFrontend')) {
            return false;
        }

        $phone = (string) $this->getSetting($contextId, 'phone');
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return false;
        }

        $message = (string) $this->getSetting($contextId, 'message');
        $buttonLabel = trim((string) $this->getSetting($contextId, 'buttonLabel'));
        if ($buttonLabel === '') {
            $buttonLabel = (string) __('plugins.generic.waFloat.settings.buttonLabel.default');
        }

        $position = (string) $this->getSetting($contextId, 'position');
        if (!in_array($position, self::POSITIONS, true)) {
            $position = 'bottom-right';
        }

        $colorStyle = (string) $this->getSetting($contextId, 'colorStyle');
        if (!in_array($colorStyle, self::COLOR_STYLES, true)) {
            $colorStyle = 'whatsapp';
        }

        $showLabel = (bool) $this->getSetting($contextId, 'showLabel');

        $offsetX = $this->sanitizeLength((string) $this->getSetting($contextId, 'offsetX'), '24');
        $offsetY = $this->sanitizeLength((string) $this->getSetting($contextId, 'offsetY'), '24');
        $customTop = $this->sanitizeLength((string) $this->getSetting($contextId, 'customTop'), '');
        $customRight = $this->sanitizeLength((string) $this->getSetting($contextId, 'customRight'), '');
        $customBottom = $this->sanitizeLength((string) $this->getSetting($contextId, 'customBottom'), '');
        $customLeft = $this->sanitizeLength((string) $this->getSetting($contextId, 'customLeft'), '');

        $href = htmlspecialchars($this->buildWhatsAppUrl($phone, $message), ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');
        $aria = htmlspecialchars((string) __('plugins.generic.waFloat.aria'), ENT_QUOTES, 'UTF-8');

        $styleParts = [];
        if ($position === 'custom') {
            if ($customTop !== '') {
                $styleParts[] = 'top:' . $customTop;
            }
            if ($customRight !== '') {
                $styleParts[] = 'right:' . $customRight;
            }
            if ($customBottom !== '') {
                $styleParts[] = 'bottom:' . $customBottom;
            }
            if ($customLeft !== '') {
                $styleParts[] = 'left:' . $customLeft;
            }
            // Fallback if no custom coords filled
            if ($styleParts === []) {
                $styleParts[] = 'right:' . $offsetX;
                $styleParts[] = 'bottom:' . $offsetY;
            }
        } else {
            $styleParts[] = '--wa-float-offset-x:' . $offsetX;
            $styleParts[] = '--wa-float-offset-y:' . $offsetY;
        }
        $inlineStyle = $styleParts !== [] ? ' style="' . htmlspecialchars(implode(';', $styleParts), ENT_QUOTES, 'UTF-8') . '"' : '';

        $posClass = 'wa-float--' . htmlspecialchars($position, ENT_QUOTES, 'UTF-8');
        $colorClass = 'wa-float--color-' . htmlspecialchars($colorStyle, ENT_QUOTES, 'UTF-8');

        $labelHtml = $showLabel
            ? '<span class="wa-float__label">' . $labelEsc . '</span>'
            : '<span class="wa-float__label wa-float__label--sr">' . $labelEsc . '</span>';

        $icon = $this->getWhatsAppIconSvg();
        $btnMod = $showLabel ? '' : ' wa-float__btn--icon-only';

        $output .=
            '<div class="wa-float ' . $posClass . ' ' . $colorClass . '" id="waFloatRoot"' . $inlineStyle . ' data-wa-float="1">' .
            '<a class="wa-float__btn' . $btnMod . '" href="' . $href . '" target="_blank" rel="noopener noreferrer" aria-label="' . $aria . '" title="' . $labelEsc . '">' .
            '<span class="wa-float__pulse" aria-hidden="true"></span>' .
            '<span class="wa-float__icon" aria-hidden="true">' . $icon . '</span>' .
            $labelHtml .
            '</a>' .
            '</div>';

        return false;
    }

    protected function sanitizeLength(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        // Allow numbers with optional px/%/rem/em/vh/vw
        if (preg_match('/^-?\d+(\.\d+)?(px|%|rem|em|vh|vw)?$/i', $value)) {
            if (preg_match('/^-?\d+(\.\d+)?$/i', $value)) {
                return $value . 'px';
            }
            return $value;
        }
        return $fallback;
    }

    protected function getWhatsAppIconSvg(): string
    {
        return '<svg class="wa-float__svg" viewBox="0 0 32 32" width="28" height="28" focusable="false" aria-hidden="true"><path fill="currentColor" d="M16.01 3C9.38 3 4 8.37 4 14.99c0 2.11.55 4.16 1.6 5.97L4 29l8.24-1.56A11.94 11.94 0 0 0 16.01 27C22.64 27 28 21.63 28 15.01 28 8.37 22.64 3 16.01 3zm0 21.82c-1.82 0-3.6-.49-5.15-1.42l-.37-.22-4.89.93.94-4.76-.24-.39A9.8 9.8 0 0 1 6.2 15c0-5.41 4.41-9.81 9.81-9.81 5.41 0 9.81 4.4 9.81 9.81 0 5.41-4.4 9.82-9.81 9.82zm5.39-7.35c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.04-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35z"/></svg>';
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\waFloat\WaFloatPlugin', '\WaFloatPlugin');
}
