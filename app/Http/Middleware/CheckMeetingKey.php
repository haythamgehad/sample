<?php

namespace App\Http\Middleware;

use App\Models\Meeting;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class CheckTokenIsValid
 *
 * @package App\Http\Middleware
 */
class CheckMeetingKey
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response|ResponseFactory|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $meetingKey = $request->meeting_key;
            $mediaId = $request->route()[2]['id'];
            $media = Meeting::where('meeting_key', $meetingKey)->first();
            if (!$media) {
                $response = [
                    'isError' => true,
                    'userFault' => true,
                    'errorMessage' => ['authorization' => __('Unauthorized')]
                ];
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            }

            return $next($request);
        } catch (Exception $e) {
            $response = [
                'isError' => true,
                'userFault' => true,
                'errorMessage' => ['authorization' => __('Unauthorized')]
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

    }
}