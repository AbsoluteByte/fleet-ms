@auth
<div class="modal fade" id="paymentFollowUpDueModal" tabindex="-1" role="dialog"
     aria-labelledby="paymentFollowUpDueModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="paymentFollowUpDueModalLabel">
                    <i class="fa fa-phone"></i> Payment call reminder
                </h5>
                <button type="button" class="close js-follow-up-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    The following driver(s) asked to be called at this time.
                </p>
                <div id="paymentFollowUpDueList"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">Close pauses the alert and shows again in 15 minutes.</small>
                <button type="button" class="btn btn-secondary js-follow-up-close" data-dismiss="modal">
                    Close (remind in 15 min)
                </button>
            </div>
        </div>
    </div>
</div>

<audio id="paymentFollowUpAlertAudio" src="{{ asset('app-assets/sounds/payment-reminder.wav') }}?v=3" preload="auto"></audio>

<script>
    (function () {
        const dueUrl = @json(route('payments.follow-up.due'));
        const csrfToken = @json(csrf_token());
        const SNOOZE_MS = 15 * 60 * 1000;
        const SNOOZE_PREFIX = 'payment_follow_up_snooze_';
        const POLL_MS = 15000;

        let pollTimer = null;
        let dismissUrls = {};
        let lastRenderedReminders = [];
        let closingViaDismiss = false;
        let modalIsOpen = false;
        let audioUnlocked = false;
        let soundPending = false;

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function snoozeKey(driverId) {
            return SNOOZE_PREFIX + String(driverId);
        }

        function readSnoozeEntry(driverId) {
            try {
                const raw = sessionStorage.getItem(snoozeKey(driverId));
                if (!raw) {
                    return null;
                }

                if (/^\d+$/.test(raw)) {
                    return { until: parseInt(raw, 10), remind_at: '' };
                }

                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed.until !== 'number') {
                    sessionStorage.removeItem(snoozeKey(driverId));
                    return null;
                }

                return parsed;
            } catch (e) {
                return null;
            }
        }

        function isSnoozed(item) {
            const entry = readSnoozeEntry(item.id);
            if (!entry) {
                return false;
            }

            if (item.remind_at && entry.remind_at && entry.remind_at !== item.remind_at) {
                clearSnooze(item.id);
                return false;
            }

            if (!entry.until || entry.until <= Date.now()) {
                clearSnooze(item.id);
                return false;
            }

            return true;
        }

        function snoozeReminders(reminders) {
            const until = Date.now() + SNOOZE_MS;
            reminders.forEach(function (item) {
                try {
                    sessionStorage.setItem(snoozeKey(item.id), JSON.stringify({
                        until: until,
                        remind_at: item.remind_at || ''
                    }));
                } catch (e) {
                    // ignore storage errors
                }
            });
        }

        function clearSnooze(driverId) {
            try {
                sessionStorage.removeItem(snoozeKey(driverId));
            } catch (e) {
                // ignore
            }
        }

        window.clearPaymentFollowUpSnooze = clearSnooze;

        function getAudio() {
            return document.getElementById('paymentFollowUpAlertAudio');
        }

        function unlockAudio() {
            if (audioUnlocked) {
                return;
            }

            const audio = getAudio();
            if (!audio) {
                return;
            }

            const previousVolume = audio.volume;
            audio.volume = 0;
            const playPromise = audio.play();
            if (!playPromise || typeof playPromise.then !== 'function') {
                return;
            }

            playPromise.then(function () {
                audio.pause();
                audio.currentTime = 0;
                audio.volume = previousVolume || 0.65;
                audioUnlocked = true;
                if (soundPending) {
                    ensureSoundPlaying();
                }
            }).catch(function () {
                audio.volume = previousVolume || 0.65;
            });
        }

        function ensureSoundPlaying() {
            const audio = getAudio();
            if (!audio) {
                return;
            }

            soundPending = true;
            audio.loop = true;
            audio.volume = 0.65;

            if (!audio.paused && audio.currentTime > 0 && !audio.ended) {
                return;
            }

            const playPromise = audio.play();
            if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function () {
                    // Browser may block autoplay until the user interacts with the page.
                });
            }
        }

        function stopSound() {
            soundPending = false;
            const audio = getAudio();
            if (!audio) {
                return;
            }
            audio.pause();
            audio.currentTime = 0;
        }

        function renderReminders(reminders) {
            const container = document.getElementById('paymentFollowUpDueList');
            if (!container) {
                return;
            }

            lastRenderedReminders = reminders.slice();
            dismissUrls = {};

            container.innerHTML = reminders.map(function (item) {
                dismissUrls[item.id] = item.dismiss_url;
                return '<div class="border rounded p-2 mb-2" data-reminder-id="' + item.id + '">' +
                    '<div>' +
                    '<strong>' + escapeHtml(item.name) + '</strong>' +
                    (item.phone ? '<div class="text-muted small"><i class="fa fa-phone"></i> ' + escapeHtml(item.phone) + '</div>' : '') +
                    '<div class="small mt-1"><strong>Reminder:</strong> ' + escapeHtml(item.remind_at_display || '') + '</div>' +
                    (item.notes ? '<div class="mt-1">' + escapeHtml(item.notes) + '</div>' : '<div class="text-muted mt-1 small">No notes</div>') +
                    '</div>' +
                    '<div class="mt-2">' +
                    '<a href="' + escapeHtml(item.payments_url) + '" class="btn btn-sm btn-outline-primary mr-1">Open payments</a>' +
                    '<button type="button" class="btn btn-sm btn-primary js-dismiss-follow-up" data-driver-id="' + item.id + '">Dismiss reminder</button>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function openDueModal(reminders) {
            if (!window.jQuery || reminders.length === 0) {
                return false;
            }

            renderReminders(reminders);
            jQuery('#paymentFollowUpDueModal').modal('show');
            ensureSoundPlaying();
            return true;
        }

        async function loadDueReminders() {
            if (!window.jQuery) {
                return;
            }

            try {
                const response = await fetch(dueUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const reminders = (Array.isArray(data.reminders) ? data.reminders : [])
                    .filter(function (item) {
                        return !isSnoozed(item);
                    });

                if (reminders.length === 0) {
                    if (modalIsOpen) {
                        closingViaDismiss = true;
                        stopSound();
                        jQuery('#paymentFollowUpDueModal').modal('hide');
                    }
                    return;
                }

                if (modalIsOpen) {
                    renderReminders(reminders);
                    ensureSoundPlaying();
                    return;
                }

                openDueModal(reminders);
            } catch (e) {
                // Ignore poll errors
            }
        }

        async function dismissReminder(driverId, button) {
            const url = dismissUrls[driverId];
            if (!url) {
                return;
            }

            button.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({})
                });

                if (!response.ok) {
                    button.disabled = false;
                    return;
                }

                clearSnooze(driverId);
                const card = button.closest('[data-reminder-id]');
                if (card) {
                    card.remove();
                }
                delete dismissUrls[driverId];
                lastRenderedReminders = lastRenderedReminders.filter(function (item) {
                    return String(item.id) !== String(driverId);
                });

                const remaining = document.querySelectorAll('#paymentFollowUpDueList [data-reminder-id]');
                if (remaining.length === 0 && window.jQuery) {
                    closingViaDismiss = true;
                    stopSound();
                    jQuery('#paymentFollowUpDueModal').modal('hide');
                }
            } catch (e) {
                button.disabled = false;
            }
        }

        function handleCloseSnooze() {
            stopSound();
            if (closingViaDismiss) {
                closingViaDismiss = false;
                lastRenderedReminders = [];
                return;
            }
            if (lastRenderedReminders.length) {
                snoozeReminders(lastRenderedReminders);
            }
            lastRenderedReminders = [];
        }

        function bindEvents() {
            document.addEventListener('click', unlockAudio, { once: true, capture: true });
            document.addEventListener('keydown', unlockAudio, { once: true, capture: true });

            document.addEventListener('click', function (event) {
                unlockAudio();
                const button = event.target.closest('.js-dismiss-follow-up');
                if (!button) {
                    return;
                }
                const driverId = button.getAttribute('data-driver-id');
                if (driverId) {
                    dismissReminder(driverId, button);
                }
            });

            jQuery('#paymentFollowUpDueModal')
                .on('shown.bs.modal', function () {
                    modalIsOpen = true;
                    ensureSoundPlaying();
                })
                .on('hide.bs.modal', function () {
                    handleCloseSnooze();
                })
                .on('hidden.bs.modal', function () {
                    modalIsOpen = false;
                    stopSound();
                    closingViaDismiss = false;
                });
        }

        function startPolling() {
            if (!window.jQuery) {
                window.setTimeout(startPolling, 100);
                return;
            }

            bindEvents();
            loadDueReminders();
            pollTimer = window.setInterval(loadDueReminders, POLL_MS);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', startPolling);
        } else {
            startPolling();
        }

        window.addEventListener('beforeunload', function () {
            if (pollTimer) {
                window.clearInterval(pollTimer);
            }
            stopSound();
        });
    })();
</script>
@endauth
