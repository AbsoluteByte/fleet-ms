<div class="modal fade" id="driverDocumentArchiveModal" tabindex="-1" role="dialog" aria-labelledby="driverDocumentArchiveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverDocumentArchiveModalLabel">
                    <i class="fa fa-archive mr-50"></i>
                    Document Archive
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th>Document</th>
                            <th>File</th>
                            <th>Archived</th>
                            <th>Reason</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($documentArchives as $archive)
                            <tr>
                                <td>{{ $archive->document_label }}</td>
                                <td><span class="text-muted font-small-3">{{ $archive->filename }}</span></td>
                                <td>
                                    {{ $archive->archived_at?->format('d M, Y h:i A') }}
                                    @if($archive->archivedBy)
                                        <br><small class="text-muted">by {{ $archive->archivedBy->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($archive->reason === 'replaced')
                                        <span class="badge badge-info">{{ $archive->reasonLabel() }}</span>
                                    @else
                                        <span class="badge badge-warning">{{ $archive->reasonLabel() }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ $archive->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No archived documents.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
