<?php

namespace App\Http\Controllers;

use App\Constants\TranslationCode;
use App\Models\User;
use App\Models\Language;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use IonGhitun\JwtToken\Jwt;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Lang;

/**
 * Class Controller
 *
 * All controllers should extend this controller
 *
 * @package App\Http\Controllers
 */
class Controller extends BaseController
{
    /** @var BaseService */
    protected $baseService;

    /** @var bool */
    private $isError = false;

    private $success = true;

    /** @var array */
    private $errorMessage = [];

    /** @var array */
    private $successMessage = [];

    /** @var bool */
    private $isForbidden = false;

    /** @var array */
    private $forbiddenMessage = [];

    /** @var bool */
    private $userFault = false;

    /** @var null */
    private $result = null;

    /** @var array */
    private $pagination = [];

    /** @var bool */
    private $refreshToken = false;

    private $isWarning = false;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->baseService = new BaseService();
    }

    /**
     * Success response
     *
     * @param string|array|null $data
     * @param array|null $pagination
     * @param bool|null $refreshToken
     *
     * @return JsonResponse
     */

    protected function sendError($errorMessage=null){
       
        return $this->userErrorResponse(array($errorMessage));
    
    }

    protected function sendResponse($data = null, $successMessage=null, $pagination = null, $refreshToken = null){
        
        return $this->successResponse($data , $successMessage, $pagination, $refreshToken);
   
    }


    protected function successResponse($data = null, $successMessage=null, $pagination = null, $refreshToken = null)
    {
       
        if ($data !== null) {
            $this->result = $data;
        }

        if ($successMessage !== null) {
            $this->successMessage = $successMessage;
        }

        if ($pagination !== null) {
            $this->pagination = $pagination;
        }

        if ($refreshToken !== null) {
            $this->refreshToken = $refreshToken;
        }

        $this->isWarning = false;

        return $this->buildResponse();
    }

    protected function warningResponse($data = null, $successMessage=null, $pagination = null, $refreshToken = null)
    {
       
        if ($data !== null) {
            $this->result = $data;
        }

        if ($successMessage !== null) {
            $this->successMessage = $successMessage;
        }

        if ($pagination !== null) {
            $this->pagination = $pagination;
        }

        if ($refreshToken !== null) {
            $this->refreshToken = $refreshToken;
        }

        $this->isWarning = true;

        return $this->buildResponse();
    }

    

    /**
     * Build the response.
     *
     * @return JsonResponse
     */
    private function buildResponse()
    {
        if ($this->isError) {
            $response = [
                'isError' => $this->isError,
                'errorMessage' => $this->errorMessage
            ];
        } elseif ($this->isForbidden) {
            $response = [
                'isForbidden' => $this->isForbidden,
                'forbiddenMessage' => $this->forbiddenMessage
            ];
        } else {
            $response = [
                'success' => !$this->isError,
                'Message' => $this->successMessage
            ];

            if ($this->result !== null) {
                
                $response['result'] = $this->result;
                $response['Message'] = $this->successMessage;
            }

            if (count($this->pagination) > 0) {
                $response['pagination'] = $this->pagination;
            }
        }

        if ($this->refreshToken && Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $response['refreshedToken'] = Jwt::generateToken([
                'id' => $user->id
            ]);
        }
        if($this->isWarning){
            unset($response['success']);
            $response2['warning']=true;
            $response = array_merge($response2, $response);
        }
       
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Return user fault response.
     *
     * @param array $errorMessage
     * @param bool|null $refreshToken
     *
     * @return JsonResponse
     */
    protected function userErrorResponse(array $errorMessage, $refreshToken = null)
    {
        $this->isError = true;
        $this->succcess = false;
        $this->userFault = true;
        $this->errorMessage = $errorMessage;

        if ($refreshToken !== null) {
            $this->refreshToken = $refreshToken;
        }
       
        return $this->buildResponse();
    }

    /**
     * Return application error response.
     *
     * @return JsonResponse
     */
    protected function errorResponse()
    {
        $this->isError = true;
        $this->success = false;
        $this->errorMessage = ['application' => __('Some Thing went wrong, please contact with admin')];

        return $this->buildResponse();
    }

    /**
     * Return access forbidden response.
     *
     * @return JsonResponse
     */
    protected function forbiddenResponse()
    {
        $this->isForbidden = true;
        $this->forbiddenMessage = ['forbidden' => __('Forbidden')];

        return $this->buildResponse();
    }

    public function getLangIdFromLocale()
    {
        return (app()->getLocale() == 'ar') ? 1 : 2;
    }
}
