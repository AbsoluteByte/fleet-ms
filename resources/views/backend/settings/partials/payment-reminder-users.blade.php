@php
    $selectedReminderUserIds = old('payment_reminder_user_ids', $selectedReminderUserIds ?? []);
    if (! is_array($selectedReminderUserIds)) {
        $selectedReminderUserIds = [];
    }
    $selectedReminderUserIds = array_map('intval', $selectedReminderUserIds);
    $totalUsers = ($tenantUsers ?? collect())->count();
    $selectedCount = count($selectedReminderUserIds);
@endphp

@once
    @push('css')
        <style>
            .payment-reminder-users-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }

            .payment-reminder-users-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 12px;
            }

            .payment-reminder-user-card {
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                margin: 0;
                padding: 14px 16px;
                border: 1px solid #e4e7ec;
                border-radius: 10px;
                background: #fff;
                cursor: pointer;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            }

            .payment-reminder-user-card:hover {
                border-color: #7367f0;
                box-shadow: 0 4px 14px rgba(115, 103, 240, 0.12);
            }

            .payment-reminder-user-card.is-selected {
                border-color: #7367f0;
                background: #f8f7ff;
                box-shadow: 0 4px 14px rgba(115, 103, 240, 0.16);
            }

            .payment-reminder-user-card input {
                position: absolute;
                opacity: 0;
                width: 0;
                height: 0;
            }

            .payment-reminder-user-avatar {
                flex: 0 0 42px;
                width: 42px;
                height: 42px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #ecebff;
                color: #7367f0;
                font-weight: 700;
                font-size: 14px;
                text-transform: uppercase;
            }

            .payment-reminder-user-card.is-selected .payment-reminder-user-avatar {
                background: #7367f0;
                color: #fff;
            }

            .payment-reminder-user-meta {
                min-width: 0;
                flex: 1 1 auto;
            }

            .payment-reminder-user-name {
                display: block;
                font-weight: 600;
                color: #2f3349;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .payment-reminder-user-email {
                display: block;
                color: #8a8fa3;
                font-size: 12px;
                line-height: 1.3;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .payment-reminder-user-check {
                flex: 0 0 22px;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                border: 2px solid #d8dbe5;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: transparent;
                font-size: 11px;
                transition: all 0.2s ease;
            }

            .payment-reminder-user-card.is-selected .payment-reminder-user-check {
                border-color: #7367f0;
                background: #7367f0;
                color: #fff;
            }

            .payment-reminder-users-summary {
                margin-top: 16px;
                padding: 12px 14px;
                border-radius: 8px;
                background: #f8f9fb;
                border: 1px solid #ebeef2;
                color: #5e5873;
                font-size: 13px;
            }
        </style>
    @endpush

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-payment-reminder-users]').forEach(function (container) {
                    var cards = container.querySelectorAll('.payment-reminder-user-card');
                    var summary = container.querySelector('[data-reminder-summary]');
                    var selectAllBtn = container.querySelector('[data-reminder-select-all]');
                    var clearAllBtn = container.querySelector('[data-reminder-clear-all]');

                    function refreshCards() {
                        cards.forEach(function (card) {
                            var input = card.querySelector('input[type="checkbox"]');
                            card.classList.toggle('is-selected', input && input.checked);
                        });

                        if (!summary) {
                            return;
                        }

                        var checked = container.querySelectorAll('.payment-reminder-user-card input[type="checkbox"]:checked').length;
                        var total = cards.length;

                        if (checked === 0) {
                            summary.textContent = 'No users selected — all ' + total + ' users will receive payment call reminder popups.';
                        } else if (checked === total) {
                            summary.textContent = 'All ' + total + ' users selected — only these users will receive reminder popups.';
                        } else {
                            summary.textContent = checked + ' of ' + total + ' users selected — only selected users will receive reminder popups.';
                        }
                    }

                    cards.forEach(function (card) {
                        var input = card.querySelector('input[type="checkbox"]');
                        if (input) {
                            input.addEventListener('change', refreshCards);
                        }
                    });

                    if (selectAllBtn) {
                        selectAllBtn.addEventListener('click', function () {
                            cards.forEach(function (card) {
                                var input = card.querySelector('input[type="checkbox"]');
                                if (input) {
                                    input.checked = true;
                                }
                            });
                            refreshCards();
                        });
                    }

                    if (clearAllBtn) {
                        clearAllBtn.addEventListener('click', function () {
                            cards.forEach(function (card) {
                                var input = card.querySelector('input[type="checkbox"]');
                                if (input) {
                                    input.checked = false;
                                }
                            });
                            refreshCards();
                        });
                    }

                    refreshCards();
                });
            });
        </script>
    @endpush
@endonce

<div class="payment-reminder-users" data-payment-reminder-users>
    <div class="payment-reminder-users-toolbar">
        <p class="text-muted small mb-0">
            Choose who receives payment call reminder popups. Leave all unchecked to allow every user.
        </p>

        @if($totalUsers > 0)
            <div class="btn-group btn-group-sm" role="group" aria-label="Reminder user selection">
                <button type="button" class="btn btn-outline-primary" data-reminder-select-all>
                    Select all
                </button>
                <button type="button" class="btn btn-outline-secondary" data-reminder-clear-all>
                    Clear all
                </button>
            </div>
        @endif
    </div>

    @if($totalUsers === 0)
        <div class="alert alert-warning mb-0">
            No users are linked to this company yet. Add users first, then assign reminder alerts here.
        </div>
    @else
        <div class="payment-reminder-users-grid">
            @foreach($tenantUsers as $tenantUser)
                @php
                    $displayName = trim((string) ($tenantUser->name ?: $tenantUser->email));
                    $initials = collect(preg_split('/\s+/', $displayName))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->implode('');
                    $initials = $initials !== '' ? $initials : '?';
                    $isSelected = in_array((int) $tenantUser->id, $selectedReminderUserIds, true);
                @endphp
                <label class="payment-reminder-user-card {{ $isSelected ? 'is-selected' : '' }}"
                       for="payment_reminder_user_{{ $tenantUser->id }}">
                    <input type="checkbox"
                           name="payment_reminder_user_ids[]"
                           id="payment_reminder_user_{{ $tenantUser->id }}"
                           value="{{ $tenantUser->id }}"
                           {{ $isSelected ? 'checked' : '' }}>
                    <span class="payment-reminder-user-avatar" aria-hidden="true">{{ $initials }}</span>
                    <span class="payment-reminder-user-meta">
                        <span class="payment-reminder-user-name">{{ $displayName }}</span>
                        <span class="payment-reminder-user-email">{{ $tenantUser->email }}</span>
                    </span>
                    <span class="payment-reminder-user-check" aria-hidden="true">
                        <i class="fa fa-check"></i>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="payment-reminder-users-summary" data-reminder-summary>
            @if($selectedCount === 0)
                No users selected — all {{ $totalUsers }} users will receive payment call reminder popups.
            @elseif($selectedCount === $totalUsers)
                All {{ $totalUsers }} users selected — only these users will receive reminder popups.
            @else
                {{ $selectedCount }} of {{ $totalUsers }} users selected — only selected users will receive reminder popups.
            @endif
        </div>
    @endif
</div>

@error('payment_reminder_user_ids')
<div class="invalid-feedback d-block mt-2">{{ $message }}</div>
@enderror
@error('payment_reminder_user_ids.*')
<div class="invalid-feedback d-block mt-2">{{ $message }}</div>
@enderror
