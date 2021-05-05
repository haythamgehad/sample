<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Language;


use App\Models\MeetingTimeVote;
use App\Models\MeetingTime;
use App\Models\Meeting;
use App\Models\Action;
use App\Models\Account;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Repositories\UserRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\MeetingTimeRepository;
use App\Repositories\MeetingReportRepository;
use App\Repositories\MeetingTimeVoteRepository;
use App\Repositories\AttachmentRepository;
use App\Repositories\CommitteeMemberRepository;
use App\Repositories\AttendeeRepository;
use App\Services\MeetingService;
use App\Repositories\AgendaRepository;
use App\Repositories\ActionRepository;
use App\Services\DirectoryService;
use App\Services\ActionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;




use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;

use App\Services\CommitteeService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Carbon\Carbon;


/**
 * Class MeetingController
 * @package App\Http\Controllers
 */

class MeetingController extends Controller
{
   
    private $meetingRepository;

    private $meetingTimeRepository;

    private $meetingReportRepository;

    private $meetingTimeVoteRepository;

    private $attachmentRepository;

    private $attendeeRepository;

    private $committeeMemberRepository;

    private $agendaRepository;

    private $actionRepository;

    private $meetingService;

    private $actionService;

    private $userRepository;

    private $directoryService;


    public function __construct(UserRepository $userRepo, MeetingRepository $meetingRepo, 
    MeetingTimeRepository $meetingTimeRepo, MeetingReportRepository $meetingReportRepo, MeetingTimeVoteRepository $meetingTimeVoteRepo, AttendeeRepository $attendeeRepo,
    CommitteeMemberRepository $committeeMemberRepo,AttachmentRepository $attachmentRepo,
    AgendaRepository $agendaRepo,ActionRepository $actionRepo
    )
    {
        $this->meetingRepository = $meetingRepo;

        $this->userRepository = $userRepo;
        
        $this->meetingTimeRepository = $meetingTimeRepo;

        $this->meetingReportRepository = $meetingReportRepo;

        $this->meetingTimeVoteRepository = $meetingTimeVoteRepo;

        $this->attendeeRepository = $attendeeRepo;

        $this->committeeMemberRepository = $committeeMemberRepo;

        $this->agendaRepository = $agendaRepo;

        $this->actionRepository = $actionRepo;

        $this->attachmentRepository = $attachmentRepo;

        $this->meetingService = new MeetingService() ;
        
        $this->actionService = new ActionService() ;

        $this->directoryService = new DirectoryService();

    }

