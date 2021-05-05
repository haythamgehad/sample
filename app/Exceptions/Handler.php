<?php

namespace App\Exceptions;

use App\Constants\TranslationCode;
use App\Services\LogService;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class Handler
 *
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Exception $exception
     *
     * @throws Exception
     */
    public function report(Throwable $exception)
    {
        //print_r($exception);die();
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * When in production we return same json structure even when error occurred.
     *
     * @param Request $request
     * @param Exception $exception
     *
     * @return JsonResponse|Response
     */
    public function render($request, Throwable $exception)
    {
        if (env('APP_DEBUG')) {
            return parent::render($request, $exception);
        }

        Log::error(LogService::getExceptionTraceAsString($exception, $request));

        $response = [
            'isError' => true,
            'userFault' => false,
            'errorMessage' => ['application' => __('Some Thing went wrong, please contact with admin')]
        ];

        return response()->json($response, Response::HTTP_OK);
    }
}