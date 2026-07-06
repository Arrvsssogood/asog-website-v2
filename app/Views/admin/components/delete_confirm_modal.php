<!-- Shared note: one delete confirmation surface for admin panels, so destructive actions feel consistent. -->
<div class="admin-delete-confirm" id="adminDeleteConfirm" aria-hidden="true">
    <div class="admin-delete-confirm-backdrop" data-admin-delete-cancel></div>
    <div class="admin-delete-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminDeleteConfirmTitle" aria-describedby="adminDeleteConfirmMessage">
        <div class="admin-delete-confirm-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
        </div>
        <div class="admin-delete-confirm-copy">
            <h2 id="adminDeleteConfirmTitle">Delete item?</h2>
            <p id="adminDeleteConfirmMessage">This item will be permanently deleted. This action cannot be undone.</p>
        </div>
        <div class="admin-delete-confirm-actions">
            <button type="button" class="btn btn-o" data-admin-delete-cancel>Cancel</button>
            <button type="button" class="btn admin-delete-confirm-ok" id="adminDeleteConfirmOk">Delete</button>
        </div>
    </div>
</div>
