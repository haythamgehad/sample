<?php

namespace App\Http\Middleware;

use App\Constants\TranslationCode;
use App\Models\User;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use IonGhitun\JwtToken\Jwt;

/**
 * Class CheckTokenIsValid
 *
 * @package App\Http\Middleware
 */
class CheckTokenIsValid
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
        try{
            $token = Jwt::validateToken($request->api_token);
            $user = User::find($token['id']);
            if (!$user) {
                $response = [
                    'isError' => true,
                    'userFault' => true,
                    'errorMessage' => ['authorization' => __('Unauthorized')]
                ];

                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            }

            $request->user_id = $user->id;
            return $next($request);
        }catch(Exception $e){
            $response = [
                'isError' => true,
                'userFault' => true,
                'errorMessage' => ['authorization' => __('Unauthorized')]
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

    }
}