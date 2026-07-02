/* ═══════════════════════════════════════════════════════════════════
   ASOG-TBI — Incubatee Application Form
   · Form persistence via sessionStorage (survives refresh)
   · Real-time inline validation
   · Async duplicate-email check
   · Preview modal before submission
   ═══════════════════════════════════════════════════════════════════ */
(function () {

  /* ─────────────────────────────────────────────────────────────────
     1. SESSION STORAGE PERSISTENCE
        Saves text field values as the user types.
        Restores them on page load ONLY when the field is still empty
        (so CI4 old() values from validation-fail redirects take priority).
  ───────────────────────────────────────────────────────────────────── */
  var form = document.getElementById('applyForm');
  var LEGACY_STORAGE_KEY = 'asog_apply_form_v1';
  var STORAGE_KEY = (form && form.dataset.storageKey) || 'asog_apply_form_public_v2';
  var isRevalidationMode = !!(form && form.dataset.formMode === 'revalidation');
  var shouldSkipDuplicateEmail = !!(form && form.dataset.skipDuplicateEmail === '1');
  var hasExistingLeanCanvas = !!(form && form.dataset.hasExistingLeanCanvas === '1');
  var existingTeamCvCount = parseInt((form && form.dataset.existingTeamCvCount) || '0', 10) || 0;
  var recaptchaEnabled = !!(form && form.dataset.recaptchaEnabled === '1');
  var recaptchaSiteKey = (form && form.dataset.recaptchaSiteKey) || '';
  var recaptchaAction = (form && form.dataset.recaptchaAction) || (isRevalidationMode ? 'application_revalidate' : 'application_submit');
  var recaptchaTokenField = form ? form.querySelector('[data-recaptcha-token]') : null;
  var persistIds  = [
    'applicantName', 'applicantEmail', 'contactNumber',
    'startupName', 'startupDescription',
    'mainRisk', 'shortTermGoals',
    'videoPresentationLink'
  ];

  try {
    sessionStorage.removeItem(LEGACY_STORAGE_KEY);
  } catch (e) {}

  // Restore saved values (skip if field already has a server-rendered value)
  try {
    var saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
    if (saved) {
      persistIds.forEach(function (id) {
        var el = document.getElementById(id);
        if (el && !el.value && saved[id]) {
          el.value = saved[id];
        }
      });
    }
  } catch (e) {}

  // Auto-save on every input event
  function saveToStorage() {
    try {
      var data = {};
      persistIds.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) data[id] = el.value;
      });
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    } catch (e) {}
  }

  function sanitizeContactNumber(value) {
    return String(value || '').replace(/\D+/g, '').slice(0, 11);
  }

  function syncContactNumber(el) {
    if (!el) return;
    var nextValue = sanitizeContactNumber(el.value);
    if (el.value !== nextValue) {
      el.value = nextValue;
    }
  }

  persistIds.forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', saveToStorage);
  });

  var contactField = document.getElementById('contactNumber');
  if (contactField) {
    syncContactNumber(contactField);
    contactField.addEventListener('input', function () {
      syncContactNumber(contactField);
      validate(contactField);
      saveToStorage();
    });
    contactField.addEventListener('blur', function () {
      syncContactNumber(contactField);
      validate(contactField);
      saveToStorage();
    });
    contactField.addEventListener('paste', function () {
      setTimeout(function () {
        syncContactNumber(contactField);
        saveToStorage();
      }, 0);
    });
  }


  /* ─────────────────────────────────────────────────────────────────
     2. REAL-TIME INLINE VALIDATION
  ───────────────────────────────────────────────────────────────────── */
  var rules = {
    'required': function (v) { return v.trim().length > 0 || 'This field is required.'; },
    'min:2':    function (v) { return v.trim().length >= 2  || 'Must be at least 2 characters.'; },
    'min:10':   function (v) { return v.trim().length >= 10 || 'Must be at least 10 characters.'; },
    'email':    function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || 'Please enter a valid email.'; },
    'phone':    function (v) { return /^09[0-9]{9}$/.test(v) || 'Please enter a valid contact number.'; },
    'url':      function (v) { return /^https?:\/\/.+\..+/.test(v)         || 'Enter a valid URL (https://...).'; },
    'name':     function (v) { return /^[A-Za-z\u00C0-\u00FF\s,\.]+$/.test(v) || 'Use format: Last Name, First Name MI'; },
  };
  var DUPLICATE_EMAIL_MESSAGE = 'This email has already been used in a previous application.';
  var duplicateToastTimer = null;

  function showHeadsUp(message) {
    var toast = document.getElementById('applyFormHeadsUp');

    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'applyFormHeadsUp';
      toast.setAttribute('role', 'alert');
      toast.setAttribute('aria-live', 'assertive');
      toast.style.position = 'fixed';
      toast.style.top = '24px';
      toast.style.right = '24px';
      toast.style.maxWidth = '360px';
      toast.style.width = 'calc(100vw - 32px)';
      toast.style.padding = '14px 16px';
      toast.style.borderRadius = '14px';
      toast.style.background = '#102033';
      toast.style.color = '#ffffff';
      toast.style.boxShadow = '0 16px 45px rgba(16, 32, 51, 0.22)';
      toast.style.border = '1px solid rgba(240, 165, 18, 0.32)';
      toast.style.zIndex = '10001';
      toast.style.opacity = '0';
      toast.style.pointerEvents = 'none';
      toast.style.transform = 'translateY(-8px)';
      toast.style.transition = 'opacity .18s ease, transform .18s ease';
      document.body.appendChild(toast);
    }

    toast.innerHTML =
      '<div style="font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#f0a512;margin-bottom:4px;">Application Notice</div>' +
      '<div style="font-size:13px;line-height:1.55;color:#ffffff;">' + escHtml(message) + '</div>';

    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    clearTimeout(duplicateToastTimer);
    duplicateToastTimer = setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
    }, 3400);
  }

  function validate(el) {
    var checks = (el.dataset.v || '').split('|').filter(Boolean);
    var val    = el.type === 'checkbox' ? (el.checked ? el.value : '') : el.value;
    var msg    = el.closest('div') && el.closest('div').querySelector('.v-msg');
    if (!msg) return true;

    if (!val.trim() && !checks.includes('required')) {
      msg.classList.add('hidden'); msg.textContent = '';
      el.classList.remove('!text-red-600');
      return true;
    }

    for (var i = 0; i < checks.length; i++) {
      var fn     = rules[checks[i]];
      if (!fn) continue;
      var result = fn(val);
      if (result !== true) {
        msg.textContent = checks[i] === 'required' && el.dataset.requiredMessage
          ? el.dataset.requiredMessage
          : result;
        msg.classList.remove('hidden');
        el.classList.add('!text-red-600');
        return false;
      }
    }

    msg.classList.add('hidden'); msg.textContent = '';
    el.classList.remove('!text-red-600');
    return true;
  }

  function setDuplicateEmailState(isDuplicate, showToast) {
    if (!emailField) return;

    if (shouldSkipDuplicateEmail) {
      emailField.dataset.dupEmail = '0';
      return;
    }

    var msg = emailField.closest('div') && emailField.closest('div').querySelector('.v-msg');
    emailField.dataset.dupEmail = isDuplicate ? '1' : '0';

    if (isDuplicate) {
      if (msg) {
        msg.textContent = DUPLICATE_EMAIL_MESSAGE;
        msg.classList.remove('hidden');
      }
      emailField.classList.add('!text-red-600');

      if (showToast) {
        showHeadsUp(DUPLICATE_EMAIL_MESSAGE);
      }
      return;
    }

    if (msg && msg.textContent.trim() === DUPLICATE_EMAIL_MESSAGE) {
      msg.textContent = '';
      msg.classList.add('hidden');
    }

    emailField.classList.remove('!text-red-600');
  }

  function validateAll() {
    var ok = true;
    document.querySelectorAll('.v-field[data-v]').forEach(function (el) {
      if (!validate(el)) ok = false;
    });

    // Check duplicate-email flag
    var ef = document.getElementById('applicantEmail');
    if (!shouldSkipDuplicateEmail && ef && ef.dataset.dupEmail === '1') {
      ok = false;
      var emsg = ef.closest('div') && ef.closest('div').querySelector('.v-msg');
      if (emsg && emsg.classList.contains('hidden')) {
        emsg.textContent = DUPLICATE_EMAIL_MESSAGE;
        emsg.classList.remove('hidden');
        ef.classList.add('!text-red-600');
      }
    }

    // Lean Canvas is required — must have a file selected
    var lcInput = document.getElementById('leanCanvas');
    var lcErr   = document.getElementById('leanCanvasErr');
    if (lcInput && lcErr) {
      if ((!lcInput.files || lcInput.files.length === 0) && !hasExistingLeanCanvas) {
        lcErr.textContent = 'Please upload your completed Lean Canvas (.docx or PDF).';
        lcErr.classList.remove('hidden');
        ok = false;
      } else {
        lcErr.classList.add('hidden');
        lcErr.textContent = '';
      }
    }

    if (!ok) {
      var first = document.querySelector('.v-msg:not(.hidden)');
      if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return ok;
  }

  // Attach blur / input listeners to all validated fields
  document.querySelectorAll('.v-field').forEach(function (el) {
    el.addEventListener('blur', function () { validate(el); });
    el.addEventListener('input', function () {
      var msg = el.closest('div') && el.closest('div').querySelector('.v-msg');
      if (msg && !msg.classList.contains('hidden')) validate(el);
    });
  });

  // Show server-side errors (already in DOM as .v-msg text)
  document.querySelectorAll('.v-msg').forEach(function (msg) {
    if (msg.textContent.trim()) {
      if (shouldSkipDuplicateEmail && msg.dataset.for === 'applicantEmail' && msg.textContent.trim() === DUPLICATE_EMAIL_MESSAGE) {
        msg.textContent = '';
        msg.classList.add('hidden');
        return;
      }

      msg.classList.remove('hidden');
      var f = msg.closest('div') && msg.closest('div').querySelector('.v-field');
      if (f) f.classList.add('!text-red-600');

      if (!shouldSkipDuplicateEmail && msg.dataset.for === 'applicantEmail' && msg.textContent.trim() === DUPLICATE_EMAIL_MESSAGE) {
        setDuplicateEmailState(true, false);
        showHeadsUp(DUPLICATE_EMAIL_MESSAGE);
      }
    }
  });


  /* ─────────────────────────────────────────────────────────────────
     3. ASYNC DUPLICATE-EMAIL CHECK
  ───────────────────────────────────────────────────────────────────── */
  var checkEmailUrl = (form && form.dataset.checkUrl) || '';
  var emailTimer    = null;
  var emailField    = document.getElementById('applicantEmail');
  var allowNativeSubmit = false;
  var isSubmittingFinal = false;

  function runDuplicateEmailCheck(showToast) {
    if (shouldSkipDuplicateEmail) {
      setDuplicateEmailState(false, false);
      return Promise.resolve(false);
    }

    if (!emailField || !checkEmailUrl) {
      return Promise.resolve(false);
    }

    if (!validate(emailField)) {
      return Promise.resolve(false);
    }

    var val = emailField.value.trim();
    if (!val) {
      setDuplicateEmailState(false, false);
      return Promise.resolve(false);
    }

    return fetch(checkEmailUrl + '?email=' + encodeURIComponent(val))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var exists = !!(d && d.exists);
        setDuplicateEmailState(exists, showToast && exists);
        return exists;
      })
      .catch(function () {
        return false;
      });
  }

  if (emailField && checkEmailUrl && !shouldSkipDuplicateEmail) {
    var checkDupe = function () {
      runDuplicateEmailCheck(true);
    };

    emailField.addEventListener('blur', function () {
      clearTimeout(emailTimer);
      emailTimer = setTimeout(checkDupe, 150);
    });
    emailField.addEventListener('input', function () {
      setDuplicateEmailState(false, false);
    });
  }


  /* ─────────────────────────────────────────────────────────────────
     3b. FILE UPLOAD PREVIEWS
         Show file name + size below each file input, with a remove (✕)
         button per file.  Uses a DataTransfer object to keep the
         FileList in sync when individual files are removed.
  ───────────────────────────────────────────────────────────────────── */
  function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
  }

  // ── Team CV (multi-file) ──────────────────
  var cvInput  = document.getElementById('teamCv');
  var cvChooser = document.getElementById('teamCvChooser');
  var cvButton = document.getElementById('teamCvButton');
  var cvList   = document.getElementById('teamCvList');
  var cvStatus = document.getElementById('teamCvStatus');
  var cvNotice = document.getElementById('teamCvNotice');
  var maxCvFiles = 10;
  var selectedCvFiles = [];

  function syncCvInput() {
    if (!cvInput) return;
    var dt = new DataTransfer();
    selectedCvFiles.slice(0, maxCvFiles).forEach(function (file) {
      dt.items.add(file);
    });
    cvInput.files = dt.files;
  }

  function cvFileKey(file) {
    return [file.name, file.size, file.lastModified].join('|');
  }

  function showCvNotice(message) {
    if (!cvNotice) return;
    if (!message) {
      cvNotice.classList.add('hidden');
      cvNotice.textContent = '';
      return;
    }
    cvNotice.textContent = message;
    cvNotice.classList.remove('hidden');
  }

  function updateCvStatus() {
    if (!cvInput || !cvStatus) return;
    var count = selectedCvFiles.length;
    if (count === 0) {
      cvStatus.textContent = isRevalidationMode && existingTeamCvCount > 0
        ? 'Keeping existing CV file' + (existingTeamCvCount === 1 ? '' : 's')
        : 'No file chosen';
    } else {
      cvStatus.textContent = count === 1 ? '1 file selected' : count + ' files selected';
    }
    if (cvChooser) {
      cvChooser.style.display = count >= maxCvFiles ? 'none' : '';
    }
  }

  function renderCvList() {
    if (!cvInput || !cvList) return;
    var files = selectedCvFiles;
    updateCvStatus();
    cvList.innerHTML = '';
    if (files.length === 0) { cvList.classList.add('hidden'); return; }
    cvList.classList.remove('hidden');

    Array.from(files).forEach(function (f, i) {
      var li = document.createElement('li');
      li.className = 'flex items-center gap-2 text-[.75rem] text-dark/70 bg-off/60 border border-navy/8 rounded px-3 py-1.5';

      // PDF icon
      li.innerHTML =
        '<svg class="w-3.5 h-3.5 flex-shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20">' +
          '<path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H4zm7 1.5L16.5 9H12a1 1 0 01-1-1V3.5z"/>' +
        '</svg>' +
        '<span class="flex-1 truncate">' + escHtml(f.name) + '</span>' +
        '<span class="text-[.65rem] text-navy/40 flex-shrink-0">' + formatBytes(f.size) + '</span>';

      // Remove button
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ml-1 text-dark/30 hover:text-red-500 transition-colors flex-shrink-0';
      btn.title = 'Remove';
      btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
      btn.addEventListener('click', function () { removeCvFile(i); });
      li.appendChild(btn);

      cvList.appendChild(li);
    });
  }

  function removeCvFile(index) {
    selectedCvFiles.splice(index, 1);
    syncCvInput();
    showCvNotice('');
    renderCvList();
  }

  if (cvInput) {
    cvInput.addEventListener('change', function () {
      var skippedDuplicates = 0;
      var skippedLimit = 0;
      Array.from(cvInput.files).forEach(function (file) {
        var isDuplicate = selectedCvFiles.some(function (selected) {
          return cvFileKey(selected) === cvFileKey(file);
        });
        if (isDuplicate) {
          skippedDuplicates++;
        } else if (selectedCvFiles.length >= maxCvFiles) {
          skippedLimit++;
        } else {
          selectedCvFiles.push(file);
        }
      });
      if (skippedDuplicates > 0 && skippedLimit > 0) {
        showCvNotice('Duplicate files were skipped, and the 10-file limit has been reached.');
      } else if (skippedDuplicates > 0) {
        showCvNotice('Duplicate files were skipped.');
      } else if (skippedLimit > 0) {
        showCvNotice('Only up to 10 CV files can be uploaded.');
      } else {
        showCvNotice('');
      }
      syncCvInput();
      renderCvList();
    });
  }
  if (cvButton && cvInput) {
    cvButton.addEventListener('click', function () { cvInput.click(); });
  }
  updateCvStatus();

  // ── Lean Canvas (single file) ─────────────
  var lcInput   = document.getElementById('leanCanvas');
  var lcChooser = document.getElementById('leanCanvasChooser');
  var lcButton  = document.getElementById('leanCanvasButton');
  var lcPreview = document.getElementById('leanCanvasPreview');
  var lcStatus  = document.getElementById('leanCanvasStatus');

  function updateLcStatus() {
    if (!lcInput || !lcStatus) return;
    var hasFile = lcInput.files && lcInput.files.length > 0;
    lcStatus.textContent = !hasFile && hasExistingLeanCanvas
      ? 'Keeping existing Lean Canvas file'
      : 'No file chosen';
    if (lcChooser) {
      lcChooser.style.display = hasFile ? 'none' : ''; 
    }
    if (lcButton) {
      lcButton.classList.toggle('hidden', hasFile);
    }
  }

  function renderLcPreview() {
    if (!lcInput || !lcPreview) return;
    if (!lcInput.files || lcInput.files.length === 0) {
      updateLcStatus();
      lcPreview.classList.add('hidden');
      lcPreview.innerHTML = '';
      return;
    }
    var f = lcInput.files[0];
    updateLcStatus();
    var lcErr = document.getElementById('leanCanvasErr');
    if (lcErr) {
      lcErr.classList.add('hidden');
      lcErr.textContent = '';
    }
    lcPreview.classList.remove('hidden');
    lcPreview.innerHTML =
      '<div class="flex items-center gap-2 text-[.75rem] text-dark/70 bg-off/60 border border-navy/8 rounded px-3 py-1.5">' +
        '<svg class="w-3.5 h-3.5 flex-shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20">' +
          '<path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H4zm7 1.5L16.5 9H12a1 1 0 01-1-1V3.5z"/>' +
        '</svg>' +
        '<span class="flex-1 truncate">' + escHtml(f.name) + '</span>' +
        '<span class="text-[.65rem] text-navy/40 flex-shrink-0">' + formatBytes(f.size) + '</span>' +
        '<button type="button" onclick="window.asogClearLeanCanvas && window.asogClearLeanCanvas();" ' +
          'class="ml-1 text-dark/30 hover:text-red-500 transition-colors flex-shrink-0" title="Remove">' +
          '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>' +
        '</button>' +
      '</div>';
  }

  window.asogClearLeanCanvas = function () {
    if (lcInput) lcInput.value = '';
    updateLcStatus();
    if (lcPreview) {
      lcPreview.classList.add('hidden');
      lcPreview.innerHTML = '';
    }
  };

  if (lcInput) {
    lcInput.addEventListener('change', renderLcPreview);
  }
  if (lcButton && lcInput) {
    lcButton.addEventListener('click', function () { lcInput.click(); });
  }
  updateLcStatus();

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }


  /* ─────────────────────────────────────────────────────────────────
     4. PREVIEW MODAL
  ───────────────────────────────────────────────────────────────────── */
  var modal = document.getElementById('previewModal');
  var body  = document.getElementById('previewBody');
  var esc   = function (e) { if (e.key === 'Escape') closeModal(); };

  function validateBeforePreview() {
    if (!validateAll()) {
      return Promise.resolve(false);
    }

    return runDuplicateEmailCheck(true).then(function (exists) {
      if (!exists) {
        return true;
      }

      var emailMsg = emailField && emailField.closest('div') && emailField.closest('div').querySelector('.v-msg');
      if (emailMsg) {
        emailMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      if (emailField) {
        emailField.focus();
      }
      return false;
    });
  }

  function openModal() {
    // Populate text fields
    var textFields = [
      'applicantName', 'applicantEmail', 'contactNumber',
      'startupName', 'startupDescription', 'mainRisk', 'shortTermGoals'
    ];
    textFields.forEach(function (id) {
      var el = document.getElementById(id);
      var pv = document.getElementById('pv_' + id);
      if (el && pv) pv.textContent = el.value.trim() || '—';
    });

    // Video link
    var vLink  = document.getElementById('videoPresentationLink');
    var pvLink = document.getElementById('pv_videoPresentationLink');
    if (vLink && pvLink) {
      var url = vLink.value.trim();
      pvLink.textContent = url || '—';
      pvLink.href        = url || '#';
    }

    // CV files
    var cvInput = document.getElementById('teamCv');
    var pvCv    = document.getElementById('pv_teamCv');
    if (cvInput && pvCv) {
      pvCv.textContent = cvInput.files.length > 0
        ? Array.from(cvInput.files).map(function (f) { return f.name; }).join(', ')
        : (isRevalidationMode && existingTeamCvCount > 0
          ? 'Keeping existing CV file' + (existingTeamCvCount === 1 ? '' : 's')
          : 'None uploaded');
    }

    // Lean Canvas file
    var lcInput = document.getElementById('leanCanvas');
    var pvLc    = document.getElementById('pv_leanCanvas');
    if (lcInput && pvLc) {
      pvLc.textContent = lcInput.files.length > 0
        ? lcInput.files[0].name
        : (hasExistingLeanCanvas ? 'Keeping existing Lean Canvas file' : 'No file uploaded');
    }

    modal.classList.remove('opacity-0', 'pointer-events-none');
    if (body) { body.classList.remove('scale-95'); body.classList.add('scale-100'); }
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', esc);
  }

  function closeModal() {
    modal.classList.add('opacity-0', 'pointer-events-none');
    if (body) { body.classList.remove('scale-100'); body.classList.add('scale-95'); }
    document.body.style.overflow = '';
    document.removeEventListener('keydown', esc);
  }

  function setFinalSubmitting(isSubmitting) {
    isSubmittingFinal = isSubmitting;
    if (!btnConfirm) return;
    btnConfirm.disabled = isSubmitting;
    btnConfirm.style.opacity = isSubmitting ? '0.72' : '';
    btnConfirm.style.cursor = isSubmitting ? 'wait' : '';
  }

  function submitNative() {
    if (!form) return;
    allowNativeSubmit = true;
    if (window.HTMLFormElement && HTMLFormElement.prototype.submit) {
      HTMLFormElement.prototype.submit.call(form);
      return;
    }
    form.submit();
  }

  function getRecaptchaToken() {
    if (!recaptchaEnabled) {
      return Promise.resolve('');
    }

    if (!recaptchaSiteKey || !window.grecaptcha || !window.grecaptcha.enterprise) {
      return Promise.reject(new Error('recaptcha_unavailable'));
    }

    return new Promise(function (resolve, reject) {
      window.grecaptcha.enterprise.ready(function () {
        window.grecaptcha.enterprise.execute(recaptchaSiteKey, { action: recaptchaAction })
          .then(resolve)
          .catch(reject);
      });
    });
  }

  function submitWithRecaptcha() {
    if (!form || isSubmittingFinal) return;

    setFinalSubmitting(true);
    getRecaptchaToken()
      .then(function (token) {
        if (recaptchaTokenField) {
          recaptchaTokenField.value = token;
        }
        try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
        closeModal();
        submitNative();
      })
      .catch(function () {
        showHeadsUp('We could not verify your submission. Please refresh the page and try again.');
        setFinalSubmitting(false);
      });
  }

  // Review & Submit button → validate first, then show modal
  var btnPreview = document.getElementById('btnPreview');
  if (btnPreview) {
    btnPreview.addEventListener('click', function () {
      validateBeforePreview().then(function (ok) {
        if (ok) openModal();
      });
    });
  }

  // Close actions
  var btnClose   = document.getElementById('btnClosePreview');
  var btnBack    = document.getElementById('btnBackEdit');
  var backdrop   = document.getElementById('previewBackdrop');
  if (btnClose)  btnClose.addEventListener('click', closeModal);
  if (btnBack)   btnBack.addEventListener('click', closeModal);
  if (backdrop)  backdrop.addEventListener('click', closeModal);

  // Confirm & Submit → clear storage, close modal, submit form
  var btnConfirm = document.getElementById('btnConfirmSubmit');
  if (btnConfirm) {
    btnConfirm.addEventListener('click', function () {
      submitWithRecaptcha();
    });
  }

  // Block native submit (Enter key) → route through preview
  if (form) {
    form.addEventListener('submit', function (e) {
      if (allowNativeSubmit) {
        allowNativeSubmit = false;
        return;
      }

      e.preventDefault();
      validateBeforePreview().then(function (ok) {
        if (ok) openModal();
      });
    });
  }

})();
