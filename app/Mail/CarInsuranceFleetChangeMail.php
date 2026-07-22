<?php

namespace App\Mail;

use App\Models\Car;
use App\Models\InsuranceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CarInsuranceFleetChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Car $car,
        public InsuranceProvider $provider,
        public string $action,
        public string $subjectLine,
        public string $bodyText,
        public ?array $sender = null,
        public bool $useCustomFrom = false,
    ) {}

    public function build()
    {
        $message = $this->subject($this->subjectLine)
            ->view('emails.car-insurance-fleet-change')
            ->with([
                'bodyText' => $this->bodyText,
                'car' => $this->car,
                'provider' => $this->provider,
                'action' => $this->action,
            ]);

        if (! is_array($this->sender) || ! filled($this->sender['address'] ?? null)) {
            return $message;
        }

        $address = (string) $this->sender['address'];
        $name = filled($this->sender['name'] ?? null) ? (string) $this->sender['name'] : null;

        $message->replyTo($address, $name);

        if ($this->useCustomFrom) {
            $message->from($address, $name);
        } elseif ($name !== null) {
            $message->from((string) config('mail.from.address'), $name);
        }

        return $message;
    }
}