    /**
    * Show Meetings list
    * GET /meetings
    * @return Response
    */
    public function index(Request $request)
    {
        $user = Auth::user();
        $sort = 'desc' ;
      if (isset($request['sort']) && !is_null($request['sort']))
      {
          $sort = $request['sort'];
      }
        $meetings = $this->meetingRepository->with($this->meetingRepository->getRelations('list'))->all(
            $this->meetingRepository->prepareIndexSearchFields($request, $user), 
            null, 
            null, 
            $this->meetingRepository->getFields('list'),
            $sort
        );
        

        $hasAccess = $this->meetingService->hasListAccess($user->id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($meetings->toArray(), 'Meetings retrieved successfully');
    }

    public function createRemoteMeeting($id){
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $account = Account::find($meeting->account_id);

        if(empty($account))
            return $this->sendError('Account not found');
        
        $token = $account->webex_access_token;

        if(!$token)
            return $this->sendError('token not found');

        $remoteMeeting = $this->meetingService->createRemoteMeeting($meeting,$user,$token);
        if($remoteMeeting){
            $this->meetingService->sendStartVideoMeetingNotification($meeting);
            return $this->sendResponse('success', 'Remote Meeting Created');
        }else
            return $this->sendError('failed');
    }

    /**
    * Generate Meeting Reports
    * GET /meetingreports/
    * @param int $id
    * @return Response
    */
    public function reports($id){
        $user = Auth::user();

        $relations = $this->meetingReportRepository->getRelations('item');

        $search = array('meeting_id'=> $id, 'status'=> 1);

        $meeting_reports = $this->meetingReportRepository->with($relations)->all(
            $search, 
            null, 
            null, 
            '*'
        );

        return $this->sendResponse($meeting_reports, 'Meeting Reports Generated successfully');

    }

    /**
    * Generate Meeting Collection
    * GET /meetings/generate-collection/
    * @param int $id
    * @return Response
    */
    public function generateCollection(Request $request, $id)
    {
        $user = Auth::user();
        $meetingUrl = '';
        if($request->get('meetingUrl') && !empty($request->get('meetingUrl'))) {
            $meetingUrl = $request->get('meetingUrl');
        }
       
        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $meetingCollection = $this->meetingService->generateCollection($meeting, $meetingUrl);

        return $this->sendResponse($meetingCollection, 'Meeting Collection Generated successfully');
    }
    
    /**
    * Post Meeting.
    * POST /meetings
    * @return Response
    * @bodyParam publish int required The  publish of the Meeting. Example: 1
    * @bodyParam title string required The  Title of the Meeting. Example: Meeting title 1
    * @bodyParam brief string required The  Title of the Meeting. Example: Meeting brief 1
    * @bodyParam number string required The  number of the Meeting. Example: Meeting number 123
    * @bodyParam quorum int required The  quorum of the Meeting. Example: Meeting 50
    * @bodyParam committee_id int required The  committee_id of the Meeting. Example:  1
    * @bodyParam location_id int required The  location_id of the Location. Example:  1
    * @bodyParam start_at datetime required The  start_at of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam end_at datetime required The  end_at of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam attendees[0][member_id] object required The  attendee member of the Committee. Example: 1  
    * @bodyParam attendees[0][position_id] int required The  attendee position of the Committee. Example: 1 
    * @bodyParam attendees[0][member_id] int required The  attendee member of the Meeting. Example: 1
    * @bodyParam attendees[0][can_acccess_list] list required The  agenda can_acccess_list  of the Meeting. Example: 1,2,3

    * @bodyParam organizers[0][member_id] object required The  member_id. Example: 1  
    * @bodyParam organizers[0][capabilities] list required The  capabilities. Example: 1,2,3
    * @bodyParam organizers[0][expires_at] list required The  expires_at. Example:  2020/07/03 03:40

    * @bodyParam attachments[0][title] file required The  attachment title  of the Meeting.  Example: title   
    * @bodyParam attachments[0][media_id] file required The  attachment  of the Meeting. Example: 1

    * @bodyParam agendas[0][title] string required The  agenda title  of the Meeting. Example: title 
    * @bodyParam agendas[0][brief] string required The  agenda brief  of the Meeting. Example: brief 
    * @bodyParam agendas[0][assignee_id] int required The  agenda assignee_id  of the Meeting. Example: 1 
    * @bodyParam agendas[0][duration] int required The  agenda duration  of the Meeting. 
    * @bodyParam agendas[0][is_work_agenda] int required The  agenda is_work_agenda  of the Meeting. Example: 1 
    * @bodyParam agendas[0][has_hidden_voting] int required The  agenda has_hidden_voting  of the Meeting. Example: 1  
    * @bodyParam agendas[0][has_visable_voting] int required The  agenda has_visable_voting  of the Meeting.  Example: 1 
    * @bodyParam agendas[0][can_acccess_list] list required The  attendees can_acccess_list  of the Meeting.  Example: 1,2,3
    * @bodyParam agendas[0][attachments][0][title] string required The  attachment title  of the Meeting.  Example: title 
    * @bodyParam agendas[0][attachments][0][media_id] int required The  attachment  of the Meeting.  Example: 1 
    * @bodyParam agendas[0][has_voting] int required The  agenda has_voting  of the Meeting. Example: 1

    * @bodyParam agendas[0][actions][0][status] int required The  status of the Action. Example: 1,3
    * @bodyParam agendas[0][actions][0][type_id] int required The  ID of the type_id. Example: 1
    * @bodyParam agendas[0][actions][0][assignee_id] int required The  ID of the assignee_id. Example: 1
    * @bodyParam agendas[0][actions][0][due_date] int required The  ID of the due_date. Example: 2020/07/03
    
    * @bodyParam agendas[0][actions][0][show_to] string required The show_to one of the Action. Example: ALL,MEMBERS,ATTENDEES
    * @bodyParam agendas[0][actions][0][is_private] int required The is_private  of the Action. Example: 1
    * @bodyParam agendas[0][actions][0][voting_visibility] string required The voting_visibility  of the Action. Example: ALL,HIDEN
    * @bodyParam agendas[0][actions][0][minimum_meeting_requests] int required The minimum_meeting_requests  of the Action. Example: 100

    * @bodyParam agendas[0][actions][0][can_change_vote] int required The can_change_vote  of the Action. Example: true,false

    * @bodyParam agendas[0][actions][0][can_change_after_voting] int required The can_change_after_voting  of the Action. Example: true,false
    * @bodyParam agendas[0][actions][0][can_change_after_publish] int required The can_change_after_publish  of the Action. Example: true,false

    * @bodyParam agendas[0][actions][0][quorum] int required The quorum  of the Action. Example: 50
    
    * @bodyParam agendas[0][actions][0][title] string required The title  of the Action. Example: title
    * @bodyParam agendas[0][actions][0][brief] text required The  brief of the Action. Example:  brief
    * @bodyParam agendas[0][actions][0][content] text required The  content of the Action. Example:  content

    */
    public function storeMeeting(Request $request){
       return $this->store($request);
    }

    /**
    * Post MeetingsAssociations.
    * POST /meetings-associations
    * @return Response
    * @bodyParam is_association int required The  is_association of the Meeting. Example: 1
    * @bodyParam publish int required The  publish of the Meeting. Example: 0
    * @bodyParam title string required The  Title of the Meeting. Example: Meeting title 1
    * @bodyParam brief string required The  Title of the Meeting. Example: Meeting brief 1
    * @bodyParam number string required The  number of the Meeting. Example: Meeting number 123
    * @bodyParam quorum int required The  quorum of the Meeting. Example: Meeting 50
    * @bodyParam committee_id int required The  committee_id of the Meeting. Example:  1
    * @bodyParam location_id int required The  location_id of the Location. Example:  1
    * @bodyParam start_at datetime required The  start_at of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam end_at datetime required The  end_at of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam attendees[0][member_id] object required The  attendee member of the Committee. Example: 1  
    * @bodyParam attendees[0][position_id] int required The  attendee position of the Committee. Example: 1 
    * @bodyParam attendees[0][member_id] int required The  attendee member of the Meeting. Example: 1
    * @bodyParam attendees[0][can_acccess_list] list required The  agenda can_acccess_list  of the Meeting. Example: 1,2,3

    * @bodyParam organizers[0][member_id] object required The  member_id. Example: 1  
    * @bodyParam organizers[0][capabilities] list required The  capabilities. Example: 1,2,3
    * @bodyParam organizers[0][expires_at] datetime required The  end_at of the Meeting. Example:  2020/07/03 03:40

    * @bodyParam attachments[0][title] file required The  attachment title  of the Meeting.  Example: title   
    * @bodyParam attachments[0][media_id] file required The  attachment  of the Meeting. Example: 1

    * @bodyParam agendas[0][title] string required The  agenda title  of the Meeting. Example: title 
    * @bodyParam agendas[0][brief] string required The  agenda brief  of the Meeting. Example: brief 
    * @bodyParam agendas[0][assignee_id] int required The  agenda assignee_id  of the Meeting. Example: 1 
    * @bodyParam agendas[0][duration] int required The  agenda duration  of the Meeting. 
    * @bodyParam agendas[0][is_work_agenda] int required The  agenda is_work_agenda  of the Meeting. Example: 1 
    * @bodyParam agendas[0][has_hidden_voting] int required The  agenda has_hidden_voting  of the Meeting. Example: 1  
    * @bodyParam agendas[0][has_visable_voting] int required The  agenda has_visable_voting  of the Meeting.  Example: 1 
    * @bodyParam agendas[0][can_acccess_list] list required The  attendees can_acccess_list  of the Meeting.  Example: 1,2,3
    * @bodyParam agendas[0][attachments][0][title] string required The  attachment title  of the Meeting.  Example: title 
    * @bodyParam agendas[0][attachments][0][media_id] int required The  attachment  of the Meeting.  Example: 1 
    
    
    * @bodyParam agendas[0][has_voting] int required The  agenda has_voting  of the Meeting. Example: 1

    * @bodyParam agendas[0][actions][0][status] int required The  status of the Action. Example: 1,3
    * @bodyParam agendas[0][actions][0][type_id] int required The  ID of the type_id. Example: 1
    * @bodyParam agendas[0][actions][0][assignee_id] int required The  ID of the assignee_id. Example: 1
    * @bodyParam agendas[0][actions][0][due_date] int required The  ID of the due_date. Example: 2020/07/03 03:40
    
    * @bodyParam agendas[0][actions][0][show_to] string required The show_to one of the Action. Example: ALL,MEMBERS,ATTENDEES
    * @bodyParam agendas[0][actions][0][is_private] int required The is_private  of the Action. Example: 1
    * @bodyParam agendas[0][actions][0][voting_visibility] string required The voting_visibility  of the Action. Example: ALL,HIDEN
    * @bodyParam agendas[0][actions][0][minimum_meeting_requests] int required The minimum_meeting_requests  of the Action. Example: 100

    * @bodyParam agendas[0][actions][0][can_change_vote] int required The can_change_vote  of the Action. Example: true,false

    * @bodyParam agendas[0][actions][0][can_change_after_voting] int required The can_change_after_voting  of the Action. Example: true,false
    * @bodyParam agendas[0][actions][0][can_change_after_publish] int required The can_change_after_publish  of the Action. Example: true,false

    * @bodyParam agendas[0][actions][0][quorum] int required The quorum  of the Action. Example: 50
    
    * @bodyParam agendas[0][actions][0][title] string required The title  of the Action. Example: title
    * @bodyParam agendas[0][actions][0][brief] text required The  brief of the Action. Example:  brief
    * @bodyParam agendas[0][actions][0][content] text required The  content of the Action. Example:  content
    
    
    */
    public function storeAssociation(Request $request){
        return $this->store($request);
    }

    /**
    * Post Time.
    * POST /times
    * @return Response
    * @bodyParam title string required The  Title of the Meeting. Example: Meeting title 1
    * @bodyParam brief string required The  Title of the Meeting. Example: Meeting brief 1
    * @bodyParam committee_id int required The  committee_id of the Meeting. Example:  1
    * @bodyParam times[0][start_at] datetime required The  Start at One  of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam times[0][end_at] datetime required The  End at One  of the Meeting. Example:  2020/07/03 03:40
    * @bodyParam time_voting_end_at datetime required The  f of the Meeting. Example:  2020/07/03 03:40
    
    
    */
    public function storeTime(Request $request){
        return $this->store($request);
    }

    public function store(Request $request)
    {
        $isWarning = false;

        $user = Auth::user();

        $input=$request->all();

        $input['type']=$request->route()[1]['type'];

        $hasAccess = $this->meetingService->hasCreateAccess($user->id, Permission::MEETING_CODE);
        if(!$hasAccess){
            return $this->forbiddenResponse();
        }

        if( $request->route()[1]['type']=='time'){

            $validator = $this->meetingService->validateCreateTimesRequest($request);
            $input['status']=MEETING::STATUS_VOTE;
        }elseif($request->route()[1]['type']=='association'){
            $validator = $this->meetingService->validateCreateAssociationRequest($request);
        }elseif($request->route()[1]['type']=='meeting'){
            $validator = $this->meetingService->validateCreateRequest($request);
        }
       

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }
            
        $input['account_id']=$user->account_id;
        
        $input['creator_id']=$user->id;

        $committee = Committee::find($input['committee_id']);

        if($committee->type == 'Associations'){
            $committeeMeetingsCount = Meeting::where('committee_id', $committee->id)->count();
            $input['number'] = $committeeMeetingsCount+1;
        }
        $input['meeting_key'] = Str::random(32);
        $meeting = $this->meetingRepository->create($input);

        $regulation = null;
        if( $request->route()[1]['type']!='time'){
            $regulation = $this->meetingService->isValidInviteMeetingRegulations($meeting);
        }

        if($regulation && isset($regulation->status) &&  $regulation->status == 'error'){
            $meeting->delete();
            return $this->sendError($regulation->message); 
        }

        $this->directoryService->createMeeting($meeting, $request);

        $return = $this->meetingService->saveMeetingRelatedDetails($meeting, $request);
       if(isset($return['isWarning']) && $return['isWarning']==true){
           $isWarning = true;
           $warrningMessage = $return['message'];
       }
        // if( $request->route()[1]['type']=='time'){
        //     $this->meetingService->notifyMemberToTimeVote($meeting);
        // }

        if( $request->route()[1]['type']=='time'){

            $relations = $this->meetingRepository->getRelations('time');
            $columns = $this->meetingRepository->getFields('time');

        }elseif($request->route()[1]['type']=='association'){

            $relations = $this->meetingRepository->getRelations('association');
            $columns = $this->meetingRepository->getFields('association');

        }elseif($request->route()[1]['type']=='meeting'){

            $relations = $this->meetingRepository->getRelations('meeting');
            $columns = $this->meetingRepository->getFields('meeting');

        }

        if($request->has('publish') && $input['publish'] == 1){
            $this->publish($meeting->id);
        } else {
            $meetingUrl = '';
            if(isset($input['link'])){
                $meetingUrl = $input['link'].'/meetings/show/'.$meeting->id;
            }

            $this->meetingService->generateCollectionForMeeting($meeting, $meetingUrl);
        }
        
        $meeting = $this->meetingRepository->with($relations)->find($meeting->id,$columns);
        if($isWarning){
            return $this->warningResponse($meeting, 'Meeting saved successfully '.$warrningMessage);

        }else{
            return $this->sendResponse($meeting->toArray(), 'Meeting saved successfully');
        }
        
    }

