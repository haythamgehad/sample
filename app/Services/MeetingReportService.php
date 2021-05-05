<?php

namespace App\Services;

use App\Constants\TranslationCode;
use Illuminate\Support\Facades\Validator;
use App\Services\BaseService;
use App\Services\PDFService;
use App\Services\MediaService;
use Illuminate\Http\Request;
use App\Models\MeetingReport;
use App\Models\Meeting;
use App\Models\Account;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\ReportShare;
use App\Models\ReportSignature;
use App\Models\SignatureHolder;
use App\Models\Attendee;
use App\Models\User;
use App\Models\Language;


use App\Services\NotificationService;
use App\Models\NotificationType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

/**
 * Class MeetingReportService
 *
 * @package App\Services
 */
class MeetingReportService  extends BaseService
{

    public function validateuploadDocxCopyRequest(Request $request)
    {

        $rules = [
            'media_id' => 'required',
        ];

        $messages = [];
    }
    /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateRequest(Request $request)
    {
        $rules = [
            'meeting_id' => 'required',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validatePublishRequest(Request $request)
    {
        $rules = [
            'wrokflow' => 'required|in:ALL,MANAGER,MEMBERS',
            'review_date' => 'required'
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateReOpenRequest(Request $request)
    {
        $rules = [
            'reopen_reason' => 'required',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */

    public function validateUpdateRequest(Request $request)
    {

        $rules = [
            'meeting_id' => 'required',

        ];

        $messages = [];


        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on update signature
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateUpdateplaceSignatureHolderRequest(Request $request)
    {
        $rules = [
            'content' => 'required',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function ministryApproved($meetingReport)
    {
        MeetingReport::where('id', $meetingReport->id)->update(array('status' => MeetingReport::STATUS_MINISTRY_APPROVED));
        return true;
    }

    public function isPresident($meeting, $userId)
    {
        foreach ($meeting->committee->members as $member) {

            if ($member->position_id == 1 && $member->id == $userId) {
                return true;
            }
        }
    }
    public function updateStatus($meetingReportId, $status)
    {
        MeetingReport::where('id', $meetingReportId)->update(array('status' => $status));
        return true;
    }

    public function checkIsSharedWithUser($id,$userId){
        return ReportShare::where('report_id',$id)->where('shared_to_id',$userId)->first();
    }

    public function updateMeetingReport($meetingReport, Request $request)
    {

        $mediaService = new MediaService();

        $meeting = Meeting::where('id', $meetingReport->id)->first();

        $account = Account::where('id', $meeting->account_id)->first();

        $input = $request->all();
        MeetingReport::where('id', $meetingReport->id)->update(array('status' => MeetingReport::STATUS_HISTORY));

        if ($request->has('media_id')) {

            $meetingReport = MeetingReport::find($meetingReport->id);

            $meetingReport->status = MeetingReport::STATUS_DRAFT;
            // Hamad error $id not defiend
            $meetingReport->parent_id = $id;

            $mediaService->moveDirectoryByPath($input['media_id'], 'Accounts/' . $account->slug . '/Reports');

            $input['docx_media_id'] = $input['media_id'];

            $pdf_media_id = $mediaService->copyToPDF($input['media_id']);

            $meetingReport->media_id = $pdf_media_id;

            $meetingReport->save();
        }

        return $meetingReport->id;
    }

    public function publishMeetingReport($meetingReport, Request $request)
    {

        $input = $request->all();

        $meetingReport = MeetingReport::find($meetingReport->id);
        $meetingReport->status = MeetingReport::STATUS_PUBLISHED;
        $meetingReport->save();
        $this->publishMeetingReportNotification($meetingReport,  $request);
    }

    public function publishMeetingReportNotification($meetingReport, Request $request)
    {

        $input = $request->all();

        $notificationService = new NotificationService();

        $link = url('/meetingreports/' . $meetingReport->id);

        if ($input['wrokflow'] == 'ALL') {

            $members = Attendee::where('meeting_id', $meetingReport->meeting_id)->where('status', Attendee::STATUS_ATTENDED);
        }

        if ($input['wrokflow'] == 'MANAGER') {

            $members = Attendee::where('meeting_id', $meetingReport->meeting_id)->where('position_id', Attendee::MANAGER_ID)->where('status', Attendee::STATUS_ATTENDED);
        }

        if ($input['wrokflow'] == 'MEMBERS') {

            $members = Attendee::where('meeting_id', $meetingReport->meeting_id)->where('position_id', '<>', Attendee::MANAGER_ID)->where('status', Attendee::STATUS_ATTENDED);
        }

        $emailLink = '';
        if(isset($input['link'])) {
            $emailLink = $input['link'] . '/meetings/meeting-report/' . $meetingReport->id;
        }

        foreach ($members as $key => $attendee) {

            if ($attendee->delegated_to_id && $attendee->delegated_to_id != $attendee->member_id) {
                $attendee_id = $attendee->delegated_to_id;
            } else {
                $attendee_id = $attendee->member_id;
            }

            $languageId = optional($attendee->user)->language_id ?? $this->getLangIdFromLocale();
            $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
            Lang::setLocale($languageCode);

            $meeting = Meeting::find($meetingReport->meeting_id);
            $notificationBody = [
                __("Committee") => optional($meeting->committee)->translation->name,
                __("Meeting Title") => $meeting->title,
                __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
                __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
                __("Location") => optional($meeting->location)->translation->name
            ];

            $notificationService->sendNotification(
                $attendee_id,
                $meetingReport->account_id,
                $meetingReport->title,
                $link,
                NotificationType::MEETING_REPORT_FINISH,
                array('body' => $notificationBody, 'meeting_id' => $meetingReport->meeting_id),
                $emailLink,
                __("Meeting Report")
            );
        }
    }

    public function shareWithMembers($report_id, $member_id, $input)
    {
        $input['shared_to_id'] = $member_id;
        $meetingreport = $this->reportsharerepository->create($input);
        $meetingreport = $this->meetingReportRepository->find($report_id);
        $this->shareMeetingReportNotification($meetingreport, $member_id);
    }

    public function shareMeetingReportNotification($meetingReport, $user_id, $email_link=null ,$email_text=null)
    {

        $notificationService = new NotificationService();

        $link = url('/meetingreports/' . $meetingReport->id);
        $meeting = $meetingReport->meeting;

        $user = User::find($user_id);

        $languageId = $user->language_id ?? $this->getLangIdFromLocale();
        $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
        Lang::setLocale($languageCode);

        $notificationBody = [
            __("Committee") => optional($meeting->committee)->translation->name,
            __("Meeting Title") => $meeting->title,
            __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
            __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
            __("Location") => optional($meeting->location)->translation->name
        ];

        $notify = $notificationService->sendNotification(
            $user_id,
            $meetingReport->account_id,
            $meeting->title,
            $link,
            NotificationType::MEETING_REPORT_SHARE,
            array('link'=>$email_link, 'body' => $notificationBody),
            $email_link,
            $email_text
        );
    }

    public function checkIsApproved($meetingReport){
        $meeting = Meeting::find($meetingReport->meeting_id);
        $id = $meetingReport->id;
        
        $allMembers = Attendee::where('meeting_id', $meeting->id)->where('is_committee_member', 1)->where('status', 3)->get();
        $sharedMembers = ReportShare::where('report_id', $id)->where('share_status', 1)->where('is_aboard_secretary',0)->get();
        $shareCount = $sharedMembers->count(); 
        $approved_count = $sharedMembers->where('status', 2)->where('share_status', 1)->count(); 
        $allMembers_count = $allMembers->count(); 

        $president = $allMembers->where('position_id', 1)->first();
        if($president) {
            $checkPresidentShared = ReportShare::where(['report_id' => $id, 'shared_to_id' => $president->member_id])->where('share_status', 1)->first();
            $approvedMembersCount = ReportShare::where('report_id', $id)->where('shared_to_id', '!=', $president->member_id)
                ->where('status', 2)->where('share_status', 1)->where('is_aboard_secretary',0)->count();
            $presidentApprove = ReportShare::where('report_id', $id)->where('shared_to_id', $president->member_id)
                ->where('status', 2)->where('share_status', 1)->count();

            if ($presidentApprove == 1 && $approved_count != $allMembers_count) {
                $this->updateStatus($id, MeetingReport::STATUS_PRESIDENT_APPROVED);
            }

            if ((($approvedMembersCount == $shareCount - 1 && $checkPresidentShared && $shareCount > 1) ||
                    ($approvedMembersCount !== 0 && $approvedMembersCount == $shareCount && !$checkPresidentShared)) &&
                $approved_count != $allMembers_count) {
                $this->updateStatus($id, MeetingReport::STATUS_MEMBERS_APPROVED);
            }

            if ($approved_count == $allMembers_count) {
                $this->updateStatus($id, MeetingReport::STATUS_ALL_APPROVED);
                Meeting::where('id', $meeting->id)->update(array('has_approved_report' => 1));
            }
        }
    }

    public function approveMeetingReportNotification($meetingReport, $user_id)
    {
        $meeting = Meeting::find($meetingReport->meeting_id);
        $notificationService = new NotificationService();

        $link = url('/meetingreports/agree/' . $meetingReport->id);
        
        if($meeting)
        $notificationService->sendNotification(
            $user_id,
            $meetingReport->account_id,
            $meeting->title,
            $link,
            NotificationType::NOTIFY_SECRETARY_WHEN_MEMBER_APPROVED_ON_MEETING_MINUTES,
            array()
        );
    }

    // __('new version of meeting report on meeting').$m , 
    public function meetingReportSignNotification($meetingReport, $user_id)
    {

        $notificationService = new NotificationService();

        $link = url('/meetingreports/' . $meetingReport->id);

        $attendees = Attendee::where('meeting_id', $meetingReport->meeting_id)->where('status', Attendee::STATUS_ATTENDED)->get();

        foreach ($attendees as $key => $attendee) {

            $notificationService->sendNotification(
                // Hamad error here $user_id not defined
                $user_id,
                $meetingReport->account_id,
                $meetingReport->title,
                $link,
                NotificationType::MEETING_REPORT_NOTIFY_SIGN,
                array(),
            );
        }
    }

    public function meetingReportSecretarySignNotification($meetingReport)
    {

        $notificationService = new NotificationService();

        $link = url('/meetingreports/' . $meetingReport->id);

        $meeting = Meeting::where('id', $meetingReport->meeting_id)->first();

        $committee = Committee::where('id', $meeting->committee_id)->first();

        $notificationService->sendNotification(
            $committee->amanuensis_id,
            $meetingReport->account_id,
            $meetingReport->title,
            $link,
            NotificationType::MEETING_REPORT_NOTIFY_SIGN_TO_SECRETARY,
            array(),
        );
    }

    public function notifyMembersForPlaceHolders($meetingReport, $userId, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/meetingreports/signature-holder/' . $meetingReport->id);

        $notificationService->sendNotification(
            $userId,
            $meetingReport->account_id,
            $meetingReport->title,
            $link,
            NotificationType::NOTIFY_MEMBER_WHEN_MEETING_CIRCULATED_FOR_SIGNTATURE,
            array('link' => $emailLink),
            $emailLink
        );
    }

    public function addNoticeMeetingReportNotification($meetingReport, $emailLink=null)
    {

        $notificationService = new NotificationService();

        $link = url('/meetingreports/' . $meetingReport->id);

        $user = User::find($meetingReport->creator_id);
        $meeting = Meeting::find($meetingReport->meeting_id);
        $languageId = $user->language_id ?? $this->getLangIdFromLocale();
        $languageCode = ($languageId == Language::ID_AR) ? Language::CODE_AR : Language::CODE_EN;
        Lang::setLocale($languageCode);

        $notificationBody = [
            __("Committee") => optional($meeting->committee)->translation->name,
            __("Meeting Title") => $meeting->title,
            __("Meeting Date") => date('Y-m-d', strtotime($meeting->start_at['full'])),
            __("Meeting Time") => date('H:i a', strtotime($meeting->start_at['full'])),
            __("Location") => optional($meeting->location)->translation->name
        ];
        
        $notificationService->sendNotification(
            $meetingReport->creator_id, 
            $meetingReport->account_id , 
            $meetingReport->title , 
            $link ,
            NotificationType::Add_Notice_To_MEETING_REPORT,
            array('body' => $notificationBody, 'meeting_id' => $meetingReport->meeting_id),
            $emailLink,
            __("Meeting Report")
        );
    }

    public function isReviewed($report_id, $user_id)
    {
        $count =  ReportShare::where('status', ReportShare::IS_REVIEWED)->where('report_id', $report_id)->where('shared_to_id', $user_id)->count();
        return $count;
    }

    public function reOpenReportSharing($report_id)
    {
        ReportShare::where('status', ReportShare::IS_REVIEWED)->where('report_id', $report_id)->update(array('status' => 0));
    }

    public function signMeetingReport($meetingReport, $user_id, $input)
    {

        if (isset($input['content'])) {
            $content = $input['content'];
        } else {
            return false;
        }

        ReportSignature::create(array('report_id' => $meetingReport->id, 'creator_id' => $user_id, 'content' => $content));
        $this->meetingReportSecretarySignNotification($meetingReport);
    }

    public function placeSignatureHolderOnMeetingReport($meetingReport, $user_id, $input)
    {

        if (!isset($input['content']) || !isset($input['annot_id']))
            return false;
        $content = $input['content'];
        $member_id = (isset($input['member_id'])) ? $input['member_id'] : false;
        $creator_id = $user_id;
        $annot_id = $input['annot_id'];

        $signatureHolder = SignatureHolder::firstOrNew(['annot_id' => $annot_id]);

        $signatureHolder->content = $content;
        $signatureHolder->report_id = $meetingReport->id;
        $signatureHolder->annot_id = $annot_id;
        if ($member_id)
            $signatureHolder->member_id = $member_id;
        $signatureHolder->creator_id = $creator_id;
        $signatureHolder->save();

        $emailLink = '';
        if(isset($input['link'])){
            $emailLink = $input['link'].'/meetings/meeting-report/'.$meetingReport->id;
        }
        //TO DO and send notification to member to sign
        if ($member_id) {
            $this->notifyMembersForPlaceHolders($meetingReport, $member_id, $emailLink);
        }
    }

    public function updatePlaceSignatureHolderOnMeetingReport(string $id, array $input): SignatureHolder
    {
        $content = $input['content'];
        $signatureHolder = SignatureHolder::where(['id' => $id])->first();
        $signatureHolder->content = $content;
        $signatureHolder->save();

        return $signatureHolder->fresh();
    }

    public function getSinatureHolders($meetingReport, $user_id, $all = false)
    {

        if ($all) {
            $signatureHolders = SignatureHolder::where('report_id', $meetingReport->id)->get();
        } else {
            $signatureHolders = SignatureHolder::where(['report_id' => $meetingReport->id, 'member_id' => $user_id])->get();
        }

        return $signatureHolders;
    }

    public function deleteSinatureHolders($meetingReport, $user_id)
    {


        $signatureHolders = SignatureHolder::where(['report_id' => $meetingReport->id, 'member_id' => $user_id])->delete();

        return $signatureHolders;
    }

    public function deleteAnnotationSignatureHolders(int $id): bool
    {
        return SignatureHolder::where(['id' => $id])->delete();
    }

    public function getAnnotationSignatureHolders(string $id): ?SignatureHolder
    {
        return SignatureHolder::where('id', $id)->first();
    }
}
