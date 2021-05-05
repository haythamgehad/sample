<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CommitteeTranslation;
use App\Repositories\AttachmentRepository;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\AttachmentService;

use App\Repositories\AttachmentMediaRepository;
use App\Repositories\MeetingRepository;

use App\Repositories\AccountRepository;
use App\Services\MediaService;
use App\Services\MeetingService;
use Ixudra\Curl\Facades\Curl;


use Response;

/**
 * Class AttachmentController
 * @package App\Http\Controllers
 */

class AttachmentController extends Controller
{
    private $attachmentRepository;
    private $attachmentService;

    private $attachmentMediaRepository;
    private $accountRepository;
    private $meetingRepository;
    private $mediaService;
    private $meetingService;

    public function __construct(MeetingRepository $meetingRepo, AttachmentRepository $attachmentRepo, AccountRepository $accountRepo,  AttachmentMediaRepository $attachmentMediaRepo)
    {
        $this->meetingRepository = $meetingRepo;
        $this->attachmentRepository = $attachmentRepo;
        $this->accountRepository = $accountRepo;
        $this->attachmentMediaRepository = $attachmentMediaRepo;

        $this->attachmentService = new AttachmentService() ;
        $this->mediaService = new MediaService() ;
        $this->meetingService = new MeetingService() ;


    }

    /**
    * Show Attachment list
    * GET /attachments
    * @return Response
    */
    public function index(Request $request)
    {
        $attachments = $this->attachmentRepository->with(['media','medias'])->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($attachments->toArray(), 'Attachments retrieved successfully');
    }

    /**
    * Post Attachment.
    * POST /attachments
    * @return Response
    * @bodyParam meeting_id int required The  ID of the meeting. Example: 1
    * @bodyParam title string required The title  of the Attachment. Example: title
    * @bodyParam brief text required The  brief of the Attachment. Example:  brief
    * @bodyParam content text required The  content of the Attachment. Example:  content
    * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
    */
    public function store(Request $request)
    {
        
        $user = Auth::user();

        $userRolePermission = $this->attachmentService->getUserPermissionActions($user->id, Permission::MEETING_CODE);
        if (isset($userRolePermission->update) && !$userRolePermission->update ) {
          // return $this->forbiddenResponse();
        }

       $validator = $this->attachmentService->validateCreateAttachmentRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();
        
        $input['creator_id']=$user->id;

        $input['account_id']=$user->account_id;

        $meeting = $this->meetingRepository->find($input['meeting_id']);

        $account = $this->accountRepository->find($meeting->account_id);

        $input['committee_id']=$meeting->committee_id;

        $attachment = $this->attachmentRepository->create($input);

        if($request->has('media_id')){
            if($meeting){
                $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
                $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                $pathName = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Attachment';
            }else
                $pathName = 'Accounts/'.$account->slug.'/Collections';
           // $this->attachmentMediaRepository->create(array('media_id'=>$input['media_id'],'attachment_id'=>$attachment->id));
            $this->mediaService->moveDirectoryByPath($input['media_id'], $pathName );
        }
        // $this->meetingService->generateCollectionForMeeting($meeting);
        if($meeting)
            $this->meetingService->generateCollection($meeting, null,false);
        return $this->sendResponse($attachment, 'Attachment updated successfully');
    }

    /**
    * Show the specified Attachment.
    * GET /attachments/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Attachment.
    */
    public function show($id)
    {
        $attachment = $this->attachmentRepository->find($id);

        if (empty($attachment)) {
            return $this->sendError('Attachment not found');
        }

        return $this->sendResponse($attachment->toArray(), 'Attachment retrieved successfully');
    }

    /**
    * Update the specified Attachment.
    * PUT/PATCH /attachments/{id}
    * @param int $id
    * @return Response
    * @urlParam id required The ID of the Attachment.
    * @bodyParam title string required The title  of the Attachment. Example: title
    * @bodyParam brief text required The  brief of the Attachment. Example:  brief
    * @bodyParam content text required The  content of the Attachment. Example:  content
    * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
    */
    public function update($id, Request $request)
    {
        $user = Auth::user();

       $validator = $this->attachmentService->validateUpdateAttachmentRequest($request);

       if (!$validator->passes()) {
           return $this->userErrorResponse($validator->messages()->toArray());
       }

        $input = $request->all();

        $attachment = $this->attachmentRepository->find($id);
        $meeting = $this->meetingRepository->find($attachment->meeting_id);
        if (empty($id)) {
            return $this->sendError('Attachment not found');
        }

        if ($attachment->creator_id !== $user->id) {
            return $this->forbiddenResponse();
        }
        
        $attachment = $this->attachmentRepository->update($input, $id);

        $account = $this->accountRepository->find($attachment->account_id);

        if($request->has('media_id')){
            if($meeting){
                $committeeTranslation = CommitteeTranslation::where('committee_id', $meeting->committee_id)->where('language_id', 2)->first();
                $committeeName = preg_replace("/\s+/", "-", $committeeTranslation->name);
                $meetingFormatName = preg_replace("/\s+/", "-", $meeting->title);
                $pathName = 'Accounts/'.$account->slug.'/'.$meeting->committee->type.'/'.$committeeName.'/'.$meetingFormatName.'/Attachment';
            }else
                $pathName = 'Accounts/'.$account->slug.'/Collections';

           // $this->attachmentMediaRepository->create(array('media_id'=>$input['media_id'],'attachment_id'=>$attachment->id));
            $this->mediaService->moveDirectoryByPath($input['media_id'], $pathName );
        }
        // $this->meetingService->generateCollectionForMeeting($meeting);
        $attachment = $this->attachmentRepository->find($attachment->id);

        return $this->sendResponse($attachment, 'Attachment updated successfully');

    }

    /**
    * Delete Attahcment Details
    * Delete /attahcments/{id}
    * @param int $id
    * @return Response
    */
    public function destroy($id)
    {
        $attachment = $this->attachmentRepository->find($id);
        $meting_id = $attachment->meeting_id;

        if (empty($attachment)) {
            return $this->sendError('Attachment not found');
        }

        $attachment->delete();

        if($attachment->media) {
            $this->mediaService->notifyMemberWithUploadedMedia($attachment->media);
        }
        
       // $this->meetingService->generateCollection($meeting); 
    //    $meeting = $this->meetingRepository->find( $meting_id);
    //    $this->meetingService->generateCollectionForMeeting($meeting);
        return $this->sendResponse('Attachment deleted successfully');
    }
}
