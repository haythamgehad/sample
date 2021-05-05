<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Models\NotificationType;
use App\Repositories\NotificationTemplateRepository;
use App\Repositories\NotificationTypeRepository;
use App\Services\NotificationTypeService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Response;

/**
 * Class NotificationTypeController
 * @package App\Http\Controllers
 */

class NotificationTypeController extends Controller
{
    private $notificationTemplateRepository;

    private $notificationTypeRepository;

    public function __construct(NotificationTemplateRepository $notificationTemplateRepo, NotificationTypeRepository $notificationTypeRepo)
    {
        $this->notificationTemplateRepository = $notificationTemplateRepo;

        $this->notificationTypeRepository = $notificationTypeRepo;

        $this->notificationTypeService = new NotificationTypeService();

    }

    /**
    * Show Notification Types list
    * GET /notifications-types
    * @return Response
    */
    public function index()
    {
        $user = Auth::user();

        $notificationTypes = $this->notificationTypeRepository->all()->toArray();

        foreach($notificationTypes as $key=>$type){
            $search = array('account_id' => $user->account_id,'type_id'=>$type['id']);
            $notificationTypes[$key]['templates'] = $this->notificationTemplateRepository->all($search, null, null, '*')->toArray();

        }

        return $this->sendResponse($notificationTypes, 'Notification Type retrieved successfully');
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $validator = $this->notificationTypeService->validateCreateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $notificationType = $this->notificationTypeRepository->create($input);

        return $this->sendResponse($notificationType->toArray(), 'Notification Type saved successfully');
    }


 /**
    * Show the specified notificationTemplates.
    * GET /notificationTemplates/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the notificationTemplates.
    */
    public function show(Request $request)
    {
        $user = Auth::user();
        $account_id = $user->account_id;
        $input = $request->all();
       

        $notificationType = NotificationType::where('code', $input['code'])->first();
        if (empty($notificationType)) {
            return $this->sendError('Notification Code not found');
        }
    
        $notification_template = NotificationTemplate::where(['type_id' => $notificationType->id , 'account_id'=> $account_id , $input['channel']=>1])->get();

        if (empty($notification_template)) {
            return $this->sendError('NotificationTemplate not found');
        }

        // $search = array('account_id' => $user->account_id,'type_id'=>$notificationType->id);
        // $notificationType['templates'] = $this->notificationTemplateRepository->all($search, null, null, '*')->toArray();
        return $this->sendResponse($notification_template, 'NotificationTemplate retrieved successfully');
    }




    public function update($id, Request $request)
    {
       
        $validator = $this->notificationTypeService->validateUpdateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $notificationType = $this->notificationTypeRepository->find($id);

        if (empty($notificationType)) {
            return $this->sendError('Notification Type not found');
        }
        $input = $request->all();
        $notificationType = NotificationType::where('code',NotificationType:: $input['code'])->get();

        $notificationType = $this->notificationTypeRepository->update($input, $id);
       
        return $this->sendResponse($notificationType->toArray(), 'Notification Type updated successfully');
    }

    public function destroy($id)
    {
        $notificationType = $this->notificationTypeRepository->find($id);

        if (empty($notificationType)) {
            return $this->sendError('Notification Type not found');
        }

        $notificationType->delete();

        return $this->sendResponse('Notification Type deleted successfully');
    }

}