    public function updateTimeMeeting(Request $request, $id)
    {
        $isWarning = false;

        $user = Auth::user();

        $input=$request->all();

        $meeting = Meeting::find($id);

        $input['type']=$request->route()[1]['type'];

        $validator = $this->meetingService->validateCreateRequest($request);
       

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $committee = Committee::find($input['committee_id']);

        
        $input['meeting_key'] = Str::random(32);
        
        $meeting->update([
            'number' =>  $input['number'],
            'committee_id' =>  $input['committee_id'],
            'type' =>  $input['type'],
            'meeting_key' =>  $input['meeting_key'],
            'start_at' =>  $input['start_at'],
            'end_at' =>  $input['end_at']
        ]);

        $regulation = $this->meetingService->isValidInviteMeetingRegulations($meeting);

        if($regulation && isset($regulation->status) &&  $regulation->status == 'error'){
            $meeting->delete();
            return $this->sendError($regulation->message); 
        }

        $this->directoryService->createMeeting($meeting, $request);
        
        $relations = $this->meetingRepository->getRelations('meeting');
        $columns = $this->meetingRepository->getFields('meeting');

        if($request->has('publish') && $input['publish'] == 1){
            $this->publish($meeting->id, $request);
        } else {
            $meetingUrl = '';
            if(isset($input['link'])){
                $meetingUrl = $input['link'].'/meetings/show/'.$meeting->id;
            }

            $this->meetingService->generateCollectionForMeeting($meeting, $meetingUrl);
        }
        
        $meeting = $this->meetingRepository->with($relations)->find($meeting->id,$columns);
        if($isWarning){
            return $this->warningResponse($meeting, 'Meeting saved successfully '.$warrningMessage);

        }else{
            return $this->sendResponse($meeting->toArray(), 'Meeting saved successfully');
        }
        
    }

