<?php

namespace App\Console;

/*
use App\Console\Commands\DeleteExpiredTokensCommand;
use App\Console\Commands\TaskNotificationsCommand;
use App\Console\Commands\TestNotificationsCommand;
*/
use App\Console\Commands\ActionNotificationsCommand;
use App\Console\Commands\TaskNotificationsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package App\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        /*
        DeleteExpiredTokensCommand::class,
        TaskNotificationsCommand::class,
        TestNotificationsCommand::class,
        */
        ActionNotificationsCommand::class,
        TaskNotificationsCommand::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        /*

        $schedule->command('delete:expiredTokens')
            ->daily()->at('22:40')
            ->appendOutputTo(storage_path('logs/cron_delete_expired_tokens.log'));

        $schedule->command('send:taskNotifications')
            ->daily()->at('22:40')
            ->appendOutputTo(storage_path('logs/cron_send_task_notifications.log'));
        
        $schedule->command('do1:testNotifications')
        ->daily()
        ->timezone('Africa/Cairo')
        ->at('00:52')
        ->appendOutputTo(storage_path('logs/cron_send_task_notifications.log'));

        */

        $schedule->command('notify:actionNotifications')
        ->daily()
        ->timezone('Africa/Cairo')
        ->at('01:22')
        ->appendOutputTo(storage_path('logs/cron_notify_action_notifications.log'));

        $schedule->command('notify:taskNotifications')
        ->daily()
        ->timezone('Africa/Cairo')
        ->at('01:22')
        ->appendOutputTo(storage_path('logs/cron_notify_task_notifications.log'));
        
            
    }
}
