(function () {
  'use strict';

  function bindToggleLabels(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('[data-toggle-form]').forEach(function (form) {
      form.querySelectorAll('.settings-switch').forEach(function (switchEl) {
        var checkbox = switchEl.querySelector('input[type="checkbox"]');
        var stateLabel = switchEl.querySelector('.settings-switch-label');
        if (!checkbox || !stateLabel || checkbox.dataset.settingsToggleBound === '1') return;

        function updateLabel() {
          stateLabel.textContent = checkbox.checked ? 'ON' : 'OFF';
        }

        checkbox.dataset.settingsToggleBound = '1';
        checkbox.addEventListener('change', updateLabel);
        updateLabel();
      });
    });
  }

  function bindPasswordToggles(root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('[data-settings-password-toggle]').forEach(function (button) {
      var input = document.getElementById(button.getAttribute('aria-controls') || '');
      if (!input || button.dataset.settingsPasswordBound === '1') return;

      button.dataset.settingsPasswordBound = '1';
      button.addEventListener('click', function () {
        var willShow = input.type === 'password';
        input.type = willShow ? 'text' : 'password';
        button.classList.toggle('is-visible', willShow);
        button.setAttribute('aria-pressed', willShow ? 'true' : 'false');
        button.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
      });
    });
  }

  function init(root) {
    bindToggleLabels(root);
    bindPasswordToggles(root);

    if (window.AdminLeanCanvas && typeof window.AdminLeanCanvas.init === 'function') {
      return window.AdminLeanCanvas.init(root);
    }

    return function () {};
  }

  if (window.AdminShell && typeof window.AdminShell.register === 'function') {
    window.AdminShell.register('settings', { init: init });
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }
})();
