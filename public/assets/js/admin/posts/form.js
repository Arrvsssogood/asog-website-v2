(function () {
    function slugify(value) {
        return String(value || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /* ── Cover image upload ──────────────── */
    var zone = document.getElementById('uploadZone');
    var input = document.getElementById('imageInput');
    var preview = document.getElementById('uploadPreview');
    var label = document.getElementById('uploadLabel');
    var titleInput = document.getElementById('title');
    var slugInput = document.getElementById('slug');

    if (zone && input) {
        if (preview && preview.querySelector('img')) {
            label.style.display = 'none';
        }

        zone.addEventListener('click', function (e) {
            if (e.target === input) return;
            input.click();
        });

        input.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="">';
                label.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.style.borderColor = '#03558C';
            zone.style.background = '#fafcff';
        });
        zone.addEventListener('dragleave', function () {
            zone.style.borderColor = '';
            zone.style.background = '';
        });
        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.style.borderColor = '';
            zone.style.background = '';
            var files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    if (titleInput && slugInput) {
        var slugManuallyEdited = slugInput.value.trim() !== '';

        titleInput.addEventListener('input', function () {
            if (slugManuallyEdited && slugInput.value.trim() !== '') return;
            slugInput.value = slugify(titleInput.value);
        });

        slugInput.addEventListener('input', function () {
            slugManuallyEdited = slugInput.value.trim() !== '';
        });

        if (slugInput.value.trim() === '' && titleInput.value.trim() !== '') {
            slugInput.value = slugify(titleInput.value);
        }
    }

    /* ── Form Dirty Checking ──────────────── */

    var postForm = document.getElementById('postForm');
    if (postForm && window.DirtyCheck) {
        var saveButtons = Array.prototype.slice.call(
            postForm.querySelectorAll('.form-actions button.btn-o[type="submit"]')
        );

        var tracker = window.DirtyCheck.watch(postForm, { buttons: saveButtons });
        tracker.baseline();
    }

    /* ── Preview functionality ───────────── */
    var editTab = document.getElementById('editTab');
    var previewTab = document.getElementById('previewTab');
    var editPanel = document.getElementById('editPanel');
    var previewPanel = document.getElementById('previewPanel');
    var previewIframe = document.getElementById('previewIframe');
    var previewError = document.getElementById('previewError');
    var form = document.getElementById('postForm');
    var previewLoaded = false;

    if (!previewTab || !form) return;

    var CSRF_FIELD_NAME = 'csrf_test_name';

    function showError(msg) {
        if (previewError) {
            previewError.textContent = msg;
            previewError.style.display = 'block';
        }
    }

    function hideError() {
        if (previewError) {
            previewError.style.display = 'none';
        }
    }

    function updateCsrf(hash) {
        if (!hash) return;
        var input = form.querySelector('input[name="' + CSRF_FIELD_NAME + '"]');
        if (input) input.value = hash;
        var meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
        if (meta) meta.setAttribute('content', hash);
    }

    function syncEditorContent() {
        var editorEl = form.querySelector('.quill-editor');
        var hiddenInput = form.querySelector('input.quill-content');

        if (!editorEl || !hiddenInput) return;

        var editorRoot = editorEl.querySelector('.ql-editor');
        hiddenInput.value = editorRoot ? editorRoot.innerHTML : editorEl.innerHTML;
    }

    function loadPreviewFromForm() {
        if (!previewIframe || !previewIframe.dataset.previewUrl) return;

        syncEditorContent();
        form.dispatchEvent(new CustomEvent('quill:sync'));

        var formData = new FormData(form);
        formData.delete('_method');
        formData.set('action', 'draft');

        fetch(previewIframe.dataset.previewUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Preview failed (' + response.status + ')');
            }

            var freshToken = response.headers.get('X-CSRF-TOKEN');
            if (freshToken) updateCsrf(freshToken);

            return response.text();
        })
        .then(function (html) {
            hideError();
            previewIframe.srcdoc = html;
            previewLoaded = true;
        })
        .catch(function (err) {
            showError('Preview couldn\'t load your latest changes — showing the last saved version.');
            console.warn(err);
            previewIframe.src = previewIframe.dataset.previewUrl + '?t=' + Date.now();
            previewLoaded = true;
        });
    }

    function activateTab(tabToShow) {
        if (!editTab || !previewTab || !editPanel || !previewPanel) return;

        var showingPreview = tabToShow === previewTab;
        editTab.classList.toggle('active', !showingPreview);
        previewTab.classList.toggle('active', showingPreview);
        editTab.setAttribute('aria-selected', String(!showingPreview));
        previewTab.setAttribute('aria-selected', String(showingPreview));
        editPanel.hidden = showingPreview;
        previewPanel.hidden = !showingPreview;
    }

    editTab.addEventListener('click', function () {
        activateTab(editTab);
    });

    previewTab.addEventListener('click', function () {
        activateTab(previewTab);
        hideError();
        loadPreviewFromForm();
    });
})();
