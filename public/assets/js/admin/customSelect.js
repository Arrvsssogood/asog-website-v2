(function () {
  var ARROW_SVG = '<svg class="csel-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6l4 4 4-4"/></svg>';

  var SELECTORS = [
    '.field select',
    '.form-group select',
    'select.lf-select',
    'select.settings-select',
    'select.app-select-filter',
    'select.status-select'
  ];

  function initCsel(sel) {
    if (sel.closest('.csel')) return;
    if (sel.type === 'hidden') return;

    var wrap = document.createElement('div');
    wrap.className = 'csel';

    sel.classList.forEach(function (c) {
      wrap.classList.add('csel-ctx--' + c);
    });

    var trigger = document.createElement('div');
    trigger.className = 'csel-trigger';
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.innerHTML = '<span class="csel-val"></span>' + ARROW_SVG;

    var panel = document.createElement('div');
    panel.className = 'csel-panel';
    panel.setAttribute('role', 'listbox');

    wrap.appendChild(trigger);
    wrap.appendChild(panel);

    var internalStyleChange = false;

    var initiallyHidden = sel.style.display === 'none';
    if (initiallyHidden) {
      wrap.style.display = 'none';
    }

    sel.parentNode.insertBefore(wrap, sel);
    internalStyleChange = true;
    sel.style.display = 'none';
    wrap.appendChild(sel);

    var styleObs = new MutationObserver(function () {
      if (internalStyleChange) {
        internalStyleChange = false;
        return;
      }
      var extDisp = sel.style.display;
      if (extDisp === 'none') {
        wrap.style.display = 'none';
      } else {
        wrap.style.display = extDisp || '';
      }
      internalStyleChange = true;
      sel.style.display = 'none';
    });
    styleObs.observe(sel, { attributes: true, attributeFilter: ['style'] });

    function buildPanel() {
      panel.innerHTML = '';

      function addOpt(opt) {
        var item = document.createElement('div');
        var cls = 'csel-opt';
        if (!opt.value) cls += ' csel-opt--ph';
        if (opt.disabled) cls += ' csel-opt--dis';
        if (opt.value === sel.value) cls += ' csel-opt--sel';
        item.className = cls;
        item.textContent = opt.text;
        item.dataset.value = opt.value;
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          if (opt.disabled) return;
          sel.value = opt.value;
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          updateValue();
          close();
          trigger.focus();
        });
        return item;
      }

      var hasGroups = Array.from(sel.children).some(function (c) { return c.tagName === 'OPTGROUP'; });

      if (!hasGroups) {
        Array.from(sel.options).forEach(function (opt) {
          panel.appendChild(addOpt(opt));
        });
      } else {
        var first = true;
        Array.from(sel.children).forEach(function (child) {
          if (child.tagName === 'OPTGROUP') {
            if (!first) {
              var sep = document.createElement('div');
              sep.className = 'csel-grp-sep';
              panel.appendChild(sep);
            }
            var lbl = document.createElement('div');
            lbl.className = 'csel-grp-label';
            lbl.textContent = child.label;
            panel.appendChild(lbl);
            Array.from(child.children).forEach(function (opt) {
              panel.appendChild(addOpt(opt));
            });
            first = false;
          } else if (child.tagName === 'OPTION') {
            panel.appendChild(addOpt(child));
            first = false;
          }
        });
      }
    }

    function updateValue() {
      var valEl = trigger.querySelector('.csel-val');
      var sel_opt = sel.options[sel.selectedIndex];
      if (sel_opt) {
        valEl.textContent = sel_opt.text;
        if (!sel_opt.value) {
          valEl.classList.add('csel-val--ph');
        } else {
          valEl.classList.remove('csel-val--ph');
        }
      }
      panel.querySelectorAll('.csel-opt').forEach(function (el) {
        el.classList.toggle('csel-opt--sel', el.dataset.value === sel.value);
      });
    }

    function open() {
      document.querySelectorAll('.csel--open').forEach(function (el) {
        if (el !== wrap) el.classList.remove('csel--open');
      });
      buildPanel();
      // Reset any previous flip
      panel.style.top = '';
      panel.style.bottom = '';
      wrap.classList.add('csel--open');
      trigger.setAttribute('aria-expanded', 'true');
      // Viewport-aware: flip upward if panel overflows below
      var panelRect  = panel.getBoundingClientRect();
      var trigRect   = trigger.getBoundingClientRect();
      var spaceBelow = window.innerHeight - trigRect.bottom;
      var spaceAbove = trigRect.top;
      if (panelRect.bottom > window.innerHeight && spaceAbove > spaceBelow) {
        panel.style.top    = 'auto';
        panel.style.bottom = 'calc(100% + 3px)';
      }
      var selOpt = panel.querySelector('.csel-opt--sel');
      if (selOpt) selOpt.scrollIntoView({ block: 'nearest' });
    }

    function close() {
      wrap.classList.remove('csel--open');
      trigger.setAttribute('aria-expanded', 'false');
      panel.style.top    = '';
      panel.style.bottom = '';
    }

    trigger.addEventListener('click', function () {
      wrap.classList.contains('csel--open') ? close() : open();
    });

    trigger.addEventListener('keydown', function (e) {
      var isOpen = wrap.classList.contains('csel--open');
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        isOpen ? close() : open();
      } else if (e.key === 'Escape') {
        close();
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!isOpen) open();
        var idx = sel.selectedIndex;
        if (idx < sel.options.length - 1) {
          sel.selectedIndex = idx + 1;
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          updateValue();
        }
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        var idx = sel.selectedIndex;
        if (idx > 0) {
          sel.selectedIndex = idx - 1;
          sel.dispatchEvent(new Event('change', { bubbles: true }));
          updateValue();
        }
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) close();
    });

    var optObs = new MutationObserver(function () {
      updateValue();
    });
    optObs.observe(sel, { childList: true, subtree: true });

    updateValue();
  }

  function init(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll(SELECTORS.join(',')).forEach(function (sel) {
      try { initCsel(sel); } catch (e) {}
    });
  }

  window.AdminCustomSelect = window.AdminCustomSelect || {};
  window.AdminCustomSelect.init = init;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
