<?php

namespace App\Console\Commands;

use App\Constants\TranslationCode;
use App\Models\Action;
use App\Services\LogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class ActionNotificationsCommand
 *
 * Send notifications to assigned user when an uncompleted test has deadline today.
 * Send notifications to users added the test when the task is uncompleted and deadline passed by a day.
 * Should be running once a day.
 *
 * @package App\Console\Commands
 */
class ActionNotificationsCommand extends Command
{
    /** @var string */
    protected $signature = 'notify:actionNotifications';

    /** @var string */
    protected $description = 'Notify notifications when a test is expiring or has expired.';

    /** @var NotificationService */
    protected $notificationService;

    /**
     * TestNotificationsCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->notificationService = new NotificationService();
    }

    /**
     * Command handle
     */
    public function handle()
    {
        try {

            $this->checkActionDueDate();
        } catch (Exception $e) {
            Log::error(LogService::getExceptionTraceAsString($e));

            $this->error($e->getMessage());
        }
    }

    /**
     * Identify uncompleted tests that have deadline today and send notifications.
     */
    private function checkActionDueDate()
    {
        $notificationService = new NotificationService();

        $actions = Action::where( 'due_date', '>', Carbon::now()->subDays(3))->get();

        foreach($actions as $action){
            $link = url('/actions/'.$action->id);
            $notificationService->sendNotification(
                $action->assignee_id, 
                $action->account_id , 
                $action->title , 
                $link ,
                NotificationType::ACTION_DUE_DATE_NOTIFICATION,
                array()
            );
        }
    }
        
 
}
