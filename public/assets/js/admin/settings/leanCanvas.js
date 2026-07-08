(function () {
  'use strict';

  function showToast(type, message) {
    var toast = document.createElement('div');
    toast.className = 'org-admin-toast account-admin-toast';
    toast.style.background = type === 'error' ? '#fee2e2' : '#dcfce7';
    toast.style.color = type === 'error' ? '#991b1b' : '#166534';
    toast.style.boxShadow = '0 10px 30px rgba(15,23,42,.12)';
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(function () {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
    });
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      setTimeout(function () { toast.remove(); }, 250);
    }, 2800);
  }

  function addListener(cleanups, target, type, handler, options) {
    if (!target || !target.addEventListener) return;
    target.addEventListener(type, handler, options);
    cleanups.push(function () {
      target.removeEventListener(type, handler, options);
    });
  }

  function init(root) {
    var scope = root && root.querySelector ? root : document;
    var card = scope.querySelector('#leanCanvasTemplateCard');
    if (!card) return function () {};

    var deleteUrl = card.getAttribute('data-delete-url') || '';
    var hasTemplate = card.getAttribute('data-has-template') === '1';
    var deleteBtn = scope.querySelector('#leanCanvasDeleteBtn');
    var replaceBtn = scope.querySelector('#leanCanvasReplaceBtn');
    var cancelReplaceBtn = scope.querySelector('#leanCanvasCancelReplaceBtn');
    var uploadForm = scope.querySelector('#leanCanvasTemplateForm');
    var fileInput = scope.querySelector('#leanCanvasTemplateFile');
    var submitBtn = scope.querySelector('#leanCanvasTemplateSubmit');
    var chooseBtn = scope.querySelector('#leanCanvasChooseBtn');
    var fileStatus = scope.querySelector('#leanCanvasFileStatus');
    var cleanups = [];

    function resetFileChoice() {
      if (fileInput) fileInput.value = '';
      if (fileStatus) fileStatus.textContent = 'No file chosen';
      if (submitBtn) submitBtn.disabled = true;
    }

    function showUploadForm() {
      if (!uploadForm) return;
      uploadForm.hidden = false;
      if (replaceBtn) replaceBtn.hidden = true;
      if (chooseBtn && typeof chooseBtn.focus === 'function') {
        window.setTimeout(function () { chooseBtn.focus(); }, 30);
      }
    }

    function hideUploadForm() {
      if (!uploadForm || !hasTemplate) return;
      uploadForm.hidden = true;
      if (replaceBtn) replaceBtn.hidden = false;
      resetFileChoice();
    }

    function submitDelete() {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = deleteUrl;
      form.style.display = 'none';

      var sourceCsrf = uploadForm ? uploadForm.querySelector('input[type="hidden"]') : null;
      if (sourceCsrf) {
        var csrfClone = document.createElement('input');
        csrfClone.type = 'hidden';
        csrfClone.name = sourceCsrf.name;
        csrfClone.value = sourceCsrf.value;
        form.appendChild(csrfClone);
      }

      document.body.appendChild(form);
      form.submit();
    }

    if (replaceBtn) {
      addListener(cleanups, replaceBtn, 'click', showUploadForm);
    }

    if (cancelReplaceBtn) {
      addListener(cleanups, cancelReplaceBtn, 'click', hideUploadForm);
    }

    if (chooseBtn && fileInput) {
      addListener(cleanups, chooseBtn, 'keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          fileInput.click();
        }
      });
    }

    if (fileInput) {
      addListener(cleanups, fileInput, 'change', function () {
        var name = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0].name : '';
        if (fileStatus) fileStatus.textContent = name !== '' ? name : 'No file chosen';
        if (submitBtn) submitBtn.disabled = name === '';
      });
    }

    if (uploadForm) {
      addListener(cleanups, uploadForm, 'submit', function (event) {
        event.preventDefault();

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
          showToast('error', 'Please choose a PDF or Word file to upload.');
          return;
        }

        var formData = new FormData(uploadForm);
        var originalLabel = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.textContent = 'Uploading...';
        }

        fetch(uploadForm.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function () {
            window.location.reload();
          })
          .catch(function () {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalLabel;
            }
            showToast('error', 'Something went wrong while uploading.');
          });
      });
    }

    if (deleteBtn) {
      addListener(cleanups, deleteBtn, 'click', function () {
        if (!window.AdminDeleteConfirm || typeof window.AdminDeleteConfirm.ask !== 'function') {
          if (window.confirm(deleteBtn.getAttribute('data-confirm-title') || 'Delete template?')) {
            submitDelete();
          }
          return;
        }

        window.AdminDeleteConfirm.ask({
          title: deleteBtn.getAttribute('data-confirm-title') || 'Delete Lean Canvas template?',
          message: deleteBtn.getAttribute('data-confirm-message') || 'This action cannot be undone.',
          confirmLabel: 'Delete'
        }).then(function (confirmed) {
          if (confirmed) submitDelete();
        });
      });
    }

    return function () {
      cleanups.forEach(function (cleanup) { cleanup(); });
      resetFileChoice();
    };
  }

  window.AdminLeanCanvas = { init: init };
})();
