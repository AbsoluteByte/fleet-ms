<div class="modal fade" id="documentDeleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="documentDeleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 28rem;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center mb-0" id="documentDeleteConfirmModalLabel">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center mr-75"
                          style="width:2.25rem;height:2.25rem;background:#fff5f5;color:#ea5455;">
                        <i class="fa fa-trash-alt"></i>
                    </span>
                    <span>Remove document?</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-1 pb-2">
                <p class="mb-0 text-body" id="documentDeleteConfirmModalBody" style="line-height: 1.55;">
                    Are you sure you want to remove this document? The file will be deleted from the system. You can upload a new file afterwards.
                </p>
                <p class="small text-muted mb-0 mt-1" id="documentDeleteConfirmModalHint"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="documentDeleteConfirmBtn">
                    <i class="fa fa-trash-alt mr-50"></i>Yes, remove document
                </button>
            </div>
        </div>
    </div>
</div>
