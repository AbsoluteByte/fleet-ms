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
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.car-insurance-fleet-change')
            ->with([
                'bodyText' => $this->bodyText,
                'car' => $this->car,
                'provider' => $this->provider,
                'action' => $this->action,
            ]);
    }
}
