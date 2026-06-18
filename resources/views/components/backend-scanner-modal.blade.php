<style>
    .fleetiq-scanner-upload-wrap > input[type="file"] {
        flex: 1 1 auto;
        min-width: 0;
    }

    .fleetiq-scan-button {
        flex: 0 0 auto;
        white-space: nowrap;
        width: 34px;
        min-width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
    }

    .asprise-web-scan-dialog-wrapper .overlay,
    .asprise-web-scan-dialog-wrapper .asprise-web-scan-dialog {
        position: fixed !important;
    }
</style>

<div class="modal fade" id="fleetiqScannerModal" tabindex="-1" role="dialog" aria-labelledby="fleetiqScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fleetiqScannerModalLabel">
                    <i class="fa fa-print mr-50"></i> Scan Document
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="fleetiqScannerStatus" class="alert alert-info mb-2">
                    Click <strong>Start Scan</strong> to scan a document directly from an attached scanner.
                </div>
                <div class="small text-muted mb-2">
                    Normal file upload still works. Scanner use requires the Scanner.js desktop/client bridge on this workstation.
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="fleetiqScannerCombinePdf" value="1">
                    <label class="custom-control-label" for="fleetiqScannerCombinePdf">
                        Combine all scanned pages into a single PDF file
                    </label>
                </div>
                <div id="fleetiqScannerPreview" class="d-flex flex-wrap"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="fleetiqScannerStart">
                    <i class="fa fa-print mr-50"></i> Start Scan
                </button>
            </div>
        </div>
    </div>
</div>
