<?php

namespace App\Mail;

use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DriverInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function build()
    {
        return $this->subject('Fleet Management - Driver Invitation')
            ->view('emails.driver-invitation')
            ->with([
                'driver' => $this->driver,
                'invitationUrl' => $this->driver->invitation_url,
                'expiresAt' => $this->driver->invited_at->addDays(7)
            ]);
    }
}