    /**
    * Show Meeting Details
    * GET /meetings/{id}
    * @param int $id
    * @return Response
    */
    public function showMeeting($id,Request $request){
        return $this->show($id, $request);
    }

    /**
    * Show Time Details
    * GET /times/{id}
    * @param int $id
    * @return Response
    */
    public function showTime($id,Request $request){
        return $this->show($id, $request);
    }

    public function show($id,Request $request)
    {
        $user = Auth::user();
        $meetingInfo = $this->meetingRepository->find($id);
        $GLOBALS['meeting_id'] = $id;

        $checkMeeting = $this->meetingRepository->all(['id' => $id, 'account_id' => $user->account_id])->first();

        if(!$checkMeeting) {
            return $this->forbiddenResponse();
        }

        if($meetingInfo->committee->type == 'Associations') {
            $committeeBoard = Committee::where('parent_id', $meetingInfo->committee_id)->first();
            $member = CommitteeMember::where('member_id', $user->id)
                ->whereIn('committee_id', [$meetingInfo->committee_id, $committeeBoard->id])->first();

            $user->shares = $member ? $member->shares : 0;
        }
        
        if($meetingInfo->committee->type == 'Associations') {
            $relation = $this->meetingRepository->getRelations('association');
            $columns = $this->meetingRepository->getFields('association');
        }else{
            $relation = $this->meetingRepository->getRelations($request->route()[1]['type']);
            $columns = $this->meetingRepository->getFields($request->route()[1]['type']);     
        }
        
        if($request->route()[1]['type'] == 'meeting') {
            if ($user->id !== $meetingInfo->committee->amanuensis_id && $user->id !== $meetingInfo->committee->secretary_id && !$this->meetingService->checkIsGovernance($meetingInfo->committee_id,$user)) {
                // $reportRelations = [
                //     'mySharedReports',
                //     'mySharedReports.myNotices',
                //     'mySharedReports.shares',
                //     'mySharedReports.signatures',
                //     'mySharedReports.media:id',
                // ];
                $reportRelations['reports'] = function($query) use($user){
                    $query->whereHas('shares', function ($query2) use ($user){
                        return $query2->where('shared_to_id', '=', $user->id);
                    });
                };
                $reportRelations = array_merge($reportRelations, [
                    'reports.shares',
                    'reports.notices',
                    'reports.signatures',
                    'reports.media:id,type',
                ]);
            } else {
                $reportRelations = [
                    'reports',
                    'reports.shares',
                    'reports.notices',
                    'reports.signatures',
                    'reports.media:id,type',
                ];
            }

            $relation = array_merge($relation, $reportRelations);
        }
        $meeting = $this->meetingRepository->with($relation)->find($id,$columns);
        $meeting->user = $user;
        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        if($meeting->type == 'time') {
            $votedDate = $this->meetingService->getMaxTimeVotedForMeeting($meeting);
            if($votedDate) {
                $meeting->start_at = $votedDate->start_at['full'];
                $meeting->end_at = $votedDate->end_at['full'];
                Meeting::where('id', $meeting->id)->update([
                    'start_at' =>$votedDate->start_at['full'], 
                    'end_at' =>$votedDate->end_at['full'] 
                ]);
            }
        }


        $hasAccess = $this->meetingService->hasReadAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $notificationService = new NotificationService();
        $notificationService->setNotificationAsRead('meeting_id', $id);

        return $this->sendResponse($meeting->toArray(), 'Meeting retrieved successfully');
    }

