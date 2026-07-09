<!-- Shared note: one "unsaved changes" confirmation surface for admin panels, paired with adminDeleteConfirm's pattern. -->
<div class="admin-discard-confirm" id="adminDiscardConfirm" aria-hidden="true">
    <div class="admin-discard-confirm-backdrop" data-admin-discard-cancel></div>
    <div class="admin-discard-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminDiscardConfirmTitle" aria-describedby="adminDiscardConfirmMessage">
        <div class="admin-discard-confirm-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17h.01"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
        </div>
        <div class="admin-discard-confirm-copy">
            <h2 id="adminDiscardConfirmTitle">Discard unsaved changes?</h2>
            <p id="adminDiscardConfirmMessage">You have unsaved changes on this page. Leaving now will discard them.</p>
        </div>
        <div class="admin-discard-confirm-actions">
            <button type="button" class="btn btn-o" data-admin-discard-cancel>Keep editing</button>
            <button type="button" class="btn admin-discard-confirm-ok" id="adminDiscardConfirmOk">Discard changes</button>
        </div>
    </div>
</div>