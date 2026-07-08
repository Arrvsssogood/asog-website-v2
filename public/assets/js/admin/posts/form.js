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
})();