    public function sendAcceptTermsCode(int $meetingId)
    {
        if(!$this->meetingRepository->find($meetingId)) {
            return $this->sendError('Meeting not found');
        }

        if(!$this->meetingService->sendAcceptTermsCode($meetingId)) {
            return $this->sendError('Can not send accept terms');
        }

        return $this->sendResponse('Accept Code has been sent Successfully');
    }

    public function getAcceptTerms(int $meetingId)
    {
        $user = Auth::user();
        if (!$this->meetingRepository->find($meetingId)) {
            return $this->sendError('Meeting not found');
        }

        $languageId = $user->language_id ?? $this->getLangIdFromLocale();
        $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
        Lang::setLocale($languageCode);

        $accountName = ($languageId == 1) ? optional($user->account)->name_ar : optional($user->account)->name_en;
        $userName = optional($user->translation)->name;
        $nationality = optional($user->nationality)->name;

        $terms = view('meeting.accept_terms')->with([
            'user' => $user,
            'accountName' => $accountName,
            'userName' => $userName,
            'nationality' => $nationality,
        ])->render();

        return $this->sendResponse(['terms' => $terms], 'Meeting Accept Terms');
    }

    /**
    * Post Vote on Meeting Time
    * POST /times/vote/{id}
    * @param Number $id
    * @return Response
    */
    public function voteOnTime($id)
    {
        $user = Auth::user();

       // $idsList=explode(',',$ids);

       // foreach($idsList as $key=>$id){
        $meetingTime = $this->meetingTimeRepository->find($id);

        $meeting = $this->meetingRepository->with('committee')->find($meetingTime->meeting_id);

        if (empty($meetingTime)) {
            return $this->sendError('Meeting Time not found');
        }
       

        if ($meeting->time_voting_end_at <  Carbon::now()->format('Y-m-d')) {
            return $this->sendError('Voting is Closed');
        }

       
        $input['creator_id'] = $user->id;

        $input['time_id'] = $id;


         $oldVotes = MeetingTime::where('meeting_id', '=', $meetingTime->meeting_id)->pluck('id');
         $arrCount = count($oldVotes);
         
         $i = 0;
         $j = 0;
         for($i ; $i < $arrCount ; $i++)
         {
            $meetingTimeVoting = MeetingTimeVote::where('time_id',$oldVotes[$i])->where('creator_id',$user->id)->delete();

         } 
      
        $meetingTimeVote = $this->meetingTimeVoteRepository->create($input);

        for($j ; $j < $arrCount ; $j++)
        {
           $meetingTimeVote = MeetingTimeVote::where('time_id',$oldVotes[$j])->get();
           $input['votes_count'] = count( $meetingTimeVote);
           $update = $this->meetingTimeRepository->update($input, $oldVotes[$j]);
        }


        $meetingTime =  $this->meetingTimeRepository->find($id);

        if($meeting->committee->secretary_id==$user->id){
            // $input['status'] = 0 ;
            $this->meetingTimeRepository->update(['status' => 0],$id);
        }

        if($oldVotes){
         
            // $meetingTimeVote = MeetingTimeVote::where('time_id',$id)->get();
            // $input['votes_count'] = count( $meetingTimeVote);
            // $this->meetingTimeRepository->update($input, $id);
            $votes_counts = MeetingTime::where('meeting_id', '=', $meetingTime->meeting_id)->pluck('votes_count');
           $count= array_sum($votes_counts->toArray());
          
           $this->meetingService->updateAllVotingTimesCounts($meetingTime->meeting_id,$count);
            
        }

     //   $this->meetingService->updateAllVotingTimesCounts($meetingTime->meeting_id);

        $meetingTime =  $this->meetingTimeRepository->with('votes')->find($id);

        return $this->sendResponse($meetingTime,'Meeting Time Vote Added successfully');

    }

    /**
    * Add Meeting time options.
    * PUT/PATCH /meetings/{id} or meetings-associations/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Meeting.
    * @bodyParam times[0][start_at] datetime required The  Start at One  of the Meeting. Example 2020/07/03 03:40 
    * @bodyParam times[0][end_at] datetime required The  End at One  of the Meeting.  Example 2020/07/03 03:40 
    */
    public function addTime($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        $input['type']=$request->route()[1]['type'];
       
        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        $this->meetingService->saveTimes($meeting, $request);

        $relations = $this->meetingRepository->getRelations('time');

        $columns = $this->meetingRepository->getFields('time');
        
        $meeting = $this->meetingRepository->with($relations)->find($meeting->id,$columns);

        return $this->sendResponse($meeting->toArray(), 'Meeting saved successfully');
    }

    /**
    * Update the specified Meeting or Associations.
    * PUT/PATCH /meetings/{id} or meetings-associations/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Meeting Time.
    */
    public function removeTime($id)
    {
        $user = Auth::user();

        $meetingtime = $this->meetingTimeRepository->find($id);

        if (empty($meetingtime)) {
            return $this->sendError('Meeting Timenot found');
        }
        
        $meetingtime->delete();

        return $this->sendResponse('Meeting Time deleted successfully');
    }

