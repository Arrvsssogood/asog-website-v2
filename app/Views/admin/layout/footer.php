        </div>
    </div>
</div>

<?= view('admin/components/delete_confirm_modal') ?>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script id="adminQuillBootstrap"
    data-base-url="<?= esc(rtrim(site_url(), '/'), 'attr') ?>"
    src="<?= base_url('assets/js/admin/layout/quill.js') ?>"
    defer></script>
<script src="<?= base_url('assets/js/admin/customSelect.js') ?>"></script>
<script src="<?= base_url('assets/js/admin/layout/deleteConfirm.js') ?>" defer></script>
<script src="<?= base_url('assets/js/admin/layout/notifications.js') ?>" defer></script>
</body>
</html>
