<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use App\Repositories\NotificationTemplateRepository;
use App\Services\NotificationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Response;

/**
 * Class NotificationTemplateController
 * @package App\Http\Controllers
 */

class NotificationTemplateController extends Controller
{
    private $notificationTemplateRepository;

    private $notificationTemplateService;

    public function __construct(NotificationTemplateRepository $notificationTemplateRepo)
    {
        $this->notificationTemplateRepository = $notificationTemplateRepo;

        $this->notificationTemplateService = new NotificationTemplateService();
    }

    /**
    * Show Notifications Templates list
    * GET /notificationTemplates
    * @return Response
    */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search=array('account_id'=>$user->account_id);
        $notificationTemplates = $this->notificationTemplateRepository->with('notificationType')->all(
            $search ,
            null, 
            null, 
            '*'
        );
        /*
        $notificationTemplates = $this->notificationTemplateRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );*/

        return $this->sendResponse($notificationTemplates->toArray(), 'Notification Templates retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $input = $request->all();
        if(isset($user->id)){
            $input['creator_id'] = $user->id;
        }
        

        $validator = $this->notificationTemplateService->validateCreateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $notificationTemplate = $this->notificationTemplateRepository->create($input);

        return $this->sendResponse($notificationTemplate->toArray(), 'NotificationTemplate saved successfully');
    }

    /**
    * Show the specified notificationTemplates.
    * GET /notificationTemplates/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the notificationTemplates.
    */
    public function show($id)
    {
        $notificationTemplate = $this->notificationTemplateRepository->with('notificationType')->find($id);

        if (empty($notificationTemplate)) {
            return $this->sendError('NotificationTemplate not found');
        }

        return $this->sendResponse($notificationTemplate->toArray(), 'NotificationTemplate retrieved successfully');
    }

    /**
    * Update the specified notificationTemplates.
    * PUT/PATCH /notificationTemplates/{id}
    * @param int $id
    * @return Response
    * @bodyParam title string required The  title of the notificationTemplates. Example: title1
    * @bodyParam content string required The  content of the notificationTemplates. Example: content 1
    * @bodyParam status int required The  status of the notificationTemplates. Example: 1s
    */
    public function update(Request $request)
    {
        $input = $request->all();
        $validator = $this->notificationTemplateService->validateUpdateRequest($request);
     
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        // $notificationTemplate = $this->notificationTemplateRepository->find($id);

        // if (empty($notificationTemplate)) {
        //     return $this->sendError('NotificationTemplate not found');
        // }
        if (isset($input["title_ar"])) $arbic_input["title"] = $input["title_ar"];
        if (isset($input["content_ar"])) $arbic_input["content"] = $input["content_ar"];
        $template_ar_update = NotificationTemplate::where([ 'id' =>$input["id_ar"] ,'language_id'=>1 ])->update($arbic_input);


        if (isset($input["title"])) $english_input["title"] = $input["title"];
        if (isset($input["content"])) $english_input["content"] = $input["content"];
        $template_en_update = NotificationTemplate::where([ 'id' =>$input["id"]  ,'language_id'=>2 ])->update($english_input);

      //  $notificationTemplate = $this->notificationTemplateRepository->find($id);

        return $this->sendResponse("", 'NotificationTemplate updated successfully');
    }


    /**
    * Post Notification Templates Update ALl.
    * POST /notificationTemplates
    * @return Response
    * @bodyParam templates[0][id] int required The  templates id of the notification. Example: 1
    * @bodyParam templates[0][status]  int required The  templates status of the notification Templates. Example: 1
    * @bodyParam templates[0][title]  string required The  templates status of the notification Templates. Example: test1
    * @bodyParam templates[0][content]  string required The  templates status of the notification Templates. Example: test1
    
    */
    public function updateAll(Request $request)
    {
        $user = Auth::user();

        $input = $request->all();
        
        if(isset($input['templates']) && !empty($input['templates'])){
            foreach($input['templates'] as $key=>$template){
                
                $validator = $this->notificationTemplateService->validateUpdateAllRequest($request, $key);

                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }

                $notificationTemplate = $this->notificationTemplateRepository->find($template['id']);

                if (empty($notificationTemplate)) {
                    return $this->sendError('Notification Type not found');
                }
                
                $this->notificationTemplateRepository->update($template,$template['id']);
            }
        }

        return $this->successResponse('Notification Template Updated successfully');
    }

    public function destroy($id)
    {
        $notificationTemplate = $this->notificationTemplateRepository->find($id);

        if (empty($notificationTemplate)) {
            return $this->sendError('NotificationTemplate not found');
        }

        $notificationTemplate->delete();

        return $this->sendResponse('NotificationTemplate deleted successfully');
    }
}