    /**
    * Update the specified Meeting.
    * PUT/PATCH /meetings/{id} 
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Meeting.
    * @bodyParam publish int required The  publish of the Meeting. Example: 0
    * @bodyParam title string required The  title of the Meeting. Example: Meeting title
    * @bodyParam brief string required The  title of the Meeting. Example: Meeting brief
    * @bodyParam number string required The  number of the Meeting. Example: Meeting number 1
    * @bodyParam quorum int required The  quorum of the Meeting. Example: Meeting 50
    * @bodyParam committee_id int required The  committee_id of the Committee. Example:  1
    * @bodyParam notify_member int required The  notify_member of the Meeting. Example:  1
    * @bodyParam location_id int required The  location_id of the Location. Example:  1
    * @bodyParam start_at date required The  start_at of the Committee. Example:  2020/03/22
    * @bodyParam end_at date required The  end_at of the Committee. Example:  2020/03/24
    */
    public function updateMeeting($id, Request $request){

       return $this->update($id, $request);
    }

    /**
    * Update the specified Association Meeting.
    * PUT/PATCH /meetings-associations/{id} 
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Meeting.
    
    * @bodyParam is_association int required The  is_association of the Meeting. Example: 1
    * @bodyParam publish int required The  publish of the Meeting. Example: 0
    * @bodyParam title string required The  title of the Meeting. Example: Meeting title
    * @bodyParam brief string required The  title of the Meeting. Example: Meeting brief
    * @bodyParam number string required The  number of the Meeting. Example: Meeting number 1
    * @bodyParam quorum int required The  quorum of the Meeting. Example: Meeting 50
    * @bodyParam committee_id int required The  committee_id of the Committee. Example:  1
    * @bodyParam notify_member int required The  notify_member of the Meeting. Example:  1
    * @bodyParam location_id int required The  location_id of the Location. Example:  1
    * @bodyParam start_at date required The  start_at of the Committee. Example:  2020/03/22 02:30
    * @bodyParam end_at date required The  end_at of the Committee. Example:  2020/03/24 02:30
    */
    public function updateAssociation($id, Request $request){

       return $this->update($id, $request);
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        $input['type']=$request->route()[1]['type'];
       
        $meeting = $this->meetingRepository->with(['committee','committee.governances'])->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        if($request->route()[1]['type']=='association'){
            $validator = $this->meetingService->validateUpdateAssociationRequest($request);
        }elseif($request->route()[1]['type']=='meeting'){
            
            $validator = $this->meetingService->validateUpdateRequest($request);
        }

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        if ($meeting->status==Meeting::STATUS_CANCELED || $meeting->status==Meeting::STATUS_FINISHED) {
            return $this->sendError('You can not edit this meeting');
        }

        if ($meeting->creator_id !== $user->id && !$this->meetingService->checkIsGovernance($meeting->committee_id,$user)) {
            return $this->forbiddenResponse();
        }

        if($request->has('title')){
            $directory = $this->directoryService->getMeetingDirectory($meeting);
            $this->directoryService->rename($directory, $input['title']);
        }

        $meeting = $this->meetingRepository->update($input, $id);

        $this->meetingService->updateCounts($meeting->id);

        $emailLink = '';
        if(isset($input['link'])) {
            $emailLink = $input['link'] . '/meetings/show/' . $meeting->id;
        }

        if($request->has('notify_member')){
            $this->meetingService->updateMeetingNotification($meeting, $emailLink);
        }

        if($request->has('publish') && $input['publish'] == 1){
            $this->publish($meeting->id);
        }

        $relations = $this->meetingRepository->getRelations('meeting');

        $columns = $this->meetingRepository->getFields('meeting');

        $meeting = $this->meetingRepository->with($relations)->find($meeting->id,$columns);

        return $this->sendResponse($meeting, 'Meeting updated successfully');
    }

    /**
    * Finish Meeting Details
    * POST /meetings/finish/{id}
    * @param int $id
    * @return Response
    */
    public function finish( $id, Request $request)
    {
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $regulation = $this->meetingService->isValidFinishRegulations($meeting);

        if(isset($regulation->status) &&  $regulation->status == 'error'){
            return $this->sendError($regulation->message); 
        }

        if($this->meetingService->finishMeeting($meeting, $request)) {
            $this->meetingService->finishMeetingNotifications($meeting);
            $this->meetingService->sendNotificationToAbsentAttendee($meeting);
        }

        $relation = $this->meetingRepository->getRelations('meeting');
        $columns = $this->meetingRepository->getFields('meeting');
        
        $meeting = $this->meetingRepository->with($relation)->find($id,$columns);

        if(isset($regulation->status) &&  $regulation->status == 'warning'){
            return $this->warningResponse($meeting, 'Meeting Finished successfully '.$regulation->message);
           }else{
            return $this->sendResponse($meeting,'Meeting Finished successfully');
        }
    } 

    
    /**
    * Ministry Approved Meeting Details
    * Ministry Approved /meetings/ministry-approved/{id}
    * @param int $id
    * @return Response
    */
    public function ministryApproved( $id)
    {
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $this->meetingService->ministryApproved($meeting);

        return $this->sendResponse('Meeting Ministry Approved successfully');
    } 

