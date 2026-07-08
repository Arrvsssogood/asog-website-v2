(function() {
    function formatFileSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(1).replace(/\.0$/, '') + ' MB';
        }

        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1).replace(/\.0$/, '') + ' KB';
        }

        return bytes + ' bytes';
    }

    function isWithinMaxSize(file, maxBytes) {
        if (!file || !maxBytes) {
            return true;
        }

        return file.size <= maxBytes;
    }

    function setHelpState(helpEl, defaultText, message, isError) {
        if (!helpEl) {
            return;
        }

        helpEl.textContent = message || defaultText;
        helpEl.classList.toggle('is-error', !!isError);
    }

    function isSquareImage(file) {
        return new Promise(function(resolve) {
            var url = URL.createObjectURL(file);
            var img = new Image();

            img.onload = function() {
                URL.revokeObjectURL(url);
                resolve(img.naturalWidth === img.naturalHeight);
            };

            img.onerror = function() {
                URL.revokeObjectURL(url);
                resolve(false);
            };

            img.src = url;
        });
    }

    /* ── Logo Upload Zone ── */
    var zone = document.getElementById('uploadZone');
    var input = document.getElementById('logoInput');
    var preview = document.getElementById('uploadPreview');
    var label = document.getElementById('uploadLabel');
    var logoHelp = document.getElementById('logoUploadHelp');
    var logoDefaultHelp = logoHelp ? logoHelp.textContent : '';
    var logoMaxBytes = parseInt(input && input.dataset.maxBytes ? input.dataset.maxBytes : '0', 10) || 0;
    var logoMaxLabel = input && input.dataset.maxLabel ? input.dataset.maxLabel : formatFileSize(logoMaxBytes);

    if (preview.querySelector('img')) label.style.display = 'none';

    zone.addEventListener('click', function(e) {
        if (e.target === input) return;
        input.click();
    });

    input.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        if (!isWithinMaxSize(file, logoMaxBytes)) {
            this.value = '';
            setHelpState(logoHelp, logoDefaultHelp, 'Selected file is ' + formatFileSize(file.size) + '. Maximum is ' + logoMaxLabel + '.', true);
            return;
        }

        setHelpState(logoHelp, logoDefaultHelp, logoDefaultHelp, false);
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="">';
            label.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.style.borderColor = '#03558C';
        zone.style.background = '#fafcff';
    });
    zone.addEventListener('dragleave', function() {
        zone.style.borderColor = '';
        zone.style.background = '';
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.style.borderColor = '';
        zone.style.background = '';
        var files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            if (!isWithinMaxSize(files[0], logoMaxBytes)) {
                setHelpState(logoHelp, logoDefaultHelp, 'Selected file is ' + formatFileSize(files[0].size) + '. Maximum is ' + logoMaxLabel + '.', true);
                return;
            }

            input.files = files;
            input.dispatchEvent(new Event('change'));
        }
    });

    /* ── White Logo Upload Zone ── */
    var zoneW = document.getElementById('uploadZoneWhite');
    var inputW = document.getElementById('logoWhiteInput');
    var previewW = document.getElementById('uploadPreviewWhite');
    var labelW = document.getElementById('uploadLabelWhite');
    var logoWhiteHelp = document.getElementById('logoWhiteUploadHelp');
    var logoWhiteDefaultHelp = logoWhiteHelp ? logoWhiteHelp.textContent : '';
    var logoWhiteMaxBytes = parseInt(inputW && inputW.dataset.maxBytes ? inputW.dataset.maxBytes : '0', 10) || 0;
    var logoWhiteMaxLabel = inputW && inputW.dataset.maxLabel ? inputW.dataset.maxLabel : formatFileSize(logoWhiteMaxBytes);

    if (previewW.querySelector('img')) labelW.style.display = 'none';

    zoneW.addEventListener('click', function(e) {
        if (e.target === inputW) return;
        inputW.click();
    });

    inputW.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        if (!isWithinMaxSize(file, logoWhiteMaxBytes)) {
            this.value = '';
            setHelpState(logoWhiteHelp, logoWhiteDefaultHelp, 'Selected file is ' + formatFileSize(file.size) + '. Maximum is ' + logoWhiteMaxLabel + '.', true);
            return;
        }

        setHelpState(logoWhiteHelp, logoWhiteDefaultHelp, logoWhiteDefaultHelp, false);
        var reader = new FileReader();
        reader.onload = function(e) {
            previewW.innerHTML = '<img class="upload-preview-white-logo" src="' + e.target.result + '" alt="">';
            labelW.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    zoneW.addEventListener('dragover', function(e) {
        e.preventDefault();
        zoneW.style.borderColor = '#7fb3d1';
        zoneW.style.background = '#044066';
    });
    zoneW.addEventListener('dragleave', function() {
        zoneW.style.borderColor = '';
        zoneW.style.background = '';
    });
    zoneW.addEventListener('drop', function(e) {
        e.preventDefault();
        zoneW.style.borderColor = '';
        zoneW.style.background = '';
        var files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            if (!isWithinMaxSize(files[0], logoWhiteMaxBytes)) {
                setHelpState(logoWhiteHelp, logoWhiteDefaultHelp, 'Selected file is ' + formatFileSize(files[0].size) + '. Maximum is ' + logoWhiteMaxLabel + '.', true);
                return;
            }

            inputW.files = files;
            inputW.dispatchEvent(new Event('change'));
        }
    });

    /* ── Founder Photo Upload Zone ── */
    /* ── Founders Repeater ── */
    var tmRows = document.getElementById('tmRows');
    var tmAdd = document.getElementById('tmAdd');
    var tmHelp = document.getElementById('tmUploadHelp');
    var tmDefaultHelp = tmHelp ? tmHelp.textContent : '';

    function bindTmPhotoInput(fileInput) {
        if (!fileInput || fileInput.dataset.bound === '1') return;
        fileInput.dataset.bound = '1';

        var zone = fileInput.closest('.tm-photo-zone');
        if (!zone) return;

        function applySelectedFile(file) {
            if (!file || !file.type || file.type.indexOf('image/') !== 0) return;
            var maxBytes = parseInt(fileInput.dataset.maxBytes || '0', 10) || 0;
            if (!isWithinMaxSize(file, maxBytes)) {
                fileInput.value = '';
                setHelpState(tmHelp, tmDefaultHelp, 'Founder photo is ' + formatFileSize(file.size) + '. Maximum is ' + formatFileSize(maxBytes) + '.', true);
                return;
            }

            var aspect = (fileInput.dataset.aspect || '').toLowerCase();
            if (aspect === 'square') {
                isSquareImage(file).then(function(isSquare) {
                    if (!isSquare) {
                        fileInput.value = '';
                        setHelpState(tmHelp, tmDefaultHelp, 'Founder photos must be square (1:1). Please crop the image before uploading.', true);
                        return;
                    }

                    setHelpState(tmHelp, tmDefaultHelp, tmDefaultHelp, false);
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                    fileInput.dispatchEvent(new Event('change'));
                });
                return;
            }

            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            fileInput.dispatchEvent(new Event('change'));
        }

        zone.addEventListener('click', function(e) {
            if (e.target === fileInput) return;
            /* Prevent label default activation from opening a second picker dialog. */
            e.preventDefault();
            fileInput.click();
        });

        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.classList.add('is-dragover');
        });

        zone.addEventListener('dragleave', function() {
            zone.classList.remove('is-dragover');
        });

        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('is-dragover');
            var files = e.dataTransfer && e.dataTransfer.files;
            if (!files || files.length === 0) return;
            applySelectedFile(files[0]);
        });

        fileInput.addEventListener('change', function() {
            var file = fileInput.files[0];
            if (!file) return;
            var maxBytes = parseInt(fileInput.dataset.maxBytes || '0', 10) || 0;
            if (!isWithinMaxSize(file, maxBytes)) {
                fileInput.value = '';
                setHelpState(tmHelp, tmDefaultHelp, 'Founder photo is ' + formatFileSize(file.size) + '. Maximum is ' + formatFileSize(maxBytes) + '.', true);
                return;
            }

            var aspect = (fileInput.dataset.aspect || '').toLowerCase();
            if (aspect === 'square') {
                isSquareImage(file).then(function(isSquare) {
                    if (!isSquare) {
                        fileInput.value = '';
                        setHelpState(tmHelp, tmDefaultHelp, 'Founder photos must be square (1:1). Please crop the image before uploading.', true);
                        return;
                    }

                    setHelpState(tmHelp, tmDefaultHelp, tmDefaultHelp, false);
                    renderPreview(file);
                });
                return;
            }

            renderPreview(file);
        });

        function renderPreview(file) {
            
            var reader = new FileReader();
            reader.onload = function(ev) {
                // Update preview without destroying the file input
                var existingImg = zone.querySelector('.tm-photo-preview');
                var placeholder = zone.querySelector('.tm-photo-placeholder');
                if (existingImg) {
                    existingImg.src = ev.target.result;
                } else {
                    if (placeholder) placeholder.style.display = 'none';
                    var img = document.createElement('img');
                    img.className = 'tm-photo-preview';
                    img.src = ev.target.result;
                    img.alt = '';
                    zone.appendChild(img);
                }
                // Clear the existing photo path since we have a new upload
                var hidden = zone.querySelector('input[name="tm_photo_existing[]"]');
                if (hidden) {
                    hidden.value = '';
                    hidden.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };
            reader.readAsDataURL(file);
        }
    }

    tmRows.querySelectorAll('.tm-photo-input').forEach(bindTmPhotoInput);

    tmAdd.addEventListener('click', function() {
        var row = document.createElement('div');
        row.className = 'tm-row';
        row.innerHTML =
            '<label class="tm-photo-zone">' +
                '<input type="hidden" name="tm_photo_existing[]" value="">' +
                '<input type="file" name="tm_photo[]" class="tm-photo-input" accept="image/*" data-aspect="square">' +
                '<span class="tm-photo-placeholder">Founder<br>Photo</span>' +
            '</label>' +
            '<input type="text" name="tm_name[]" placeholder="Name">' +
            '<input type="text" name="tm_role[]" placeholder="Founder title (e.g. CEO, CTO)">' +
            '<button type="button" class="tm-remove" title="Remove">×</button>';
        tmRows.appendChild(row);
        bindTmPhotoInput(row.querySelector('.tm-photo-input'));
        row.querySelector('input[name="tm_name[]"]').focus();
    });

    tmRows.addEventListener('click', function(e) {
        if (e.target.classList.contains('tm-remove')) {
            var row = e.target.closest('.tm-row');
            if (tmRows.querySelectorAll('.tm-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input[type="text"]').forEach(function(inp) {
                    inp.value = '';
                });
                var zone = row.querySelector('.tm-photo-zone');
                if (zone) {
                    zone.innerHTML =
                        '<input type="hidden" name="tm_photo_existing[]" value="">' +
                        '<input type="file" name="tm_photo[]" class="tm-photo-input" accept="image/*" data-aspect="square">' +
                        '<span class="tm-photo-placeholder">Founder<br>Photo</span>';
                    bindTmPhotoInput(zone.querySelector('.tm-photo-input'));
                }
            }
        }
    });

    /* ── Contacts Repeater ── */
    var contactRows = document.getElementById('contactRows');
    var contactAdd = document.getElementById('contactAdd');

    if (contactRows && contactAdd) {
        function buildContactRow() {
            var row = document.createElement('div');
            row.className = 'contact-row';
            row.innerHTML =
                '<input type="text" name="contact_person[]" placeholder="Contact person">' +
                '<input type="text" name="contact_number[]" placeholder="Number">' +
                '<input type="text" name="contact_email[]" placeholder="Email">' +
                '<button type="button" class="contact-remove" title="Remove">×</button>';
            return row;
        }

        contactAdd.addEventListener('click', function() {
            var row = buildContactRow();
            contactRows.appendChild(row);
            var firstInput = row.querySelector('input[name="contact_person[]"]');
            if (firstInput) {
                firstInput.focus();
            }
        });

        contactRows.addEventListener('click', function(e) {
            if (!e.target.classList.contains('contact-remove')) {
                return;
            }

            var row = e.target.closest('.contact-row');
            if (!row) {
                return;
            }

            if (contactRows.querySelectorAll('.contact-row').length > 1) {
                row.remove();
                return;
            }

            row.querySelectorAll('input').forEach(function(input) {
                input.value = '';
            });
        });
    }
})();
