<div class="modal fade" id="driverFollowUpModal" tabindex="-1" role="dialog"
     aria-labelledby="driverFollowUpModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="driverFollowUpForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="driverFollowUpModalLabel">Note / Reminder</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="driverFollowUpModalSubtitle"></p>
                    <div class="form-group">
                        <label for="driverFollowUpNotes">Notes</label>
                        <textarea name="notes" id="driverFollowUpNotes" rows="4" class="form-control"
                                  placeholder="e.g. Driver asked to call back about outstanding balance"></textarea>
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="driverFollowUpSetReminder"
                               name="set_reminder" value="1">
                        <label class="custom-control-label" for="driverFollowUpSetReminder">Set reminder</label>
                    </div>
                    <div class="form-group mb-0" id="driverFollowUpRemindAtGroup" style="display: none;">
                        <label for="driverFollowUpRemindAt">Reminder date &amp; time</label>
                        <input type="datetime-local" name="remind_at" id="driverFollowUpRemindAt"
                               class="form-control">
                        <small class="form-text text-muted">
                            When this time arrives, a popup will show so you can call the driver.
                        </small>
                    </div>
                    <div id="driverFollowUpError" class="alert alert-danger mt-2 mb-0" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="driverFollowUpSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
