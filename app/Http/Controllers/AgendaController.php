<?php

namespace App\Http\Controllers;


use App\Models\Agenda;
use App\Models\Attendee;
use App\Models\Meeting;
use App\Repositories\AgendaRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\AttachmentRepository;
use App\Repositories\AttendeeRepository;

use App\Repositories\AttachmentMediaRepository;
use App\Repositories\AccountRepository;
use App\Services\MediaService;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;

use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\CommitteeTranslation;
use App\Models\MeetingAgenda;

use App\Services\AgendaService;
use App\Services\MeetingService;
use App\Services\NotificationService;

use Ixudra\Curl\Facades\Curl;
use IonGhitun\JwtToken\Jwt;


use Response;

/**
 * Class AgendaController
 * @package App\Http\Controllers
 */

class AgendaController extends Controller
{
    

    private $agendaRepository;

    private $meetingRepository;

    private $attachmentRepository;

    private $attachmentMediaRepository;
    private $accountRepository;
    private $attendeeRepository;
    private $mediaService;

    private $agendaService;

    private $meetingService;

    


    public function __construct(AgendaRepository $agendaRepo,AttendeeRepository $attendeeRepository, MeetingRepository $meetingRepo, AttachmentRepository $attachmentRepo, AccountRepository $accountRepo,  AttachmentMediaRepository $attachmentMediaRepo)
    {
        $this->accountRepository = $accountRepo;

        $this->agendaRepository = $agendaRepo;

        $this->attachmentRepository = $attachmentRepo;

        $this->attachmentMediaRepository = $attachmentMediaRepo;

        $this->meetingRepository = $meetingRepo;
        $this->attendeeRepository = $attendeeRepository;

        $this->agendaService = new AgendaService() ;

        $this->meetingService = new MeetingService() ;
        
        $this->mediaService = new MediaService() ;


    }

    /**
    * Show Agendas list
    * GET /agendas
    * @return Response
    */
    public function index(Request $request)
    {
        $user = Auth::user();

        $agendas = $this->agendaRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        $hasAccess1 = $this->meetingService->hasListAccess($user->id, Permission::AGENDA_CODE);

        $hasAccess2 = $this->meetingService->hasListAccess($user->id, Permission::AGENDA_NOT_WORK_CODE);

        if (!$hasAccess1 && !$hasAccess2){
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($agendas->toArray(), 'Agendas retrieved successfully');
    }

    public function userAgendas(Request $request)
    {
        $user = Auth::user();
        $meetingsIds = $this->getLoggedUserMeetings()->pluck('id')->toArray();
        $agendas = Agenda::with($this->agendaRepository->getRelations('list'))
            ->whereHas('meetings', function($query) use($meetingsIds, $request) {
                if ($request->meeting_id) {
                    $query->where('meeting_agendas.meeting_id', $request->meeting_id);
                }else{
                    $query->whereIn('meeting_agendas.meeting_id', $meetingsIds);
                }
            })
            ->where(function ($query) use ($user) {
                $query->whereRaw("find_in_set('" . $user->id . "',agendas.can_acccess_list)")
                    ->orWhere('can_acccess_list', '=', '')
                    ->orWhereNull('can_acccess_list');
            });
        if ($request->committee_id) {
            $agendas->where('committee_id', $request->committee_id);
        }

        $agendas = $agendas->groupBy('agendas.id')->paginate($request->page_count ?? 20);

        return $this->sendResponse($agendas->toArray(), 'Agendas retrieved successfully');
    }

    public function getLoggedUserMeetings()
    {
        $user = auth()->user();

        return Meeting::where(function ($query2) use ($user) {
            $query2->Where(function ($query) use ($user) {
                $query->whereHas('attendees', function ($query3) use ($user) {
                    return $query3->where('member_id', '=', $user->id);
                })->where('status', '!=', 0);
            })
                ->orWhere(function ($query4) use ($user) {
                    $query4->whereHas('committee', function ($query5) use ($user) {
                        return $query5->where('secretary_id', '=', $user->id)
                            ->orWhere('amanuensis_id', '=', $user->id);
                    });
                })
                ->orWhere(function ($query) use ($user) {
                    $query->whereHas('reports', function ($query) use ($user) {
                        return $query->whereHas('shares', function ($query) use ($user) {
                            return $query->where('shared_to_id', '=', $user->id)
                                ->where('share_status', 2);
                        });
                    })->where('status', 5);
                });
        })->get();
    }

    /**
    * Post Agenda From Clauses.
    * POST /agendas-clauses
    * @return Response
    * @bodyParam meeting_id int required The  ID of the meeting. Example: 1
    * @bodyParam clause_ids list required The  ID of the meeting. Example: 1,2,3,5
    */
    public function storeFromClauses(Request $request)
    {
        
        $user = Auth::user();

        $hasAccess1 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_CODE);

        $hasAccess2 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_NOT_WORK_CODE);
        
