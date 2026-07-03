<div class="modal fade" id="paymentNotesModal" tabindex="-1" role="dialog" aria-labelledby="paymentNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="paymentNotesForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect_to" id="paymentNotesRedirectTo" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentNotesModalLabel">Payment Notes</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-1" id="paymentNotesModalSubtitle"></p>
                    <div class="form-group mb-0">
                        <label for="paymentNotesInput">Notes</label>
                        <textarea name="notes" id="paymentNotesInput" rows="4" class="form-control"
                                  placeholder="Add payment notes (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Notes</button>
                </div>
            </form>
        </div>
    </div>
</div>
