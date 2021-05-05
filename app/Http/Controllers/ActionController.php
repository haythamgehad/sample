<?php

namespace App\Http\Controllers;

use App\Models\Action;

use App\Models\ActionVoting;
use App\Models\CommitteeMember;
use App\Models\Meeting;
use App\Repositories\AgendaRepository;
use App\Repositories\ActionRepository;
use App\Repositories\AttendeeRepository;

use App\Repositories\MeetingRepository;
use App\Repositories\TodoRepository;
use App\Repositories\ActionMediaRepository;
use App\Services\MeetingService;
use App\Repositories\CommitteeMemberRepository;
use App\Repositories\AttachmentRepository;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\ReopenVote;
use App\Models\Committee;
use App\Models\CommitteeTranslation;
use App\Models\RolePermission;
use App\Models\ActionVotingElement;
use App\Services\ActionService;
use App\Services\MediaService;
use App\Services\DirectoryService;
use App\Services\PDFService;
use Illuminate\Support\Facades\DB;


use Response;

/**
 * Class ActionController
 * @package App\Http\Controllers
 */

class ActionController extends Controller
{
    private $agendaRepository;

    private $actionRepository;

    private $attendeeRepository;

    private $todoRepository;

    private $actionService;

    private $meetingService;

    private $committeeMemberRepository;
    private $meetingRepository;
    private $mediaService;
    private $attachmentRepository;
    private $directoryService;
    private $pdfService;

    public function __construct(
        AgendaRepository $agendaRepo,
        AttendeeRepository $attendeeRepo,
        ActionRepository $actionRepo,
        TodoRepository $todoRepo,
        ActionMediaRepository $actionMediaRepo,
        CommitteeMemberRepository $committeeMemberRepository,
        MeetingRepository $meetingRepo,
        AttachmentRepository $attachmentRepository
    ){
        $this->agendaRepository = $agendaRepo;

        $this->attendeeRepository = $attendeeRepo;

        $this->actionMediaRepository = $actionMediaRepo;

        $this->actionRepository = $actionRepo;

        $this->todoRepository = $todoRepo;

        $this->committeeMemberRepository = $committeeMemberRepository;

        $this->actionService = new ActionService();
        $this->meetingService = new MeetingService() ;
        $this->mediaService = new MediaService() ;
        $this->meetingRepository = $meetingRepo;
        $this->attachmentRepository = $attachmentRepository;
        $this->directoryService = new DirectoryService();
        $this->pdfService = new PDFService();
    }

