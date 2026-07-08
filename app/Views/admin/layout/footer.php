        </div>
    </div>
</div>

<?= view('admin/components/delete_confirm_modal') ?>

<script src="<?= base_url('assets/vendor/quill/quill.min.js') ?>"></script>
<script id="adminQuillBootstrap"
    data-base-url="<?= esc(rtrim(site_url(), '/'), 'attr') ?>"
    src="<?= base_url('assets/js/admin/layout/quill.js') ?>"
    defer></script>
<script src="<?= base_url('assets/js/admin/customSelect.js') ?>"></script>
<script src="<?= base_url('assets/js/admin/layout/adminShell.js') ?>" defer></script>
<script src="<?= base_url('assets/js/admin/layout/deleteConfirm.js') ?>" defer></script>
<script src="<?= base_url('assets/js/admin/layout/notifications.js') ?>" defer></script>
<script src="<?= base_url('assets/js/admin/settings/index.js') ?>" defer></script>
</body>
</html>
