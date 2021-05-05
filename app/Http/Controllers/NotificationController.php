<?php

namespace App\Http\Controllers;


use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;


/**
 * Class NotificationController
 * @package App\Http\Controllers
 */
class NotificationController extends Controller
{

    public function __construct()
    {
    }

    /**
     * Show Notifications list
     * GET /notifications
     * @return Response
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::with(['fromUser'])->where('to_id', $user->id)->orderBy('id', 'desc')->take(50)->get();

        return $this->sendResponse($notifications->toArray(), 'Notifications retrieved successfully');
    }
}
