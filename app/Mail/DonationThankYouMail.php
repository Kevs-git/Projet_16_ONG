<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DonationThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public Donation $donation;

    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
    }

    public function build()
    {
        return $this->subject('Merci pour votre don')
            ->view('emails.donation_thank_you')
            ->with([
                'donorName' => $this->donation->donor?->name,
                'campaignTitle' => $this->donation->campaign?->title,
                'amount' => $this->donation->amount,
                'receiptNumber' => $this->donation->receipt_number,
            ]);
    }
}
