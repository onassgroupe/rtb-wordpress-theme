/* onass-live-edit — Customizer Controls Panel
 * ─────────────────────────────────────────────
 * Listens for postMessage events from the preview iframe:
 *   onass:setting-updated → marks setting dirty → enables Publish button
 *   focus-control         → opens + focuses the matching control
 *   focus-section         → expands the matching section
 */
(function ($) {
    'use strict';

    wp.customize.bind('ready', function () {

        /* ── onass:setting-updated ──────────────────────────────
           Inline edit in the preview → propagate to the controls panel.
           Marks the setting as dirty → activates the "Publish" button.
        ──────────────────────────────────────────────────────── */
        wp.customize.previewer.bind('onass:setting-updated', function (data) {
            var setting = wp.customize(data.id);
            if (setting) {
                setting.set(data.value);
                // Also update the control input visually so it stays in sync
                var control = wp.customize.control(data.id);
                if (control && control.container) {
                    control.container.find('input, textarea').val(data.value);
                }
            }
        });

        /* ── focus-control ──────────────────────────────────────
           Preview click on [data-live] → expand section → focus control.
        ──────────────────────────────────────────────────────── */
        wp.customize.previewer.bind('focus-control', function (id) {
            var control = wp.customize.control(id);
            if (!control) { return; }

            var section = wp.customize.section(control.section());
            if (section) {
                // If section lives inside a panel, expand the panel first
                var panelId = section.params && section.params.panel;
                if (panelId) {
                    var panel = wp.customize.panel(panelId);
                    if (panel) {
                        panel.expand({
                            completeCallback: function () {
                                section.expand({
                                    completeCallback: function () {
                                        control.focus();
                                    }
                                });
                            }
                        });
                        return;
                    }
                }
                section.expand({
                    completeCallback: function () {
                        control.focus();
                    }
                });
            } else {
                control.focus();
            }
        });

        /* ── focus-section ──────────────────────────────────────
           Preview badge click → expand the section in the left panel.
        ──────────────────────────────────────────────────────── */
        wp.customize.previewer.bind('focus-section', function (id) {
            var section = wp.customize.section(id);
            if (!section) { return; }

            var panelId = section.params && section.params.panel;
            if (panelId) {
                var panel = wp.customize.panel(panelId);
                if (panel) {
                    panel.expand({
                        completeCallback: function () {
                            section.expand();
                        }
                    });
                    return;
                }
            }
            section.expand();
        });

    });

}(jQuery));
