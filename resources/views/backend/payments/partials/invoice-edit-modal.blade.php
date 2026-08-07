@once('invoice-edit-modal')
    <div class="modal fade" id="editInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="editInvoiceForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editInvoiceModalLabel">Edit invoice</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_invoice_subtotal">Subtotal</label>
                            <input type="number" step="0.01" min="0" name="subtotal" id="edit_invoice_subtotal" class="form-control" required>
                        </div>
                        <div class="form-group mb-0">
                            <label for="edit_invoice_total_amount">Total amount</label>
                            <input type="number" step="0.01" min="0" name="total_amount" id="edit_invoice_total_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = window.jQuery ? window.jQuery('#editInvoiceModal') : null;
                var form = document.getElementById('editInvoiceForm');
                var updateUrlTemplate = @json(route('invoices.update', ['invoice' => '__INVOICE__']));

                if (!form || !modal) {
                    return;
                }

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('.js-edit-invoice');
                    if (!button) {
                        return;
                    }

                    var invoiceId = button.getAttribute('data-invoice-id');
                    if (!invoiceId) {
                        return;
                    }

                    form.action = updateUrlTemplate.replace('__INVOICE__', invoiceId);
                    document.getElementById('edit_invoice_subtotal').value = button.getAttribute('data-subtotal') || '';
                    document.getElementById('edit_invoice_total_amount').value = button.getAttribute('data-total-amount') || '';
                    modal.modal('show');
                });
            });
        </script>
    @endpush
@endonce
