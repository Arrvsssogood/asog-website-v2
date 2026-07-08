(function () {
    'use strict';

    var card = document.getElementById('leanCanvasTemplateCard');
    if (!card) return;

    var modal = document.getElementById('leanCanvasPreviewModal');
    var modalBody = document.getElementById('leanCanvasPreviewBody');
    var modalFoot = document.getElementById('leanCanvasPreviewFoot');
    var modalDownload = document.getElementById('leanCanvasPreviewDownload');

    var previewBtn = document.getElementById('leanCanvasPreviewBtn');
    var deleteBtn = document.getElementById('leanCanvasDeleteBtn');
    var uploadForm = document.getElementById('leanCanvasTemplateForm');
    var fileInput = document.getElementById('leanCanvasTemplateFile');
    var submitBtn = document.getElementById('leanCanvasTemplateSubmit');
    var chooseBtn = document.getElementById('leanCanvasChooseBtn');
    var fileStatus = document.getElementById('leanCanvasFileStatus');

    var previewUrl = card.getAttribute('data-preview-url');
    var deleteUrl = card.getAttribute('data-delete-url');

    var lastFocused = null;

    // ─── Toast ────────────────────────────────────────────────
    function showToast(type, message) {
        var toast = document.createElement('div');
        toast.className = 'org-admin-toast account-admin-toast';
        var bg = type === 'error' ? '#fee2e2' : (type === 'info' ? '#dbeafe' : '#dcfce7');
        var color = type === 'error' ? '#991b1b' : (type === 'info' ? '#0c4a6e' : '#166534');
        toast.style.background = bg;
        toast.style.color = color;
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

    // ─── Choose File button → hidden input ────────────────────
    if (chooseBtn && fileInput) {
        chooseBtn.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            var name = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0].name : '';
            if (fileStatus) {
                fileStatus.textContent = name !== '' ? name : 'No file chosen';
            }
            if (submitBtn) {
                submitBtn.disabled = name === '';
            }
        });
    }

    // ─── Upload / replace ─────────────────────────────────────
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                showToast('error', 'Please choose a PDF or Word file to upload.');
                return;
            }

            var formData = new FormData(uploadForm);

            if (submitBtn) submitBtn.disabled = true;
            var originalLabel = submitBtn ? submitBtn.textContent : '';
            if (submitBtn) submitBtn.textContent = 'Uploading\u2026';

            fetch(uploadForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function () {
                    // The controller redirects on success/error with a flash toast,
                    // so a simple reload surfaces the server-side toast and refreshed state.
                    window.location.reload();
                })
                .catch(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalLabel;
                    }
                    showToast('error', 'Something went wrong while uploading.');
                });
        });
    }

    // ─── Delete ───────────────────────────────────────────────
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (!window.AdminDeleteConfirm || typeof window.AdminDeleteConfirm.ask !== 'function') {
                // Fallback: native confirm if the shared modal isn't available.
                if (!window.confirm(deleteBtn.getAttribute('data-confirm-title') || 'Delete template?')) {
                    return;
                }
                submitDelete();
                return;
            }

            window.AdminDeleteConfirm.ask({
                title: deleteBtn.getAttribute('data-confirm-title') || 'Delete Lean Canvas template?',
                message: deleteBtn.getAttribute('data-confirm-message') || 'This action cannot be undone.',
                confirmLabel: 'Delete'
            }).then(function (confirmed) {
                if (confirmed) {
                    submitDelete();
                }
            });
        });
    }

    function submitDelete() {
        // Build a CSRF-bearing POST form and submit. The controller redirects back.
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteUrl;
        form.style.display = 'none';

        // Reuse the CSRF token already present in the upload form's hidden field.
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

    // ─── Preview modal (matches apply-form guidelines modal style) ─
    function openModal() {
        if (!modal) return;
        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lc-modal-open');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lc-modal-open');
        if (modalBody) modalBody.innerHTML = '<div class="lc-modal-loading">Loading template&hellip;</div>';
        if (modalFoot) modalFoot.hidden = true;
        if (lastFocused && typeof lastFocused.focus === 'function') {
            window.setTimeout(function () { lastFocused.focus(); }, 30);
        }
    }

    function renderPreview(data) {
        if (!modalBody) return;

        if (data.isPdf) {
            modalBody.innerHTML = '<iframe src="' + data.url + '" title="Lean Canvas template" class="lc-modal-iframe"></iframe>';
        } else {
            var isLocal = data.url.indexOf('localhost') !== -1 || data.url.indexOf('127.0.0.1') !== -1 || data.url.indexOf('::1') !== -1;
            if (isLocal && typeof window.mammoth !== 'undefined') {
                modalBody.innerHTML = '<div class="lc-modal-loading">Rendering document&hellip;</div>';
                fetch(data.url)
                    .then(function (res) { return res.arrayBuffer(); })
                    .then(function (buffer) { return window.mammoth.convertToHtml({ arrayBuffer: buffer }); })
                    .then(function (result) {
                        modalBody.innerHTML = '<div class="lc-mammoth-content">' + result.value + '</div>';
                    })
                    .catch(function () {
                        modalBody.innerHTML =
                            '<div class="lc-modal-loading" style="gap:.75rem">' +
                            '<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#03558C" stroke-width="1.6" aria-hidden="true">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>' +
                            '</svg>' +
                            '<strong style="color:#1e293b;font-size:.92rem">Could not render preview</strong>' +
                            '<span>Use the Download button below to view the file.</span>' +
                            '</div>';
                    });
            } else {
                var viewerUrl = 'https://docs.google.com/viewer?url=' + encodeURIComponent(data.url) + '&embedded=true';
                modalBody.innerHTML = '<iframe src="' + viewerUrl + '" title="Lean Canvas template" class="lc-modal-iframe"></iframe>';
            }
        }

        if (modalFoot && modalDownload) {
            modalDownload.setAttribute('href', data.url);
            modalDownload.setAttribute('download', data.name || 'lean-canvas-template');
            modalFoot.hidden = false;
        }
    }

    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            openModal();

            fetch(previewUrl, {
                method: 'GET',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Preview request failed.');
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (!data || data.ok === false) {
                        if (modalBody) {
                            modalBody.innerHTML = '<div class="lc-modal-error">' +
                                (data && data.message ? data.message : 'Could not load the template.') +
                                '</div>';
                        }
                        return;
                    }
                    renderPreview(data);
                })
                .catch(function () {
                    if (modalBody) {
                        modalBody.innerHTML = '<div class="lc-modal-error">Could not load the template.</div>';
                    }
                });
        });
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-lean-canvas-preview-close]')) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();
