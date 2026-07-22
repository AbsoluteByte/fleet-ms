@extends('layouts.admin', ['title' => 'Daily Expenses'])
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
                                    @forelse($expenses as $expense)
                                        <tr>
                                            <td>{{ $expense->date?->format('d M Y') }}</td>
                                            <td>
                                                <span class="badge {{ $expense->daily_expense_type === \App\Models\Expense::DAILY_TYPE_VEHICLE ? 'badge-primary' : 'badge-info' }}">
                                                    {{ ucfirst($expense->daily_expense_type ?: \App\Models\Expense::DAILY_TYPE_OFFICE) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($expense->car)
                                                    {{ $expense->car->registration ?: '—' }}
                                                    @if($expense->car->carModel)
                                                        <div class="small text-muted">{{ $expense->car->carModel->name }}</div>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                {{ $expense->title ?: $expense->description }}
                                                @if($expense->notes)
                                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($expense->notes, 80) }}</div>
                                                @endif
                                            </td>
                                            <td>£{{ number_format((float) $expense->amount, 2) }}</td>
                                            <td>{{ $expense->payment_method ?: '—' }}</td>
                                            <td>
                                                @if($expense->bankAccount)
                                                    {{ $expense->bankAccount->bank_name }}
                                                    <div class="small text-muted">{{ $expense->bankAccount->account_number }}</div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($expense->isPending())
                                                    <span class="badge badge-warning">Pending</span>
                                                @else
                                                    <span class="badge badge-success">Posted</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($expense->document)
                                                    <a href="{{ asset('uploads/expense_documents/'.$expense->document) }}" target="_blank" rel="noopener">
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
                                                No daily expenses yet.
                                                <a href="{{ route($url.'create') }}">Add your first daily expense</a>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $expenses->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
