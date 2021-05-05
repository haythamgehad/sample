<?php

namespace App\Console\Commands;

use App\Constants\TranslationCode;
use App\Models\Task;
use App\Models\TaskAssignee;
use App\Services\LogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class TaskNotificationsCommand
 *
 * Send notifications to assigned user when an uncompleted test has deadline today.
 * Send notifications to users added the test when the task is uncompleted and deadline passed by a day.
 * Should be running once a day.
 *
 * @package App\Console\Commands
 */
class TaskNotificationsCommand extends Command
{
    /** @var string */
    protected $signature = 'notify:taskNotifications';

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

            $this->checkTaskDueDate();
        } catch (Exception $e) {
            Log::error(LogService::getExceptionTraceAsString($e));

            $this->error($e->getMessage());
        }
    }

    /**
     * Identify uncompleted tests that have deadline today and send notifications.
     */
    private function checkTaskDueDate()
    {
        $notificationService = new NotificationService();

        $tasks = Task::where( 'due_date', '>', Carbon::now()->subDays(3))->get();

        foreach($tasks as $task){

            $link = url('/tasks/'.$task->id);
                $notificationService->sendNotification(
                    $task->assignee_id, 
                    $task->account_id , 
                    $task->title , 
                    $link ,
                    NotificationType::TASK_DUE_DATE_NOTIFICATION,
                    array()
                );
        }
        
    }
}
