<?php

namespace amrfayad\MailTracker\Events;

use amrfayad\MailTracker\Model\SentEmail;
use App\Events\Event;
use Illuminate\Queue\SerializesModels;

class ViewEmailEvent extends Event
{
    use SerializesModels;

    public $sent_email;

    /**
     * Create a new event instance.
     *
     * @param  sent_email  $sent_email
     * @return void
     */
    public function __construct(SentEmail $sent_email)
    {
        $this->sent_email = $sent_email;
    }
}