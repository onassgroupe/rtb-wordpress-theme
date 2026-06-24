/* onass-live-edit — Customizer Live Preview Engine
 * ─────────────────────────────────────────────────
 * Features:
 *   - Inline editing: click on [data-live] → contenteditable
 *   - Enter / Blur → save via wp.customize(key).set()
 *   - Escape → cancel
 *   - Undo stack per element (Ctrl/Cmd+Z restores previous value)
 *   - Navigation blocked inside .cs-zone (keeps preview stable)
 *   - Click .cs-badge → focus-section in left panel
 *   - Click [data-live] → focus-control in left panel
 *   - CSS feedback injected automatically
 *   - postMessage channel: onass:setting-updated
 *   - window.onassLiveEdit exposed for theme-level bindings
 */
(function ($) {
    'use strict';

    var DELAY   = 250;
    var CHANNEL = 'onass:setting-updated';

    /* ──────────────────────────────────────────────────
       Styles injectés dans le contexte Customizer
       ────────────────────────────────────────────────── */
    var css =
        /* Zones éditables */
        '.cs-zone { position: relative; }\n' +
        '.cs-zone:hover { outline: 2px solid rgba(0,124,186,.4); outline-offset: 0px; }\n' +

        /* Badge section */
        '.cs-badge {\n' +
        '  display: none; position: absolute; top: 0; left: 0; z-index: 9999;\n' +
        '  background: #007cba; color: #fff; font-size: 11px; font-weight: 700;\n' +
        '  letter-spacing: .05em; padding: 5px 12px;\n' +
        '  align-items: center; gap: 5px; cursor: pointer; pointer-events: auto;\n' +
        '  text-transform: uppercase; font-family: -apple-system,"Segoe UI",sans-serif;\n' +
        '  user-select: none; border-bottom-right-radius: 4px; transition: background .15s;\n' +
        '}\n' +
        '.cs-badge:hover { background: #005a87; }\n' +
        '.cs-zone:hover > .cs-badge { display: flex; }\n' +

        /* Variante accent (ex: section CTA colorée) */
        '.cs-zone--accent.cs-zone:hover > .cs-badge { background: #c9a227; }\n' +
        '.cs-zone--accent.cs-zone .cs-badge:hover { background: #a07c1a; }\n' +

        /* [data-live] — indicateur hover */
        '[data-live]:not([contenteditable="true"]) {\n' +
        '  cursor: text !important;\n' +
        '  transition: outline .1s, background .1s;\n' +
        '  border-radius: 2px;\n' +
        '}\n' +
        '[data-live]:not([contenteditable="true"]):hover {\n' +
        '  outline: 2px dashed #007cba !important;\n' +
        '  outline-offset: 3px;\n' +
        '  background: rgba(0,124,186,.06) !important;\n' +
        '}\n' +

        /* Mode édition inline active */
        '[data-live][contenteditable="true"] {\n' +
        '  outline: 2px solid #007cba !important;\n' +
        '  outline-offset: 3px;\n' +
        '  background: rgba(0,124,186,.08) !important;\n' +
        '  border-radius: 2px;\n' +
        '  cursor: text !important;\n' +
        '  min-width: 30px;\n' +
        '  white-space: pre-wrap;\n' +
        '}\n' +

        /* Tooltip */
        '[data-live][contenteditable="true"]::after {\n' +
        '  content: "↵ Entrée · Échap annuler · Ctrl+Z annuler · Alt+Click ouvre le control";\n' +
        '  position: absolute;\n' +
        '  bottom: calc(100% + 4px); left: 0;\n' +
        '  background: #007cba; color: #fff;\n' +
        '  font-size: 10px; font-family: -apple-system,"Segoe UI",sans-serif;\n' +
        '  padding: 3px 8px; border-radius: 3px;\n' +
        '  white-space: nowrap; pointer-events: none;\n' +
        '  z-index: 9999;\n' +
        '}\n' +

        /* Indicateur "modifié non publié" sur les zones */
        '.cs-zone.cs-dirty { outline: 2px solid rgba(202,136,0,.5) !important; }\n' +
        '.cs-zone.cs-dirty > .cs-badge::before {\n' +
        '  content: "● "; color: #ffd700;\n' +
        '}\n';

    $('<style id="onass-live-edit-css">').text(css).appendTo('head');

    /* ──────────────────────────────────────────────────
       Bloquer navigation dans les zones .cs-zone
       ────────────────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        var target = e.target;
        while (target && target.tagName !== 'A') {
            target = target.parentElement;
        }
        if (!target) return;
        var href = target.getAttribute('href') || '';
        if (href.charAt(0) === '#') return;
        if (!target.closest('.cs-zone')) return;
        e.preventDefault();
        e.stopPropagation();
    }, true);

    /* ──────────────────────────────────────────────────
       Clic sur badge → focus-section dans le panneau
       ────────────────────────────────────────────────── */
    $(document).on('click', '.cs-badge', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var sectionId = $(this).closest('[data-cs]').data('cs');
        if (sectionId && wp.customize && wp.customize.preview) {
            wp.customize.preview.send('focus-section', sectionId);
        }
    });

    /* ──────────────────────────────────────────────────
       Undo stack — stocke les valeurs précédentes
       (en mémoire session uniquement, non persisté)
       ────────────────────────────────────────────────── */
    var undoStack = {}; // { settingKey: [val1, val2, ...] }

    function pushUndo(key, value) {
        if (!undoStack[key]) { undoStack[key] = []; }
        undoStack[key].push(value);
        // Limiter à 20 états par setting
        if (undoStack[key].length > 20) { undoStack[key].shift(); }
    }

    function popUndo(key) {
        if (!undoStack[key] || !undoStack[key].length) { return null; }
        return undoStack[key].pop();
    }

    /* ──────────────────────────────────────────────────
       Marquer/démarquer la zone comme "dirty"
       ────────────────────────────────────────────────── */
    function markDirty($el) {
        $el.closest('.cs-zone').addClass('cs-dirty');
    }

    /* ──────────────────────────────────────────────────
       Édition inline — clic sur [data-live]
       ────────────────────────────────────────────────── */
    $(document).on('click', '[data-live]', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $el = $(this);
        if ($el.attr('contenteditable') === 'true') return;

        // Alt+Click → ouvre le control dans le sidebar (au lieu d'éditer inline)
        if (e.altKey) {
            var keyAlt = $el.data('live');
            if (keyAlt && wp.customize && wp.customize.preview) {
                wp.customize.preview.send('focus-control', keyAlt);
            }
            return;
        }

        var key      = $el.data('live');
        var original = $el.text();

        // NB : on N'envoie PAS focus-control ici — cela volait le focus DOM vers
        // l'input du sidebar et empêchait la saisie inline.
        // L'utilisateur peut ouvrir le control via le badge .cs-badge ou via Alt+Click.

        $el.attr('contenteditable', 'true').focus();

        // Sélectionner tout le texte
        var range = document.createRange();
        range.selectNodeContents($el[0]);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);

        function applyValue(newval) {
            if (newval !== original && newval !== '' && wp.customize) {
                pushUndo(key, original);
                markDirty($el);
                wp.customize(key, function (setting) {
                    setting.set(newval);
                });
                wp.customize.preview.send(CHANNEL, { id: key, value: newval });
            }
        }

        function save() {
            var newval = $el.text().replace(/\n/g, ' ').trim();
            $el.removeAttr('contenteditable');
            $el.off('.onassedit');
            applyValue(newval);
        }

        function cancel() {
            $el.text(original).removeAttr('contenteditable');
            $el.off('.onassedit');
        }

        $el.on('keydown.onassedit', function (ev) {
            if (ev.key === 'Enter' && !ev.shiftKey) {
                ev.preventDefault();
                save();
            }
            if (ev.key === 'Escape') {
                ev.preventDefault();
                cancel();
            }
            // Ctrl+Z / Cmd+Z → annuler la dernière modification sauvegardée
            if ((ev.ctrlKey || ev.metaKey) && ev.key === 'z') {
                ev.preventDefault();
                var prev = popUndo(key);
                if (prev !== null && wp.customize) {
                    $el.text(prev);
                    wp.customize(key, function (setting) {
                        setting.set(prev);
                    });
                    wp.customize.preview.send(CHANNEL, { id: key, value: prev });
                }
            }
        });

        $el.on('blur.onassedit', function () {
            setTimeout(save, 80);
        });
    });

    /* ──────────────────────────────────────────────────
       AUTO-BIND UNIVERSEL — édition sidebar → rendu live
       Pour chaque setting référencé via [data-live="key"],
       on bind la valeur du Customizer à l'élément DOM.
       Sécurité : .text() utilisé partout (jamais .html()).
       Perf : debounce 250ms, bind une seule fois par clé.
       ────────────────────────────────────────────────── */
    var boundKeys = {};

    function autoBindLiveKey(key) {
        if (!key || boundKeys[key]) return;
        boundKeys[key] = true;
        if (!wp.customize) return;
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                if (typeof newval !== 'string' && typeof newval !== 'number') return;
                $('[data-live="' + key + '"]')
                    .not('[contenteditable="true"]')
                    .text(String(newval));
            }, DELAY));
        });
    }

    function scanAndBindAll(root) {
        var $root = root ? $(root) : $(document);
        $root.find('[data-live]').addBack('[data-live]').each(function () {
            autoBindLiveKey($(this).attr('data-live'));
        });
    }

    // Bind initial dès que wp.customize est prêt
    if (wp.customize && wp.customize.bind) {
        wp.customize.bind('preview-ready', function () {
            scanAndBindAll();
        });
    }
    $(function () { scanAndBindAll(); });

    // MutationObserver — rebind si le thème injecte des [data-live] dynamiquement
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element
                        scanAndBindAll(node);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    /* ──────────────────────────────────────────────────
       Helpers live-update — exposés globalement
       pour que les thèmes puissent les appeler
       ────────────────────────────────────────────────── */

    /** Met à jour le texte d'un ou plusieurs sélecteurs CSS */
    function liveText(key, selector) {
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                $(selector).not('[contenteditable="true"]').text(newval);
            }, DELAY));
        });
    }

    /** Met à jour un lien tel: + le texte du champ */
    function liveTel(key) {
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                $('[data-live-tel="' + key + '"]').attr('href', 'tel:' + newval.replace(/\s+/g, ''));
                $('[data-live="' + key + '"]').not('[contenteditable="true"]').text(newval);
            }, DELAY));
        });
    }

    /** Met à jour un lien mailto: + le texte du champ */
    function liveMail(key) {
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                $('[data-live-mail="' + key + '"]').attr('href', 'mailto:' + newval);
                $('[data-live="' + key + '"]').not('[contenteditable="true"]').text(newval);
            }, DELAY));
        });
    }

    /** Met à jour un lien WhatsApp via data-live-wa */
    function liveWa(key) {
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                var num = newval.replace(/^\+/, '');
                $('[data-live-wa="' + key + '"], [data-live-wa]').attr('href', 'https://wa.me/' + num);
            }, DELAY));
        });
    }

    /** Met à jour un lien tel: dans une zone CTA (data-live-cta-tel) */
    function liveCtaTel(key) {
        wp.customize(key, function (setting) {
            setting.bind(_.debounce(function (newval) {
                $('[data-live-cta-tel]')
                    .not('[contenteditable="true"]')
                    .text(newval)
                    .attr('href', 'tel:' + newval.replace(/\s+/g, ''));
            }, DELAY));
        });
    }

    /* ──────────────────────────────────────────────────
       API publique
       ────────────────────────────────────────────────── */
    window.onassLiveEdit = {
        liveText:        liveText,
        liveTel:         liveTel,
        liveMail:        liveMail,
        liveWa:          liveWa,
        liveCtaTel:      liveCtaTel,
        autoBind:        autoBindLiveKey,
        scanAndBindAll:  scanAndBindAll,
        CHANNEL:         CHANNEL,
    };

}(jQuery));