        if(!$hasAccess1 && !$hasAccess2 ){
            return $this->forbiddenResponse();
        }

       $validator = $this->agendaService->validateCreateAgendaFromClausesRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();
        
        $input['creator_id']=$user->id;

        $input['account_id']=$user->account_id;

        $meeting = $this->meetingRepository->find($input['meeting_id']);

        $input['committee_id']=$meeting->committee_id;
        $clause_ids = explode(',', $input['clause_ids']);
        foreach($clause_ids as $clause_id){

            $clause = Clause::where('id' , $clause_id)->first();
            $input['clause_id'] = $clause->id;
            $input['title'] = $clause->name;
            $agenda = $this->agendaRepository->create($input);
        }
        
        return $this->sendResponse($agenda, 'Agenda Added successfully');
    }

    /**
    * Post Agenda Fields From Clauses.
    * POST /agendas-fields
    * @return Response
    * @bodyParam agenda_id int required The  ID of the Agenda. Example: 1
    * @bodyParam field_values[0][field_id] int required The  Value of the Field. Example: 1
    * @bodyParam field_values[0][value] int required The  Value of the Field. Example: 1
    */
    public function storeAgendaFields($id, Request $request)
    {
        $user = Auth::user();

        $hasAccess1 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_CODE);

        $hasAccess2 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_NOT_WORK_CODE);
        
        if(!$hasAccess1 && !$hasAccess2 ){
            return $this->forbiddenResponse();
        }

        $this->agendaService->saveAgendaFields($request);
        
    }


    /**
    * Post Agenda.
    * POST /agendas
    * @return Response
    * @bodyParam meeting_id int required The  ID of the meeting. Example: 1
    * @bodyParam title file required The  agenda title  of the Meeting.  Example: test
    * @bodyParam brief file required The  agenda brief  of the Meeting. Example: test
    * @bodyParam assignee_id int required The  agenda assignee_id  of the Meeting. Example: 1  
    * @bodyParam presenter string required The  agenda presenter  of the Meeting. Example: presenter 1
    
    * @bodyParam duration int required The  agenda duration  of the Meeting. Example: 10 
    * @bodyParam is_work_agenda int required The  agenda is_work_agenda  of the Meeting.Example: 1  
    * @bodyParam has_hidden_voting int required The  agenda has_hidden_voting  of the Meeting.Example: 1   
    * @bodyParam has_visable_voting int required The  agenda has_visable_voting  of the Meeting.Example: 1  
    * @bodyParam can_acccess_list list required The  agenda can_acccess_list  of the Meeting.Example: 1,2,3 
    * @bodyParam attachments][0][title] string required The  attachment title  of the Meeting.  Example: test
    * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
    */
    public function store(Request $request)
    {
        if($request->id) {
            $checkAgenda = Agenda::find($request->id);
            if($checkAgenda) {
                $this->update($request, $request->id);
                $agenda = $this->agendaRepository->find($request->id);
                return $this->sendResponse($agenda, 'Agenda Added successfully');
            }
        }
        $user = Auth::user();

        $hasAccess1 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_CODE);

        $hasAccess2 = $this->agendaService->hasCreateAccess($user->id, Permission::AGENDA_NOT_WORK_CODE);
        
        if(!$hasAccess1 && !$hasAccess2 ){
            return $this->forbiddenResponse();
        }

       $validator = $this->agendaService->validateCreateAgendaRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();
        
        $input['creator_id']=$user->id;

        $input['account_id']=$user->account_id;

        $meeting = $this->meetingRepository->find($input['meeting_id']);

        $account = $this->accountRepository->find($meeting->account_id);

        $input['committee_id']=$meeting->committee_id;

        $meetingUrl = '';
        if(isset($input['link'])){
            $meetingUrl = $input['link'].'/meetings/show/'.$meeting->id;
        }


        $agenda = $this->agendaRepository->create($input);
        if ($agenda) {
            $meetingAgendasData = [
                'meeting_id' => $meeting->id,
                'agenda_id' => $agenda->id,
                'original' => 1
            ];
            MeetingAgenda::create($meetingAgendasData);
        }

        if(isset($input['can_acccess_list']) && !empty($input['can_acccess_list'])) {
            $accessList = explode(',', $input['can_acccess_list']);
            foreach ($accessList as $one) {
                if($one != '') {
                    $checkAttendee = Attendee::where('member_id', $one)->where('meeting_id', $input['meeting_id'])->first();
                    $attendeeData = [];
                    if (!$checkAttendee) {
                        $attendeeData['member_id'] = $one;
                        $attendeeData['meeting_id'] = $input['meeting_id'];
                        $attendeeData['is_committee_member'] = 0;
                        $this->attendeeRepository->create($attendeeData);
                    }
                }
            }
        }
       

        if(isset($input['attachments']) && !empty($input['attachments'])){
            foreach($input['attachments'] as $key=>$attachment){
    
                $validator = $this->agendaService->validateAttachmentsRequest($request, $key);
    
                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }
    
                $attachment['meeting_id']=$agenda->meeting_id;
                $attachment['agenda_id']=$agenda->id;
                $attachment['creator_id']=$agenda->creator_id;
                $attachment['media_id']=$input['attachments'][$key]['media_id'];
                $attachment['title']=$input['attachments'][$key]['title'];
                $attachment = $this->attachmentRepository->create($attachment);
                $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();

                $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
               //  $this->attachmentMediaRepository->create(array('media_id'=>$input['attachments'][$key]['media_id'],'attachment_id'=>$attachment->id));
               $moveToPath = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Agenda'; 
               $this->mediaService->moveDirectoryByPath($input['attachments'][$key]['media_id'],  $moveToPath);
            }
            
        }

        $this->meetingService->generateCollectionForMeeting($meeting, $meetingUrl);

        $agenda = $this->agendaRepository->find($agenda->id);

        return $this->sendResponse($agenda, 'Agenda Added successfully');
    }

    /**
    * Show Agendas Details
    * GET /agendas/{id}
    * @param int $id
    * @return Response
    */
    public function show($id)
    {
        $user = Auth::user();

        $agenda = $this->agendaRepository->find($id);

        if (empty($agenda)) {
            return $this->sendError('Agenda not found');
        }

        if($agenda->is_work_agenda){
            $hasAccess = $this->agendaService->hasReadAccess($user->id, $agenda->creator_id, Permission::AGENDA_NOT_WORK_CODE);
        }else{
            $hasAccess = $this->agendaService->hasReadAccess($user->id, $agenda->creator_id, Permission::AGENDA_CODE);
        }
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        if ($agenda->clause_id){
            $agenda['fields'] = $this->agendaService->getFields($agenda->id);
        }

        $notificationService = new NotificationService();
        $notificationService->setNotificationAsRead('agenda_id', $id);


        return $this->sendResponse($agenda, 'Agenda retrieved successfully');
    }
    
    /**
    * Postpone Agendas Details
    * GET /agendas/postpone/{id}
    * @param int $id
    * @return Response
    */
    public function postpone($id)
    {
        $user = Auth::user();

        $agenda = $this->agendaRepository->find($id);

        if (empty($agenda)) {
            return $this->sendError('Agenda not found');
        }

        if($agenda->is_work_agenda){
            $hasAccess = $this->agendaService->hasUpdateAccess($user->id, $agenda->creator_id, Permission::AGENDA_NOT_WORK_CODE);
        }else{
            $hasAccess = $this->agendaService->hasUpdateAccess($user->id, $agenda->creator_id, Permission::AGENDA_CODE);
        }
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $input['status'] = Agenda::STATUS_POSTPONED;
        
        $agenda = $this->agendaRepository->update($input, $id);

        return $this->sendResponse($agenda->toArray(), 'Agenda Postponed successfully');
    }

    /**
    * Update the specified Agenda.
    * PUT/PATCH /agendas/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Agenda.
    * @bodyParam title string required The title  of the Agenda. Example: title
    * @bodyParam brief text required The  brief of the Agenda. Example:  brief
    * @bodyParam content text required The  content of the Agenda. Example:  content
    * @bodyParam can_acccess_list list required The  agenda can_acccess_list  of the Agenda.Example: 1,2,3 
    * @bodyParam attachments[0][title] file required The  media id  of the Media Id member . Example:1 
    * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
    */
    public function update(Request $request,$id)
    {
        
        $user = Auth::user();

        $agenda = $this->agendaRepository->find($id);

        if (empty($agenda)) {
            return $this->sendError('Agenda not found');
        }

        if($agenda->is_work_agenda){
            $hasAccess = $this->agendaService->hasUpdateAccess($user->id, $agenda->creator_id, Permission::AGENDA_NOT_WORK_CODE);
        }else{
            $hasAccess = $this->agendaService->hasUpdateAccess($user->id, $agenda->creator_id, Permission::AGENDA_CODE);
        }
        
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

       $validator = $this->agendaService->validateUpdateAgendaRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();
        
        $agenda = $this->agendaRepository->update($input, $id, true);
        $meetingAgenda = MeetingAgenda::where([
            'meeting_id' => $agenda->meeting_id, 
            'agenda_id' => $agenda->id
            ])->count();

        if($meetingAgenda == 0) {
            MeetingAgenda::create([
                'meeting_id' => $agenda->meeting_id,
                'agenda_id' => $agenda->id,
                'original' => 0
            ]);
        }

        $meeting = $this->meetingRepository->find($agenda->meeting_id);

        $account = $this->accountRepository->find($meeting->account_id);

        if(isset($input['attachments']) && !empty($input['attachments'])){
            foreach($input['attachments'] as $key=>$attachment){
                if(isset($attachment['id']))
                    continue;
                $validator = $this->agendaService->validateAttachmentsRequest($request, $key);
    
                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }
    
                $attachment['meeting_id']=$agenda->meeting_id;
                $attachment['agenda_id']=$agenda->id;
                $attachment['creator_id']=$agenda->creator_id;
                $attachment = $this->attachmentRepository->create($attachment);
                if(isset($input['attachments'][$key]['media_id']) && !empty($input['attachments'][$key]['media_id'])){
                    $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
                    $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                    $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                    // $this->attachmentMediaRepository->create(array('media_id'=>$input['attachments'][$key]['media_id'],'attachment_id'=>$attachment->id));
                    $moveToPath = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Agenda';
                    $this->mediaService->moveDirectoryByPath($input['attachments'][$key]['media_id'], $moveToPath);
            
                }
            }

            $meetingUrl = '';
            if(isset($input['link'])){
                $meetingUrl = $input['link'].'/meetings/show/'.$meeting->id;
            }

            $this->meetingService->generateCollectionForMeeting($meeting, $meetingUrl);
        }

        if(isset($input['can_acccess_list']) && !empty($input['can_acccess_list'])) {
            $accessList = explode(',', $input['can_acccess_list']);
            foreach ($accessList as $one) {
                if($one != ''){
                    $checkAttendee = Attendee::where('member_id', $one)->where('meeting_id', $agenda->meeting_id)->first();
                    $attendeeData = [];
                    if(!$checkAttendee) {
                        $attendeeData['member_id'] = $one;
                        $attendeeData['meeting_id'] = $agenda->meeting_id;
                        $attendeeData['is_committee_member'] = 0;
                        $this->attendeeRepository->create($attendeeData);
                    }
                }
            }
        }

        $agenda = $this->agendaRepository->find($agenda->id);

        return $this->sendResponse($agenda, 'Agenda updated successfully');

    }

    /**
    * Delete Agendas Details
    * Delete /agendas/{id}
    * @param int $id
    * @return Response
    */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        $agenda = $this->agendaRepository->find($id);

        if (empty($agenda)) {
            return $this->sendError('Agenda not found');
        }
        $meeting = $this->meetingRepository->find($agenda->meeting_id);
        
        if($agenda->is_work_agenda){
            $hasAccess = $this->agendaService->hasDeleteAccess($user->id, $agenda->creator_id, Permission::AGENDA_NOT_WORK_CODE);
        }else{
            $hasAccess = $this->agendaService->hasDeleteAccess($user->id, $agenda->creator_id, Permission::AGENDA_CODE);
        }
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $agenda->delete();

        $meetingUrl = '';
        if(isset($request->link)){
            $meetingUrl = $request->link.'/meetings/show/'.$meeting->id;
        }

        $this->meetingService->generateCollectionForMeeting($meeting, $meetingUrl);

        return $this->sendResponse('Agenda deleted successfully');
    }

    public function attachAgendaToMeeting(Request $request)
    {
        try {
            $validator = $this->agendaService->validateAttachAgendaToMeetingRequest($request);
            if (!$validator->passes()) {
                return $this->userErrorResponse($validator->messages()->toArray());
            }

            MeetingAgenda::create([
                'meeting_id' => $request->meeting_id,
                'agenda_id' => $request->agenda_id
            ]);
            return $this->sendResponse([],'Agenda has been attached successfully to meeting');
        } catch (\Exception $e) {
            return $this->sendError('Can not attach agenda to meeting');
        }
    }
}
