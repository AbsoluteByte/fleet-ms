@extends('layouts.admin', ['title' => 'Other Payments'])
@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $plural }}</h4>
                        <a class="btn btn-primary float-right" href="{{ route($url.'create') }}">
                            <i class="fa fa-plus"></i> Add {{ $singular }}
                        </a>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Vehicle</th>
                                        <th>Title</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Bank</th>
                                        <th>Status</th>
                                        <th>Document</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($otherPayments as $otherPayment)
                                        <tr>
                                            <td>{{ $otherPayment->payment_date?->format('d M Y') }}</td>
                                            <td>
                                                <span class="badge {{ $otherPayment->other_payment_type === \App\Models\OtherPayment::TYPE_VEHICLE ? 'badge-primary' : 'badge-info' }}">
                                                    {{ ucfirst($otherPayment->other_payment_type ?: \App\Models\OtherPayment::TYPE_OFFICE) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($otherPayment->car)
                                                    {{ $otherPayment->car->registration ?: '—' }}
                                                    @if($otherPayment->car->carModel)
                                                        <div class="small text-muted">{{ $otherPayment->car->carModel->name }}</div>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                {{ $otherPayment->title }}
                                                @if($otherPayment->notes)
                                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($otherPayment->notes, 80) }}</div>
                                                @endif
                                            </td>
                                            <td>£{{ number_format((float) $otherPayment->amount, 2) }}</td>
                                            <td>{{ $otherPayment->payment_method ?: '—' }}</td>
                                            <td>
                                                @if($otherPayment->bankAccount)
                                                    {{ $otherPayment->bankAccount->bank_name }}
                                                    <div class="small text-muted">{{ $otherPayment->bankAccount->account_number }}</div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($otherPayment->isPending())
                                                    <span class="badge badge-warning">Pending</span>
                                                @else
                                                    <span class="badge badge-success">Posted</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($otherPayment->document)
                                                    <a href="{{ asset('uploads/other_payment_documents/'.$otherPayment->document) }}" target="_blank" rel="noopener">
                                                        View
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                No other payments yet.
                                                <a href="{{ route($url.'create') }}">Add your first other payment</a>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $otherPayments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
