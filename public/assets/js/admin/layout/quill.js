document.addEventListener('DOMContentLoaded', function () {
  var bootstrap = document.getElementById('adminQuillBootstrap');
  var appBase = bootstrap ? (bootstrap.getAttribute('data-base-url') || '').replace(/\/$/, '') : '';

  var BlockEmbed = Quill.import('blots/block/embed');
  var imageDialog = null;
  var imageDialogWired = false;
  var activeFigure = null;

  function normalizeCaption(value) {
    return (value || '').toString().replace(/\s+/g, ' ').trim();
  }

  function createCaptionFigure(url, caption) {
    var figure = document.createElement('figure');
    var image = document.createElement('img');
    var figcaption = document.createElement('figcaption');
    var normalizedCaption = normalizeCaption(caption);

    figure.className = 'ql-image-caption';
    figure.setAttribute('contenteditable', 'false');
    figure.setAttribute('data-url', url);
    figure.setAttribute('data-caption', normalizedCaption);

    image.setAttribute('src', url);
    image.setAttribute('alt', normalizedCaption);
    figure.appendChild(image);

    figcaption.setAttribute('data-caption', '');
    figcaption.textContent = normalizedCaption;
    figure.appendChild(figcaption);

    return figure;
  }

  function findCaption(figure) {
    var figcaption = figure ? figure.querySelector('figcaption') : null;
    return figcaption ? normalizeCaption(figcaption.textContent || '') : '';
  }

  function ensureCaptionFigure(figure) {
    var image = figure.querySelector('img');
    var figcaption = figure.querySelector('figcaption');
    var caption = normalizeCaption(figcaption ? figcaption.textContent || '' : '');

    if (! image) {
      return null;
    }

    figure.classList.add('ql-image-caption');
    figure.setAttribute('contenteditable', 'false');
    figure.setAttribute('data-url', image.getAttribute('src') || '');
    figure.setAttribute('data-caption', caption);

    if (! figcaption) {
      figcaption = document.createElement('figcaption');
      figure.appendChild(figcaption);
    }

    figcaption.setAttribute('data-caption', '');
    figcaption.textContent = caption;

    return figure;
  }

  function updateFigureCaption(figure, caption) {
    var image = figure.querySelector('img');
    var figcaption = figure.querySelector('figcaption');
    var normalizedCaption = normalizeCaption(caption);

    if (! figcaption) {
      figcaption = document.createElement('figcaption');
      figure.appendChild(figcaption);
    }

    figcaption.textContent = normalizedCaption;
    figure.setAttribute('data-caption', normalizedCaption);

    if (image) {
      image.setAttribute('alt', normalizedCaption);
    }
  }

  function getImageDialog() {
    if (! imageDialog) {
      imageDialog = {
        overlay: document.getElementById('qlImageDialogOverlay'),
        preview: document.getElementById('qlImageDialogPreview'),
        caption: document.getElementById('qlImageDialogCaption'),
        deleteButton: document.getElementById('qlImageDialogDelete'),
        cancel: document.getElementById('qlImageDialogCancel'),
        save: document.getElementById('qlImageDialogSave')
      };
    }

    return imageDialog;
  }

  function showImageDialog() {
    var dialog = getImageDialog();

    if (! dialog.overlay) {
      return null;
    }

    dialog.overlay.hidden = false;
    return dialog;
  }

  function hideImageDialog() {
    var dialog = getImageDialog();

    if (! dialog.overlay) {
      return;
    }

    dialog.overlay.hidden = true;
    activeFigure = null;
  }

  function bindImageDialog(quill, hiddenInput) {
    var dialog = getImageDialog();

    if (imageDialogWired || ! dialog.overlay) {
      return;
    }

    imageDialogWired = true;

    dialog.cancel.addEventListener('click', hideImageDialog);
    dialog.overlay.addEventListener('click', function (event) {
      if (event.target === dialog.overlay) {
        hideImageDialog();
      }
    });

    dialog.save.addEventListener('click', function () {
      if (! activeFigure) {
        hideImageDialog();
        return;
      }

      updateFigureCaption(activeFigure, dialog.caption.value);
      syncHiddenInput(quill, hiddenInput);
      hideImageDialog();
      quill.focus();
    });

    dialog.deleteButton.addEventListener('click', function () {
      if (! activeFigure) {
        hideImageDialog();
        return;
      }

      activeFigure.remove();
      syncHiddenInput(quill, hiddenInput);
      hideImageDialog();
      quill.focus();
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && ! dialog.overlay.hidden) {
        hideImageDialog();
      }
    });
  }

  function openImageDialog(figure) {
    var image = figure && figure.querySelector('img');
    var dialog = getImageDialog();

    if (! image || ! dialog.overlay) {
      return;
    }

    activeFigure = figure;
    dialog.preview.src = image.getAttribute('src') || '';
    dialog.caption.value = findCaption(figure);
    showImageDialog();

    setTimeout(function () {
      dialog.caption.focus();
      dialog.caption.select();
    }, 0);
  }

  function wrapImageNode(image) {
    var figure = createCaptionFigure(image.getAttribute('src') || '', '');
    var parent = image.parentElement;

    if (parent && parent.tagName === 'P' && parent.children.length === 1 && parent.firstElementChild === image) {
      parent.replaceWith(figure);
      return;
    }

    image.replaceWith(figure);
  }

  function upgradeExistingImages(root) {
    root.querySelectorAll('figure').forEach(function (figure) {
      ensureCaptionFigure(figure);
    });

    root.querySelectorAll('img').forEach(function (image) {
      if (image.closest('figure.ql-image-caption')) {
        return;
      }

      wrapImageNode(image);
    });
  }

  function syncHiddenInput(quill, hiddenInput) {
    if (hiddenInput) {
      hiddenInput.value = quill.root.innerHTML;
    }
  }

  var CaptionedImageBlot = class extends BlockEmbed {
    static create(value) {
      var data = typeof value === 'string' ? { url: value, caption: '' } : (value || {});
      var node = super.create();
      var figure = createCaptionFigure(data.url || data.src || '', data.caption || '');

      node.className = figure.className;
      node.setAttribute('contenteditable', 'false');
      node.setAttribute('data-url', figure.getAttribute('data-url') || '');
      node.setAttribute('data-caption', figure.getAttribute('data-caption') || '');

      while (figure.firstChild) {
        node.appendChild(figure.firstChild);
      }

      return node;
    }

    static value(node) {
      var image = node.querySelector('img');
      var figcaption = node.querySelector('figcaption');

      return {
        url: node.getAttribute('data-url') || (image ? image.getAttribute('src') || '' : ''),
        caption: figcaption ? normalizeCaption(figcaption.textContent || '') : ''
      };
    }
  };

  CaptionedImageBlot.blotName = 'captionedImage';
  CaptionedImageBlot.tagName = 'figure';
  CaptionedImageBlot.className = 'ql-image-caption';
  Quill.register(CaptionedImageBlot, true);

  function uploadImage(file) {
    var fd = new FormData();
    fd.append('image', file);

    return fetch(appBase + '/admin/posts/upload-image', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.error) {
          alert(data.error);
          return null;
        }
        return data.url;
      })
      .catch(function () {
        alert('Image upload failed.');
        return null;
      });
  }

  function insertImage(quill, url) {
    var range = quill.getSelection(true);
    quill.insertText(range.index, '\n');
    quill.insertEmbed(range.index + 1, 'captionedImage', { url: url, caption: '' });
    quill.insertText(range.index + 2, '\n');
    quill.setSelection(range.index + 3);
  }

  document.querySelectorAll('.quill-editor').forEach(function (editorEl) {
    var hiddenInput = editorEl.parentNode.querySelector('.quill-content');

    var quill = new Quill(editorEl, {
      theme: 'snow',
      modules: {
        toolbar: {
          container: [
            ['bold', 'italic', 'underline'],
            [{ header: [1, 2, 3, false] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image'],
            ['clean']
          ],
          handlers: {
            image: function () {
              var input = document.createElement('input');
              input.setAttribute('type', 'file');
              input.setAttribute('accept', 'image/*');
              input.click();

              var q = this.quill;
              input.onchange = function () {
                var file = input.files[0];
                if (!file) return;
                uploadImage(file).then(function (url) {
                  if (url) insertImage(q, url);
                });
              };
            }
          }
        },
        clipboard: { matchVisual: false }
      }
    });

    bindImageDialog(quill, hiddenInput);

    if (hiddenInput && hiddenInput.value) {
      quill.root.innerHTML = hiddenInput.value;
      upgradeExistingImages(quill.root);
      syncHiddenInput(quill, hiddenInput);
    }

    quill.root.addEventListener('input', function () {
      syncHiddenInput(quill, hiddenInput);
    });

    quill.root.addEventListener('click', function (event) {
      var figure = event.target && event.target.closest ? event.target.closest('figure.ql-image-caption') : null;

      if (! figure) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      openImageDialog(figure);
    });

    quill.root.addEventListener('paste', function (event) {
      var items = (event.clipboardData || window.clipboardData).items;
      if (!items) return;

      for (var i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') === -1) continue;
        event.preventDefault();
        event.stopPropagation();

        var file = items[i].getAsFile();
        if (!file) return;

        uploadImage(file).then(function (url) {
          if (url) insertImage(quill, url);
        });
        return;
      }
    });

    quill.root.addEventListener('drop', function (event) {
      var files = event.dataTransfer && event.dataTransfer.files;
      if (!files || files.length === 0) return;

      var file = files[0];
      if (file.type.indexOf('image') === -1) return;
      event.preventDefault();
      event.stopPropagation();

      uploadImage(file).then(function (url) {
        if (url) insertImage(quill, url);
      });
    });

    if (hiddenInput && hiddenInput.value) {
      quill.root.innerHTML = hiddenInput.value;
    }

    quill.on('text-change', function () {
      if (hiddenInput) {
        var newVal = quill.root.innerHTML;
        if (hiddenInput.value !== newVal) {
          hiddenInput.value = newVal;
          hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      }
    });

    var form = editorEl.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        syncHiddenInput(quill, hiddenInput);
      });
    }
  });
});
