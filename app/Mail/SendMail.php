<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class SendMail
 *
 * @package App\Mail
 */
class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * SendMail constructor.
     *
     * @param $to
     * @param string $subject
     * @param string $blade
     * @param array $data
     */
    public function __construct($to, string $subject, string $blade, array $data)
    {
        $this->to($to)
            ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->subject($subject)
            ->view($blade)
            ->with($data);
            
            if(isset($data['attach_file']) && !empty($data['attach_file']) && isset($data['attach_mime']) && !empty($data['attach_mime'])){
                $this->attach($data['attach_file'], array('mime' => $data['attach_mime']));
            }
            
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this;
    }
}