    /**
    * Invite Attendees Details
    * POST /meetings/invite/{id}
    * @param int $id
    * @return Response
    */
    public function invite( Request $request, $id)
    {
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $regulation = $this->meetingService->isValidInviteMeetingRegulations($meeting);

        if(isset($regulation->status) &&  $regulation->status == 'error'){
            return $this->sendError($regulation->message); 
        }

        $emailLink = '';
        if ($request->link) {
            $emailLink = $request->link . '/meetings/during-meeting/' . $meeting->id;
        }

        $this->meetingService->InviteAttendeesNotification($meeting, $emailLink);

        if(isset($regulation->status) &&  $regulation->status == 'warning'){
            return $this->warningResponse($meeting, 'Meeting Invitation successfully '.$regulation->message);
           }else{
            return $this->sendResponse($meeting, 'Meeting Invitation successfully');
           }

       
        
    } 
    /**
    * Cancel Meeting Details
    * Cancel /meetings/cancel/{id}
    * @param int $id
    * @return Response
    */
    public function cancel( $id)
    {
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        if ($meeting->status==Meeting::STATUS_FINISHED) {
            return $this->sendError('You can not Cancel this meeting');
        }
        
        $input['status'] = Meeting::STATUS_CANCELED;

        $meeting = $this->meetingRepository->update($input, $id);

        $this->meetingService->cancelMeetingNotification($meeting);

        return $this->sendResponse('Meeting Canceled successfully');
    }  

    /**
    * Publish Meeting Details
    * GET /meetings/publish/{id}
    * @param int $id
    * @return Response
    */
    public function publish( $id, Request $request )
    {
        $input = $request->all();
        // add meeting url to the parameters and generate link text for the (url)
        // $languageId = app()->getLocale();
        // $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
        // //To do setup globally
        // Lang::setLocale($languageCode);
        $user = Auth::user();

        $relations = $this->meetingRepository->getRelations('meeting');
        $columns = $this->meetingRepository->getFields('meeting');
        $meeting = $this->meetingRepository->with($relations)->find($id,$columns);
        // $meeting = $this->meetingRepository->find($id);

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        if ($meeting->status==Meeting::STATUS_FINISHED) {
            return $this->sendError('You can not Publish this meeting');
        }

        // check if regulation

        $regulation = $this->meetingService->isValidInviteMeetingRegulations($meeting);

        if(isset($regulation->status) &&  $regulation->status == 'error'){
            return $this->sendError($regulation->message); 
        }

        if(isset($regulation->status) &&  $regulation->status == 'warning' && !isset($input['pass_warnings'])){
            return $this->warningResponse($meeting, $regulation->message);
        }

        if ($meeting->status==Meeting::STATUS_PUBLISHED) {
            return $this->sendError('Meeting already published');
        }
        

        $input['status'] = Meeting::STATUS_PUBLISHED;
        $meeting = $this->meetingRepository->update($input, $id);

        $meeting_url = '';
        $secondMeetingUrl= '';
        if(isset($input['link'])){
            $meeting_url = $input['link'].'/meetings/show/'.$meeting->id.'?accept=true';
            $secondMeetingUrl = $input['link'].'/meetings/show/'.$meeting->id.'?accept=false';
        }
       // send the meeting url here with the parameters and text for the link
       // $this->meetingService->publishMeetingNotification($meeting ,$meeting_url,$link_text);

        $attendeeData = ['meeting_id'=>$id,'status'=>2];

        $attendeeMe = $this->attendeeRepository->all(array('meeting_id'=>$id,'member_id'=>$user->id), null, null, '*')->first();
        if(isset($attendeeMe->id) )
        {
            $this->attendeeRepository->update($attendeeData, $attendeeMe->id);
            $this->meetingService->updateCounts($meeting->id);
        }
        
        if($meeting->meeting_association_type === 'Normal' || $meeting->meeting_association_type === 'Upnormal'){
            $meeting_url = $input['link'].'/meetings/association/show/'.$meeting->id;
            $secondMeetingUrl = $input['link'].'/meetings/show/'.$meeting->id.'?accept=false';
            $this->meetingService->publishAssociationMeetingNotification($meeting ,$meeting_url, $secondMeetingUrl);
        }else{
            $this->meetingService->publishMeetingNotification($meeting ,$meeting_url, $secondMeetingUrl);
        }
        
        $meeting = $this->meetingRepository->with($relations)->find($meeting->id,$columns);
        return $this->sendResponse($meeting->toArray(),__('Meeting Published successfully'));
    }  

    /**
    * Start Meeting Details
    * POST /meetings/start/{id}
    * @bodyParam is_valid_quorum int required The  is_valid_quorum of the Meeting if i need to start the meeting even the quorum is not valid make it 0 . Example: 1
    * @param int $id
    * @return Response
    */
    public function start( $id, Request $request)
    {
        $input = $request->all();

        $user = Auth::user();

        $meeting = $this->meetingRepository->with(['agendas','actions', 'committee'])->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        if ($meeting->status!=Meeting::STATUS_PUBLISHED) {
            return $this->sendError('You can not Start this meeting');
        }

        $validator = $this->meetingService->validateStartRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $regulation = null;
        if($meeting->committee->type != 'Associations') {
            $regulation = $this->meetingService->isValidStartMeetingRegulations($meeting);
        }
        
        if(isset($regulation->status) &&  $regulation->status == 'error'){
            return $this->sendError($regulation->message); 
        }
        
        $meeting = $this->meetingRepository->find($id);
        if($meeting->committee->type == 'Associations') {
            $email_link = $input['link'].'/meetings/association/during-meeting/'.$meeting->id;
        } else {
            $email_link = $input['link'].'/meetings/during-meeting/'.$meeting->id;
        }
        
        $this->meetingService->startMeeting($meeting, $input);
        $this->meetingService->startMeetingNotifications($meeting ,$email_link);
        $this->meetingService->InviteAttendeesNotification($meeting, $email_link);

        if(isset($regulation->status) &&  $regulation->status == 'warning'){
            return $this->warningResponse($meeting, 'Meeting Started with Warning '.$regulation->message);
           }else{
            return $this->sendResponse($meeting, 'Meeting Started successfully');
        }
    }
    /**
    * Join Meeting Details
    * GET /meetings/join/{id}
    * @param int $id
    * @return Response
    */
    public function join( $id, Request $request)
    {
        $input = $request->all();

        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }

