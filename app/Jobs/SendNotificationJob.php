<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class SendMailJob
 *
 * @package App\Jobs
 */
class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable ;

    /** 
     * @var int 
     */
    public $tries = 3;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $accountId;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $link;

    /**
     * @var string
     */
    protected $typeCode;

    /**
     * @var string
     */
    protected $extraData;

    /**
     * @var string|null
     */
    protected $emailLink;

    /**
     * @var string|null
     */
    protected $linkText;

    /**
     * @var string|null
     */
    protected $attachFile;

    /**
     * @var string|null
     */
    protected $attachMime;

    public function __construct(
        $userId,
        $accountId,
        $title,
        $link,
        $typeCode,
        $extraData = [],
        $emailLink = '',
        $linkText = '',
        $attachFile = '',
        $attachMime = ''
    )
    {
        $this->userId = $userId;
        $this->accountId = $accountId;
        $this->title = $title;
        $this->link = $link;
        $this->typeCode = $typeCode;
        $this->extraData = $extraData;
        $this->emailLink = $emailLink;
        $this->linkText = $linkText;
        $this->attachFile = $attachFile;
        $this->attachMime = $attachMime;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try{
            $notificationService = new NotificationService();
            $send = $notificationService->sendNotificationInQueue(
                $this->userId,
                $this->accountId,
                $this->title,
                $this->link,
                $this->typeCode,
                $this->extraData,
                $this->emailLink,
                $this->linkText,
                $this->attachFile,
                $this->attachMime
            );
        } catch(\Exception $e) {
            Log::info('Can not send notification: ' . $e->getMessage());
        }
    }
}
