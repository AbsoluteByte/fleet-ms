<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgreementClientDocumentsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{path: string, as: string, label: string, mime: string}>  $attachmentsData
     * @param  list<string>  $attachedLabels
     * @param  list<string>  $missingDocuments
     */
    public function __construct(
        public Agreement $agreement,
        public array $attachmentsData,
        public array $attachedLabels,
        public array $missingDocuments
    ) {}

    public function build()
    {
        $company = $this->agreement->documentCompany();
        $subjectCompany = $company?->name ?: 'FleetIQ';
        $carReg = $this->agreement->car?->registration ?: 'Vehicle';

        $mail = $this->subject("Documents for {$carReg} - Agreement #{$this->agreement->id}")
            ->view('emails.agreement-client-documents')
            ->with([
                'agreement' => $this->agreement,
                'company' => $company,
                'driver' => $this->agreement->driver,
                'attachedLabels' => $this->attachedLabels,
                'missingDocuments' => $this->missingDocuments,
                'subjectCompany' => $subjectCompany,
            ]);

        foreach ($this->attachmentsData as $attachment) {
            $mail->attach($attachment['path'], [
                'as' => $attachment['as'],
                'mime' => $attachment['mime'],
            ]);
        }

        return $mail;
    }
}