        if ($meeting->status!=Meeting::STATUS_STARTED) {
            return $this->sendError('You can not Join this meeting');
        }

        $this->meetingService->joinMeeting($meeting, $user);

        return $this->sendResponse('Meeting Joined successfully');
    } 
    
    /**
    * Attendee Member 
    * GET /meetings/attendee/{id}
    * @bodyParam member_id int required The  member_id of the Meeting. Example: 1
    * @param int $id
    * @return Response
    */
    public function attendeeMemberMeeting( $id, Request $request)
    {
        $input = $request->all();
        
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }


        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meeting);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        if ($meeting->status!=Meeting::STATUS_PUBLISHED && $meeting->status!=Meeting::STATUS_STARTED) {
            return $this->sendError('Meeting status must be published or started');
        }

        $validator = $this->meetingService->validateattendeeMemberRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $this->meetingService->attendeeMemberMeeting($meeting, $input);

        $actions = Action::where('meeting_id',$id)->where('status','10')->get();
        foreach($actions as $action)
        {
            $this->actionService->updateCounts($action->id);
        }

        return $this->sendResponse('Member Attendeed for Meeting successfully');
    } 
    /**
    * Delete Meeting Details
    * Delete /meetings/{id}
    * @param int $id
    * @return Response
    */
    public function destroy( $id)
    {
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($id);

        $hasAccess = $this->meetingService->hasDeleteAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess){
            return $this->forbiddenResponse();
        }

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }
        
        $meeting->delete();

        return $this->sendResponse('Meeting deleted successfully');
    }  

    /**
    * Delete Meeting Details
    * Delete /meetings-report-content/{id}
    * @param int $id
    * @return Response
    */
    public function getReportContent($id){

        $meeting = $this->meetingRepository->find($id);
        
        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }
        // make content equal to meeting object from database and 
        $content = $this->meetingService->createReportContentfromMettingDetails($meeting);
       
 
     //   $members = CommitteeMember::with('member.translation','position','membership')->where('committee_id',$meeting->committee_id)->get();


        return view('meeting.test')->with(['meeting' => $meeting  ])->withHeaders('X-Frame-Options', 'ALLOWALL');
 
       

      //  return view('meeting.test')->with(['meeting' => $meeting , 'members' => $members ])->withHeaders('X-Frame-Options', 'ALLOWALL');

        // return response($content)
        //     ->header('Content-Type', 'text/html');
    }

    /**
    * Get Meeting Details
    * Get /meetings-main-details/{id}
    * @param int $id
    * @return Response
    */
    public function getMeetingMainDetails($id){

        $meeting = $this->meetingRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting not found');
        }
        $content = $this->meetingService->getMeetingMainDetails($meeting);
        echo $content;
    }

    /**
    * Get Agenda Details
    * Get /agenda-main-details/{id}
    * @param int $id
    * @return Response
    */
    public function getAgendaMainDetails($id){

        $agenda = $this->agendaRepository->find($id);

        if (empty($agenda)) {
            return $this->sendError('Agenda not found');
        }
        $content = $this->meetingService->getAgendaMainDetails($agenda);
        echo $content;
    }

    /**
    * Get Attachment Details
    * Get /attacchment-main-details/{id}
    * @param int $id
    * @return Response
    */
    public function getAttachmentMainDetails($id){

        $attachment = $this->attachmentRepository->find($id);

        if (empty($attachment)) {
            return $this->sendError('Attachment not found');
        }
        $content = $this->meetingService->getAttachmentMainDetails($attachment);
        echo $content;
    }

    


    public function ical(Request $request){

        $input = $request->all();

        $validator = $this->meetingService->validateICALRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        // 1. Create new calendar
        $vCalendar = new \Eluceo\iCal\Component\Calendar('www.example.com');
        
        $value    = "MAILTO:".$input['organizer_email'];
        $param    = $input['organizer_name'];

        $vOrganizer = new \Eluceo\iCal\Property\Event\Organizer($value, ['CN' => $param]);

        // 2. Create an event
        $vEvent = new \Eluceo\iCal\Component\Event();
        $vEvent
        ->setDtStart(new \DateTime($input['start_at']))
        ->setDtEnd(new \DateTime($input['end_at']))
        //->setAlert(new \DateTime($input['end_at']))
        ->setNoTime(false)
        ->setLocation($input['location'])
        ->setSummary($input['summery'])
        ->setDescription($input['description'])
        ->setOrganizer($vOrganizer);
        
        // Adding Timezone (optional)
        $vEvent->setUseTimezone('Africa/Cairo');

        // 3. Add event to calendar
        $vCalendar->addComponent($vEvent);

        // 4. Set headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        // 5. Output
        echo $vCalendar->render();
    }

    public function testPDFCreate(){
        $html="<h1>test</h1>";
        $pdf= PDF::loadHTML($html);
        return $pdf->stream();
    }

    public function attendeeAcceptTerms(Request $request)
    {
        $validator = $this->meetingService->validateAcceptTermsRequest($request);
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        if(!$this->meetingService->acceptTerms($request->meeting_id, $request->code)) {
            return $this->sendError('Invalid Accept terms Code');
        }

        return $this->sendResponse('you have been Accepted Terms successfully');
    }
}