    /**
     * Show Actions list
     * GET /actions
     * @return Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $columns = $this->actionRepository->getFields('list');

        $relations=$this->actionRepository->getRelations('list');

        $actions = $this->actionRepository->with($relations)->all(
            $this->actionRepository->prepareIndexSearchFields($request, $user),
            null,
            null,
            $columns
        );

        return $this->sendResponse($actions->toArray(), 'Actions retrieved successfully');
    }

    /**
     * Post Action.
     * POST /actions
     * @return Response
     * @bodyParam status int required The  status of the Action. Example: 1,3
     * @bodyParam agenda_id int required The  ID of the Agenda. Example: 1
     * @bodyParam type_id int required The  ID of the type_id. Example: 1
     * @bodyParam assignee_id int required The  ID of the assignee_id. Example: 1
     * @bodyParam due_date int required The  ID of the due_date. Example: 2020/07/03 03:40
    
     * @bodyParam show_to string required The show_to one of the Action. Example: ALL,MEMBERS,ATTENDEES
     * @bodyParam is_private int required The is_private  of the Action. Example: 1
     * @bodyParam voting_visibility string required The voting_visibility  of the Action. Example: ALL,HIDEN
     * @bodyParam minimum_meeting_requests int required The minimum_meeting_requests  of the Action. Example: 100

     * @bodyParam can_change_vote int required The can_change_vote  of the Action. Example: true,false

     * @bodyParam can_change_after_voting int required The can_change_after_voting  of the Action. Example: true,false
     * @bodyParam can_change_after_publish int required The can_change_after_publish  of the Action. Example: true,false

     * @bodyParam quorum int required The quorum  of the Action. Example: 50
    
     * @bodyParam title string required The title  of the Action. Example: title
     * @bodyParam brief text required The  brief of the Action. Example:  brief
     * @bodyParam content text required The  content of the Action. Example:  content
     * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $account = $user->account;
        $input = $request->all();

        if ($request->has('is_private') && $input['is_private']) {
            $validator = $this->actionService->validateCreatePrivateActionRequest($request);
        } else {
            $validator = $this->actionService->validateCreateActionRequest($request);
        }

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }
        $input['creator_id'] = $user->id;

        $input['account_id'] = $user->account_id;

        if (!$request->has('content')) {
            $input['content'] = $input['title'];
        }
        
        if ($request->has('agenda_id') && !empty($input['agenda_id'])) {
            $agenda = $this->agendaRepository->find($input['agenda_id']);
            $input['meeting_id'] = isset($input['meeting_id']) ? $input['meeting_id'] : $agenda->meeting_id;
            $input['committee_id'] = isset($input['committee_id']) ? $input['committee_id'] : $agenda->committee_id;
        }


        $action = $this->actionRepository->create($input);

//        if (isset($input['attachments']) && !empty($input['attachments'])) {
//            foreach ($input['attachments'] as $key => $attachment) {
//                $validator = $this->actionService->validateAttachmentActionRequest($key, $request);
//                if (!$validator->passes()) {
//                    return $this->userErrorResponse($validator->messages()->toArray());
//                }
//                $attachment['action_id'] = $action->id;
//                $attachment['media_id'] = $attachment['media_id'];
//                $attachment = $this->actionMediaRepository->create($attachment);
//            }
//        }

        if(isset($input['attachments']) && !empty($input['attachments'])){
            $this->directoryService->createAction($action, $request);
            foreach($input['attachments'] as $key=>$attachment){

                $validator = $this->actionService->validateAttachmentActionRequest($key, $request);

                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }

                $attachment['action_id'] = $action->id;
                $attachment['creator_id'] = $user->id;
                $attachment['media_id'] = $input['attachments'][$key]['media_id'];
                $attachment['title']=$input['attachments'][$key]['title'];
                $this->attachmentRepository->create($attachment);
                $committee = Committee::find($input['committee_id']);
                $committeeTranslation = CommitteeTranslation::where('committee_id', $input['committee_id'])->where('language_id', 2)->first();
                $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                $actionFormatName = preg_replace("/\s+/", "-", $action->title);
                $moveToPath = 'Accounts/'.$account->slug.'/'.$committee->type.'/'.$committeeName.'/Resolutions/'.$actionFormatName;
                $this->mediaService->moveDirectoryByPath($input['attachments'][$key]['media_id'],  $moveToPath);
            }

        }

        if ($request->has('voting_elements') && $input['voting_elements']) {
            foreach($input['voting_elements'] as $element) {
                $votingElement = [
                    'action_id' =>  $action->id,
                    'text' =>  $element['text'],
                ];
                ActionVotingElement::create($votingElement);
            }
        }

        $input['action_id'] = $action->id;

        unset($input['assignee_id']);

        $todo = $this->todoRepository->create($input);

        $relations = $this->actionRepository->getRelations('list');

        $action = $this->actionRepository->with($relations)->find($action->id);

        $emailLink = '';
        
        if (!$action->is_private) {
            if ($request->link) {
                $emailLink = $request->link . '/meetings/during-meeting/' . $action->meeting_id;
            }
            $meeting = $this->meetingRepository->find($action->meeting_id);
            $this->actionService->notifyMembersWithNewAction($action, $emailLink);
            $this->meetingService->generateCollectionForMeeting($meeting, $emailLink);
        } else {
            if ($request->link) {
                $emailLink = $request->link . '/decisions';
            }
            $this->actionService->notifyMembersWithPrivateAction($action, );
        }
        
        return $this->sendResponse($action, 'Action updated successfully');
    }

    /**
     * Srart Voting the specified Action.
     * Post /actions/close-vote/{id}
     * @param int $id
     * @return Response
     */
    public function startVote($id, Request $request)
    {

        $user = Auth::user();

        $input = $request->all();

        $action = $this->actionRepository->with('committee')->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $input['status'] = Action::STATUS_START_VOTE;

        $input['vote_started_at'] = date('Y-m-d H:i:s');
        
        $input['boss_weighting'] = ($this->actionService->isBossWeightingRegulations($action))?1:0;
        $action = $this->actionRepository->update($input, $id);
        

        if (in_array($action->voting_type, [Action::VISIBLE, Action::HIDDEN])) {
            $this->actionService->updateCounts($id);
        } elseif(in_array($action->voting_type, [Action::QUESTIONAIRE, Action::CUMULATIVE, Action::HIDDEN_QUESTIONAIRE])) {
            $this->actionService->updateQuestionaireVotingResult($action);
        }


        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }
        if($action->meeting_id) {
            $this->actionService->notifyMemberToActionVote($action,'',$emailLink);
        } else {
            $this->actionService->notifyMemberToActionVoteForDecision($action,'',$emailLink);
        }
        return $this->sendResponse($action, 'Action updated successfully');
    }

    public function startMeetingActionsVoting($meetingId)
    {
        $meeting = Meeting::find($meetingId);
        if (!$meeting) {
            return $this->sendError('Meeting not found');
        }

        foreach($meeting->actions->whereNotNull('voting_type') as $action) {
            $input = [];
            $input['status'] = Action::STATUS_START_VOTE;
            $input['vote_started_at'] = date('Y-m-d H:i:s');
            $input['boss_weighting'] = ($this->actionService->isBossWeightingRegulations($action)) ? 1 : 0;
            $this->actionRepository->update($input, $action->id);
            

            if (in_array($action->voting_type, [Action::VISIBLE, Action::HIDDEN])) {
                $this->actionService->updateCounts($action->id);
            } elseif(in_array($action->voting_type, [Action::QUESTIONAIRE, Action::CUMULATIVE, Action::HIDDEN_QUESTIONAIRE])) {
                $this->actionService->updateQuestionaireVotingResult($action);
            }

        }
        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/association/during-meeting/' . $action->meeting_id;
        }
        $this->actionService->notifyMemberToAssociationActionVote($meeting, $emailLink);
        return $this->sendResponse($meeting, 'Meeting actions started Successfully');
    }

    public function closeMeetingActionsVoting($meetingId)
    {
        $meeting = Meeting::find($meetingId);
        if (!$meeting) {
            return $this->sendError('Meeting not found');
        }

        foreach($meeting->actions->whereNotNull('voting_type') as $action) {
            $input = [];
            $input['status'] = Action::STATUS_VOTE_CLOSED;
            $input['vote_ended_at'] = date('Y-m-d H:i:s');
            $this->actionRepository->update($input, $action->id);
        }

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/association/during-meeting/' . $meeting->id;
        }
        $this->actionService->notifyMemberToCloseAssociationActionVote($meeting, $emailLink);

        return $this->sendResponse($meeting, 'Meeting actions closed Successfully');
    }



    /**
     * Close Voting the specified Action.
     * Post /actions/close-vote/{id}
     * @param int $id
     * @return Response
     */
    public function closeVote($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        $action = $this->actionRepository->with('committee')->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $regulation = $this->actionService->isValidCloseActionRegulations($action);

        if (isset($regulation->status) &&  $regulation->status == 'error') {
            return $this->sendError($regulation->message);
        }

        $input['status'] = Action::STATUS_VOTE_CLOSED;

        $input['vote_ended_at'] = date('Y-m-d H:i:s');

        $action = $this->actionRepository->update($input, $id);

        ReopenVote::where('action_id', $id)->update(['status' => 0]);

        if($action->meeting_id) {
            $this->actionService->notifyMemberToCloseActionVote($action);
        } else {
            $this->actionService->notifyMemberToCloseActionVoteForDecision($action);
        }

        if (isset($regulation->status) &&  $regulation->status == 'warning') {
            return $this->warningResponse($action, 'Action Votted with Warning ' . $regulation->message);
        } else {
            return $this->sendResponse($action, 'Action Votted successfully');
        }
    }

    /**
     * reOpenVote the specified Action.
     * Post /actions/reopen-vote/{id}
     * @param int $id
     * @return Response
     * @urlParam reopen_vote_list list The list of the Re Open votting for Action.
     */
    public function reOpenVote($id, Request $request)
    {

        $user = Auth::user();

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $action = $this->actionRepository->update($input, $id);

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }

        $this->actionService->notifyMemberToActionVote($action, $input['reopen_vote_list'], $emailLink);

        return $this->sendResponse($action, 'Action updated successfully');
    }

    /**
     * Vote the specified Action.
     * Post /actions/vote/{id}
     * @param int $id
     * @return Response
     * @urlParam status int The status of the Action.
     * @bodyParam member_id int required The  ID of the member_id. Example: 1
     */
    public function vote($id, Request $request)
    {
        $input = $request->all();
        $user = Auth::user();
        $action = $this->actionRepository->find($id);
        $current_user_array = array($user->id);
        $reopen_vote_array = explode(',', $action->reopen_vote_list);

        if (empty($action)) {
            return $this->sendError('Action not found');
        }

        $validator = $this->actionService->validateVotingActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $reopen_vote  = ReopenVote::where('user_id', $user->id)->where('action_id', $action->id)->where('status', 1)->first();
        if ($action->status != Action::STATUS_START_VOTE && !in_array($user->id, $reopen_vote_array) && !$reopen_vote) {
            return $this->sendError('Action Votting did not start or Closed');
        }

        if ($action->is_private !== 1) {
            $attendeeMe = $this->attendeeRepository->all(array('meeting_id' => $action->meeting_id, 'member_id' => $user->id, 'status'=>'3'), null, null, '*')->first();
        } else {
            $attendeeMe = $this->committeeMemberRepository->all(array('committee_id' => $action->committee_id, 'member_id' => $user->id), null, null, '*')->first();
        }

        if ($attendeeMe && in_array($action->voting_type, [Action::VISIBLE, Action::HIDDEN])) {
            $vote_status = $this->actionService->vote($user->id, $action, $input['status']);
        } elseif(in_array($action->voting_type, [Action::QUESTIONAIRE, Action::CUMULATIVE, Action::HIDDEN_QUESTIONAIRE])) {
            if($request->has('voting_elements') && !empty($input['voting_elements'])) {
                if(in_array($action->voting_type, [Action::QUESTIONAIRE, Action::HIDDEN_QUESTIONAIRE])) {
                    $vote_status = $this->actionService->questionaireVoting($user, $action, $input['voting_elements']);
                }else{
                    $vote_status = $this->actionService->cumulativeVoting($user, $action, $input['voting_elements']);
                }
                
                if (!$vote_status) {
                    return $this->sendError('Invalid votting, please try again');
                }
            } 
        }


        if (!isset($vote_status) || !$vote_status) {
            return $this->sendError('You can not Vote For this action');
        }

        $array_diff = array_diff($reopen_vote_array, $current_user_array);

        $reopen_vote_list = implode(',', $array_diff);

        $data['reopen_vote_list'] = $reopen_vote_list;

        $this->actionRepository->update($data, $action->id);

        if($action->meeting_id) {
            if($action->meeting->committee->type == 'Associations') {
                $actionsForVoting = $action->meeting->actions->whereNotNull('voting_type')->count();
                $userVotings = ActionVoting::where('creator_id', $user->id)
                ->where('confirmed', 1)
                ->whereIn('action_id', $action->meeting->actions->pluck('id')->toArray())
                ->distinct('action_id')->count();
                
                if($actionsForVoting == $userVotings && $actionsForVoting != 0) {
                    $this->pdfService->getVotingCardAsPdf($action->meeting);
                }
            }
        }

        return $this->sendResponse('Action Voted successfully');
    }

    public function votingConfirmation(Request $request)
    {
        $user = Auth::user();
        $action = $this->actionRepository->find($request->action_id);

        if (empty($action)) {
            return $this->sendError('Action not found');
        }

        $validator = $this->actionService->validateVotingConfirmationRequest($request);
        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        if(!$this->actionService->votingConfirmation($request->action_id, $request->code)) {
            return $this->sendError('Invalid Confirmation Code');
        }

        if($action->meeting_id) {
            if($action->meeting->committee->type == 'Associations') {
                $actionsForVoting = $action->meeting->actions->whereNotNull('voting_type')->count();
                $userVotings = ActionVoting::where('creator_id', $user->id)
                ->where('confirmed', 1)
                ->whereIn('action_id', $action->meeting->actions->pluck('id')->toArray())
                ->distinct('action_id')->count();
                if($actionsForVoting == $userVotings && $actionsForVoting != 0) {
                    $this->pdfService->getVotingCardAsPdf($action->meeting);
                }
            }
        }

        return $this->sendResponse('you have been votted successfully');
    }

    public function resendConfirmationCode(int $actionId)
    {
        if(!$this->actionRepository->find($actionId)) {
            return $this->sendError('Action not found');
        }

        if(!$this->actionService->resendConfirmationCode($actionId)) {
            return $this->sendError('Invalid Confirmation Code');
        }

        return $this->sendResponse('Confirmation Code has been sent Successfully');
    }

    public function actionVotingsResult(int $actionId)
    {
        $votingResult = $this->actionService->votingResult($actionId);

        return $this->sendResponse($votingResult, 'Action Votings retrieved successfully');
    }

    /**
     * Show the specified Action.
     * GET /actions/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Attachment.
     */
    public function show($id)
    {
        $user = Auth::user();
        $relations = $this->actionRepository->getRelations('item');
        $action = $this->actionRepository->with($relations)->find($id);
        if (empty($action)) {
            return $this->sendError('Action not found');
        }
        $action->progress = $this->actionService->calculateProgress($action);

        $action->execution = $this->actionService->calculateExecution($action);

        $action->votings = $this->actionService->getVoting($action, $user->id);

        return $this->sendResponse($action, 'Action retrieved successfully');
    }

    /**
     * Update the specified Action.
     * PUT/PATCH /actions/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Action.
     * @bodyParam status int required The  status of the Action. Example: 1,3
     * @bodyParam show_to string required The show_to one of the Action. Example: ALL,MEMBERS,ATTENDEES
     * @bodyParam can_change_vote int required The can_change_vote  of the Action. Example: true,false
     * @bodyParam quorum int required The quorum  of the Action. Example: 50
     * @bodyParam title string required The title  of the Action. Example: title
     * @bodyParam brief text required The  brief of the Action. Example:  brief
     * @bodyParam content text required The  content of the Action. Example:  content
     * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
     */
    public function update($id, Request $request)
    {
        $user = Auth::user();
        $account = $user->account;
        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        if (!$action->can_change_after_publish && $action->status == Action::STATUS_PUBLISHED) {
            return $this->sendError('Action can not be Updated cause it is published');
        }

        if (!$action->can_change_after_voting && $action->total_voted_count > 0) {
            return $this->sendError('Action can not be Updated Cause it is Already started voted');
        }

        if ($action->is_private) {
            $validator = $this->actionService->validateUpdatePrivateActionRequest($request);
        } else {
            $validator = $this->actionService->validateUpdateActionRequest($request);
        }

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }



        $action = $this->actionRepository->update($input, $id, true);

//        if (isset($input['attachments']) && !empty($input['attachments'])) {
//            foreach ($input['attachments'] as $key => $attachment) {
//                $validator = $this->actionService->validateAttachmentActionRequest($key, $request);
//                if (!$validator->passes()) {
//                    return $this->userErrorResponse($validator->messages()->toArray());
//                }
//                $attachment['action_id'] = $action->id;
//                $attachment['media_id'] = $attachment['media_id'];
//                $attachment = $this->actionMediaRepository->create($attachment);
//            }
//        }

        if(isset($input['attachments']) && !empty($input['attachments'])) {
            foreach ($input['attachments'] as $key => $attachment) {
                if (isset($attachment['id']))
                    continue;
                $validator = $this->actionService->validateAttachmentActionRequest($key, $request);

                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }

                $attachment['action_id'] = $action->id;
                $attachment['creator_id'] = $user->id;
                $attachment['media_id'] = $input['attachments'][$key]['media_id'];
                $attachment['title'] = $input['attachments'][$key]['title'];
                $this->attachmentRepository->create($attachment);

                if (isset($input['attachments'][$key]['media_id']) && !empty($input['attachments'][$key]['media_id'])) {
                    $committeeTranslation = CommitteeTranslation::where('committee_id', $action->committee_id)->where('language_id', 2)->first();
                    $actionFormatName = preg_replace("/\s+/", "-", $action->title);
                    $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                    $committee = Committee::find($action->committee_id);
                    $moveToPath = 'Accounts/' . $account->slug . '/' . $committee->type . '/' . $committeeName . '/Resolutions/' . $actionFormatName;
                    $this->mediaService->moveDirectoryByPath($input['attachments'][$key]['media_id'], $moveToPath);

                }
            }
        }

        ActionVotingElement::where('action_id', $action->id)->delete();
        if ($request->has('voting_elements') && $input['voting_elements']) {
            foreach($input['voting_elements'] as $element) {
                $votingElement = [
                    'action_id' =>  $action->id,
                    'text' =>  $element['text'],
                ];
                ActionVotingElement::create($votingElement);
            }
        }

        //to be reviewed

        // $todo = $this->todoRepository->all(array('action_id'=>$action->id))->first();

        // $todo = $this->todoRepository->update($input, $todo->id);

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }
        if (!$action->is_private) {
            $this->actionService->notifyMembersWithUpdateAction($action, $emailLink);

            if ($action->assignee_id) {
                $this->actionService->notifyAssigneeWithUpdateAction($action, $emailLink);
            }

            if ($action->meeting_id) {
                $meeting = $this->meetingRepository->find($action->meeting_id);
                $this->meetingService->generateCollectionForMeeting($meeting, $emailLink);
            }
        } else {
            $this->actionService->notifyMembersWithPrivateAction($action);
        }

        return $this->sendResponse($action, 'Action updated successfully');
    }

    /**
     * Update the Assignee of specified Action.
     * POST /actions/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Action.
     * @bodyParam assignee_id int required The  ID of the assignee. Example: 1
     */
    public function assign_action($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->actionService->validateAssigneeActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        //to do remove comment after status updated correct
        // if($action->status != Action::STATUS_APPROVED){
        //     return $this->sendError('Action must be Approved first');
        // }

        $action = $this->actionRepository->update($input, $id, true);

        $this->actionService->notifyMembersWithUpdateAction($action);

        if ($action->assignee_id) {
            $this->actionService->notifyAssigneeWithUpdateAction($action);
        }

        return $this->sendResponse($action, 'Action assigned successfully');
    }


    public function assignee($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->actionService->validateAssigneeActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        if ($action->status != Action::STATUS_APPROVED) {
            return $this->sendError('Action must be Approved first');
        }

        $action = $this->actionRepository->update($input, $id, true);

        $todo = $this->todoRepository->all(array('action_id' => $action->id))->first();

        $todo = $this->todoRepository->update($input, $todo->id);


        $this->actionService->notifyMembersWithUpdateAction($action);

        if ($action->assignee_id) {
            $this->actionService->notifyAssigneeWithUpdateAction($action);
        }

        return $this->sendResponse($action, 'Action updated successfully');
    }

    /**
     * Publish the specified Action.
     * POST /actions/publish/{id}
     * @param int $id
     * @return Response
     */
    public function publish($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->actionService->validateUpdateActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $input['status'] = Action::STATUS_PUBLISHED;

        $action = $this->actionRepository->update($input, $id);

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }

        $this->actionService->notifyMembersWithPublishAction($action, $emailLink);

        if ($action->assignee_id) {
            $this->actionService->notifyAssigneeWithPublishAction($action, $emailLink);
        }


        return $this->sendResponse($action, 'Action Published successfully');
    }

    /**
     * End the specified Action.
     * POST /actions/end/{id}
     * @param int $id
     * @return Response
     */
    public function end($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->actionService->validateUpdateActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $input['status'] = Action::STATUS_ENDED;

        $action = $this->actionRepository->update($input, $id);

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }

        $this->actionService->notifyMembersWithEndAction($action, $emailLink);

        if ($action->assignee_id) {
            $this->actionService->notifyAssigneeWithEndAction($action, $emailLink);
        }


        return $this->sendResponse($action, 'Action Ended successfully');
    }
    /**
     * Cancel the specified Action.
     * POST /actions/cancel/{id}
     * @param int $id
     * @return Response
     */
    public function cancel($id, Request $request)
    {
        $user = Auth::user();

        $validator = $this->actionService->validateUpdateActionRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $action = $this->actionRepository->find($id);

        if (empty($id)) {
            return $this->sendError('Action not found');
        }

        if ($action->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }

        $input['status'] = Action::STATUS_CANCELED;

        $action = $this->actionRepository->update($input, $id);

        $emailLink = '';
        if (isset($input['link']) && $action->meeting_id) {
            $emailLink = $input['link'] . '/meetings/during-meeting/' . $action->meeting_id;
        }

        $this->actionService->notifyMembersWithCancelAction($action, $emailLink);

        if ($action->assignee_id) {
            $this->actionService->notifyAssigneeWithCancelAction($action, $emailLink);
        }


        return $this->sendResponse($action, 'Action Canceled successfully');
    }

    /**
     * Delete Action Details
     * Delete /actions/{id}
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $action = $this->actionRepository->find($id);

        if (empty($action)) {
            return $this->sendError('Action not found');
        }

        $action->delete();

        if($action->meeting_id) {
            $meeting = $this->meetingRepository->find($action->meeting_id);
            $this->meetingService->generateCollectionForMeeting($meeting);
        }

        return $this->sendResponse('Action deleted successfully');
    }

    public function addExtraBasicDetailstoInput($input, $language_code, $token = "")
    {
        $input['default_language_code'] = $language_code;
    }

    public function weightingBossVoting(int $id)
    {
        $action = $this->actionRepository->find($id);
        if (!$action) {
            return $this->sendError('Action not found');
        }

        if (!$this->actionService->weightingBossVoting($action)) {
            return $this->sendError('Some thing went wrong');
        }

        return $this->sendResponse([], 'Action has been updated successfully');
    }
}
