<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Committee;
use App\Models\MeetingReport;
use App\Models\Permission;
use App\Models\ReportShare;
use App\Repositories\MediaUserShareRepository;
use App\Repositories\MeetingReportRepository;
use App\Repositories\MeetingRepository;
use App\Repositories\ReportNoticeRepository;
use App\Repositories\ReportShareRepository;
use App\Services\MeetingReportService;
use App\Services\MeetingService;
use App\Services\ReportNoticeService;
use App\Services\ReportShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Response;

/**
 * Class MeetingReportController
 * @package App\Http\Controllers\API
 */

class MeetingReportController extends Controller
{
    /** @var  MeetingReportRepository */
    private $meetingReportRepository;
    private $meetingReportService;

    private $meetingRepository;
    private $meetingService;

    private $reportShareRepository;
    private $reportShareService;

    private $reportNoticeRepository;
    private $reportNoticeService;

    private $mediaUserShareRepository;

    public function __construct(
        MeetingRepository $meetingRepo,
        MeetingReportRepository $meetingReportRepo,
        ReportShareRepository $reportShareRepo,
        ReportNoticeRepository $reportNoticeRepo,
        MediaUserShareRepository $mediaUserShareRepository
    ) {
        $this->meetingRepository = $meetingRepo;
        $this->meetingService = new meetingService();

        $this->meetingReportRepository = $meetingReportRepo;
        $this->meetingReportService = new meetingReportService();

        $this->reportShareRepository = $reportShareRepo;
        $this->reportShareService = new reportShareService();

        $this->reportNoticeRepository = $reportNoticeRepo;
        $this->reportNoticeService = new reportNoticeService();

        $this->mediaUserShareRepository = $mediaUserShareRepository;
    }

    /**
     * Display a listing of the MeetingReport.
     * GET|HEAD /meetingreports
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $input = $request->all();

        $user = Auth::user();

        $meeting = $this->meetingRepository->find($input['meeting_id']);

        $hasAccess = $this->meetingService->hasReadAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $search = array('account_id' => $user->account_id, 'meeting_id' => $meeting->id, 'language_id' => $this->getLangIdFromLocale());

        $meetingreports = $this->meetingReportRepository->all(
            $search,
            null,
            null
        );
        /*
        $meetingreports = $this->meetingreportRepository->all(
        $request->except(['skip', 'limit']),
        $request->get('skip'),
        $request->get('limit')
        );
         */
        return $this->sendResponse($meetingreports->toArray(), 'MeetingReport retrieved successfully');
    }

    /**
     * Store a newly created MeetingReport in storage.
     * POST /meetingreports
     *
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $user = Auth::user();

        $meeting = $this->meetingRepository->find($input['meeting_id']);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }
        $input['account_id'] = $user->account_id;

        $input['creator_id'] = $user->id;
        $input['committee_id'] = $meeting->committee_id;

        $reports = MeetingReport::where('meeting_id', $input['meeting_id'])->get();
        if ($reports->count() > 0) {
            $count = $reports->count();
            $input['version_id'] = $count + 1;
        } else {
            $input['version_id'] = 1;
        }
        $meetingreport = $this->meetingReportRepository->create($input);

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport saved successfully');
    }

    /**
     * Display the specified MeetingReport.
     * GET|HEAD /meetingreports/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {

        $user = Auth::user();
        $report = $this->meetingReportRepository->find($id);

        $relations = $this->meetingReportRepository->getRelations('item');
        if ($user->id !== optional($report->committee)->amanuensis_id && $user->id !== optional($report->committee)->secretary_id) {
            $noticeRelations = [
                'myNotices',
                'myNotices.creator',
                'myNotices.creator.translation:user_id,title,name',
                'myNotices.replies',
                'myNotices.replies.creator',
                'myNotices.replies.creator.translation:user_id,title,name',
            ];
        } else {
            $noticeRelations = [
                'notices',
                'notices.creator',
                'notices.creator.translation:user_id,title,name',
                'notices.replies',
                'notices.replies.creator.translation:user_id,title,name',
            ];
        }

        $mergeRelations = array_merge($relations, $noticeRelations);

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->with($mergeRelations)->find($id);
        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasReadAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport retrieved successfully');
    }

    /**
     * Display the specified MeetingReport.
     * GET|HEAD /meetingreports/signature-holders/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function getSignatureHolders($id)
    {

        $user = Auth::user();

        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->find($id);
        if (empty($meetingReport)) {
            return $this->sendError('MeetingReport not found');
        }

        if ($meetingReport->creator_id === $user->id) {
            $placeHolders = $this->meetingReportService->getSinatureHolders($meetingReport, $user->id, $all = true);
        } else {
            $placeHolders = $this->meetingReportService->getSinatureHolders($meetingReport, $user->id, $all = false);
        }

        return $this->sendResponse($placeHolders->toArray(), 'MeetingReport retrieved successfully');
    }

    /**
     * Reopen the specified MeetingReport in storage.
     * POST /meetingreports/{id}
     * media_id
     * @param int $id
     *
     * @return Response
     */
    public function reopen($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $input['status'] = 0;

        $input['review_date'] = date("Y/m/d");

        $input['is_reopen'] = 1;

        $meetingreport = $this->meetingReportRepository->update($input, $id);

        $this->meetingReportService->reOpenReportSharing($meetingreport);

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport updated successfully');
    }

    /**
     * Update the specified MeetingReport in storage.
     * PUT/PATCH /report-docx-copy/{id}
     * @bodyParam media_id int required The  ID of the Media. Example: 1
     * @param int $id
     *
     * @return Response
     */
    public function uploadDocxCopy($id, Request $request)
    {

        $user = Auth::user();

        $input = $request->all();

        $validator = $this->meetingReportService->validateuploadDocxCopyRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meetingreport = $this->meetingreportService->updateMeetingReport($meetingreport, $request);

        $this->meetingReportService->reOpenReportSharing($meetingreport);

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport updated successfully'); // $meetingreport = $this->meetingreportService->updateMeetingReport($meetingreport, $request);

        $meetingreport = $this->meetingReportRepository->update($input, $id);

        $this->meetingReportService->reOpenReportSharing($meetingreport);

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport updated successfully');
    }

    /**
     * Update the specified MeetingReport in storage.
     * PUT/PATCH /meetingreports/{id}
     * @param int $id
     *
     * @return Response
     */
    public function update($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $meetingreport = $this->meetingReportRepository->update($input, $id);

        $this->meetingReportService->reOpenReportSharing($meetingreport);

        return $this->sendResponse($meetingreport->toArray(), 'MeetingReport updated successfully');
    }

    /**
     * Ministry Approved Meeting Report Details
     * Ministry Approved /meetingreports/ministry-approved/{id}
     * @param int $id
     * @return Response
     */
    public function ministryApproved($id)
    {
        $user = Auth::user();

        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meeting)) {
            return $this->sendError('Meeting Report not found');
        }

        $hasAccess = $this->meetingService->hasSecrectaryAccess($user->id, $meetingreport);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $this->meetingService->ministryApproved($meetingreport);

        return $this->sendResponse('Meeting Ministry Approved successfully');
    }

    /**
     * Publish the specified MeetingReport.
     * POST /meetingreports/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function publish($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $validator = $this->meetingReportService->validatePublishRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $this->meetingReportService->publishMeetingReport($meetingreport, $request);

        return $this->sendResponse($meetingreport, 'MeetingReport updated successfully');
    }

    /**
     * Sign the specified MeetingReport in storage.
     * POST /meetingreports/sign/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function sign($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        $relations = $this->meetingReportRepository->getRelations('item');

        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->with($relations)->find($id);
        if (empty($meetingReport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingReport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $this->meetingReportService->signMeetingReport($meetingReport, $user->id, $input);

        return $this->sendResponse($meetingReport->toArray(), 'MeetingReport Signed successfully');
    }

    /**
     * Add / Update member signature place holder on meeting report
     * POST /meetingreports/signature-holder/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function signatureHolder($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->find($id);

        if (empty($meetingReport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingReport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $this->meetingReportService->placeSignatureHolderOnMeetingReport($meetingReport, $user->id, $input);

        return $this->sendResponse($meetingReport->toArray(), 'MeetingReport Signature place holder successfully');
    }

    /**
     * Update member signature place holder on meeting report
     * PUT /meetingreports/annotation-signature-holder/{annotId}
     *
     * @param string $annotId
     *
     * @return Response
     */
    public function updateAnnotationSignatureHolder(string $id, Request $request): JsonResponse
    {
        $user = Auth::user();
        $input = $request->all();
        $annotationSignature = $this->meetingReportService->getAnnotationSignatureHolders($id);

        if (!$annotationSignature) {
            return $this->sendError('Annotation Signature not found');
        }

        $meetingReport = $this->meetingReportRepository->find($annotationSignature->report_id);

        $validator = $this->meetingReportService->validateUpdateplaceSignatureHolderRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $meeting = $this->meetingRepository->find($meetingReport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $this->meetingReportService->updatePlaceSignatureHolderOnMeetingReport($id, $input);

        return $this->sendResponse($meetingReport->toArray(), 'MeetingReport Signature place holder successfully');
    }

    /**
     * Remove the specified MeetingReport from storage.
     * DELETE /meetingreports/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {

        $user = Auth::user();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasDeleteAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $meetingreport->delete();

        return $this->sendResponse('MeetingReport deleted successfully');
    }

    /**
     * Remove the specified MeetingReport signature holder.
     * DELETE /meetingreports/signature-holder/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function deleteSignatureHolder($id)
    {

        $user = Auth::user();

        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->find($id);

        $placeHolders = $this->meetingReportService->deleteSinatureHolders($meetingReport, $user->id);

        if ($placeHolders) {
            return $this->sendResponse('MeetingReport place holder deleted successfully');
        } else {
            return $this->sendResponse('place holder not found');
        }

    }

    public function deleteAnnotationSignatureHolder(int $id): JsonResponse
    {

        $placeHolders = $this->meetingReportService->deleteAnnotationSignatureHolders($id);

        if ($placeHolders) {
            return $this->sendResponse('MeetingReport place holder deleted successfully');
        }

        return $this->sendResponse('place holder not found');
    }

    /**
     * Share the specified MeetingReport.
     * POST /meetingreports/share/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function share($id, Request $request, $code = null)
    {

        $user = Auth::user();

        $input = $request->all();
        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->with(['attendees','committee'])->find($meetingreport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $validator = $this->reportShareService->validateCreateRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input['report_id'] = $id;

        $input['creator_id'] = $user->id;
        //TODO:: to be reviewd
        // handle sharing with president and members logic
        $email_link = '';
        if (isset($input['link'])) {
            $email_link = $input['link'] . '/meetings/meeting-report/' . $id;
        }

        if ($code || isset($input['code'])) {
            if (isset($input['code'])) {
                $code = $input['code'];
            }
            $input['share_status'] = ReportShare::OFFICIAL_SHARE;
            $isSecretaryAboard = $this->meetingService->checkIsSecretaryAboard($meeting);
            if ($code == 1) {
                MeetingReport::where('id', $id)->update(array('send_to_president' => 1, 'send_to_members' => 1));
                foreach ($meeting->committee->members as $member) {
                    //  $this->meetingReportService->shareWithMembers($meetingreport->report_id, $member->member_id, $input);
                    $input['shared_to_id'] = $member->member_id;
                    $input['position_id'] = $member->position_id;
                    $attendee = Attendee::where('member_id', $member->member_id)->where('meeting_id', $meeting->id)->first();
                    if (!$member->position_id) {
                        $input['speciality'] = $attendee ? $attendee->speciality : null;
                    }
                    if ($attendee && $attendee->status == 3) {
                        $count = ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->count();
                        if ($count < 1) {
                            $this->mediaUserShareRepository->create([
                                'media_id' => $meetingreport->media_id,
                                'creator_id' => $user->id,
                                'shared_to_id' => $member->member_id,
                                'type_id' => 1,
                            ]);
                            $report = $this->reportShareRepository->create($input);
                            $this->meetingReportService->shareMeetingReportNotification($meetingreport, $member->member_id, $email_link);
                        } else {
                            ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->update(array('share_status' => $input['share_status']));
                        }
                    }
                }
                if($isSecretaryAboard && !$this->meetingReportService->checkIsSharedWithUser($id,$meeting->committee->amanuensis_id)){
                    $input['shared_to_id'] = $meeting->committee->amanuensis_id;
                    $input['position_id'] = 5;
                    $input['is_aboard_secretary'] = 1;
                    $input['status'] = 2;
                    $report = $this->reportShareRepository->create($input);
                }
            }

            if ($code == 2) {
                // get committe president and share the report with him
                MeetingReport::where('id', $id)->update(array('send_to_president' => 1));
                foreach ($meeting->committee->members as $member) {
                    if ($member->position_id == 1) {
                        $input['shared_to_id'] = $member->member_id;
                        $input['position_id'] = $member->position_id;
                        if (!$member->position_id) {
                            $input['speciality'] = $attendee ? $attendee->speciality : null;
                        }
                        // $this->meetingReportService->shareWithMembers($meetingreport->report_id, $member->member_id, $input);
                        $count = ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->count();
                        if ($count < 1) {
                            $this->mediaUserShareRepository->create([
                                'media_id' => $meetingreport->media_id,
                                'creator_id' => $user->id,
                                'shared_to_id' => $member->member_id,
                                'type_id' => 1,
                            ]);
                            $report = $this->reportShareRepository->create($input);
                            $this->meetingReportService->shareMeetingReportNotification($meetingreport, $member->member_id, $email_link);
                            break;
                        } else {
                            ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->update(array('share_status' => $input['share_status']));
                        }
                    }
                }
                if($isSecretaryAboard && !$this->meetingReportService->checkIsSharedWithUser($id,$meeting->committee->amanuensis_id)){
                    $input['shared_to_id'] = $meeting->committee->amanuensis_id;
                    $input['position_id'] = 5;
                    $input['is_aboard_secretary'] = 1;
                    $input['status'] = 2;
                    $report = $this->reportShareRepository->create($input);
                }
            }

            if ($code == 3) {
                MeetingReport::where('id', $id)->update(array('send_to_members' => 1));
                foreach ($meeting->committee->members as $member) {
                    if ($member->position_id != 1) {
                        $input['shared_to_id'] = $member->member_id;
                        $input['position_id'] = $member->position_id;
                        $attendee = Attendee::where('member_id', $member->member_id)->where('meeting_id', $meeting->id)->first();
                        if (!$member->position_id) {
                            $input['speciality'] = $attendee ? $attendee->speciality : null;
                        }
                        if ($attendee && $attendee->status == 3) {
                            //  $this->meetingReportService->shareWithMembers($meetingreport->report_id, $member->member_id, $input);
                            $count = ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->count();
                            if ($count < 1) {
                                $this->mediaUserShareRepository->create([
                                    'media_id' => $meetingreport->media_id,
                                    'creator_id' => $user->id,
                                    'shared_to_id' => $member->member_id,
                                    'type_id' => 1,
                                ]);
                                $report = $this->reportShareRepository->create($input);
                                $this->meetingReportService->shareMeetingReportNotification($meetingreport, $member->member_id, $email_link);
                            } else {
                                ReportShare::where('report_id', $id)->where('shared_to_id', $member->member_id)->update(array('share_status' => $input['share_status']));
                            }
                        }
                    }
                }
                if($isSecretaryAboard && !$this->meetingReportService->checkIsSharedWithUser($id,$meeting->committee->amanuensis_id)){
                    $input['shared_to_id'] = $meeting->committee->amanuensis_id;
                    $input['position_id'] = 5;
                    $input['is_aboard_secretary'] = 1;
                    $input['status'] = 2;
                    $report = $this->reportShareRepository->create($input);
                }
            }
            if (isset($input['approved_date'])) {
                MeetingReport::where('id', $id)->update(array('approved_date' => $input['approved_date']));
            }
        }

        if (isset($input['sharing'])) {
            foreach ($input['sharing'] as $share) {
                $input['shared_to_id'] = $share;
                $input['share_status'] = ReportShare::NON_OFFICIAL_SHARE;
                $count = ReportShare::where('report_id', $id)->where('shared_to_id', $share)->count();
                if ($count < 1) {
//                    $report = $this->reportShareRepository->create($input);
                    //                    $this->meetingReportService->shareMeetingReportNotification($meetingreport, $share);
                    $this->mediaUserShareRepository->create([
                        'media_id' => $meetingreport->media_id,
                        'creator_id' => $user->id,
                        'shared_to_id' => $share,
                        'type_id' => 1,
                    ]);
                    $meetingreport = $this->reportShareRepository->create($input);
                    $meetingreport = $this->meetingReportRepository->find($meetingreport->report_id);
                    $this->meetingReportService->shareMeetingReportNotification($meetingreport, $share, $email_link);
                }
            }
        }

        $this->meetingReportService->checkIsApproved($meetingreport);

        return $this->sendResponse('Report shared successfully');
    }

    /**
     * Agree the specified MeetingReport.
     * POST /meetingreports/agree/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function agree($id, Request $request)
    {

        $user = Auth::user();
        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->find($id);

        if (empty($meetingReport)) {
            return $this->sendError('MeetingReport not found');
        }

        $meeting = $this->meetingRepository->find($meetingReport->meeting_id);

        $hasAccess = $this->meetingService->hasUpdateAccess($user->id, $meeting->creator_id, Permission::MEETING_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        ReportShare::where(['report_id' => $id, 'shared_to_id' => $user->id])->update(array('status' => ReportShare::ACCEPTED));

        // get all members count in shares and check if the president are not on the list

        $this->meetingReportService->checkIsApproved($meetingReport);
        $committee = Committee::find($meeting->committee_id);
        $this->meetingReportService->approveMeetingReportNotification($meetingReport, $committee->amanuensis_id);
        return $this->sendResponse('success');
    }

    /**
     * Notice the specified MeetingReport.
     * POST /meetingreports/notice/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function notice($id, Request $request)
    {

        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $isReviewed = $this->meetingReportService->isReviewed($id, $user->id);

        if ($isReviewed) {
            return $this->sendError('You Already approved this report');
        }

        $validator = $this->reportNoticeService->validateCreateRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input['report_id'] = $id;

        $input['creator_id'] = $user->id;

        $this->reportNoticeRepository->create($input);

        $this->meetingReportService->addNoticeMeetingReportNotification($meetingreport);

        $meetingReport = $this->meetingReportRepository->with('notices')->find($id);

        return $this->sendResponse($meetingReport, 'Report Notice shared successfully');
    }

    /**
     * Reply Notice the specified MeetingReport.
     * POST /meetingreports/reply-notice/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function replyNotice($id, Request $request)
    {

        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingreport = $this->meetingReportRepository->find($id);

        if (empty($meetingreport)) {
            return $this->sendError('MeetingReport not found');
        }

        $isReviewed = $this->meetingReportService->isReviewed($id, $user->id);

        if ($isReviewed) {
            return $this->sendError('You Already approved this report');
        }

        $validator = $this->reportNoticeService->validateReplyRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input['report_id'] = $id;

        $input['creator_id'] = $user->id;

        $this->reportNoticeRepository->create($input);

        $this->meetingReportService->addNoticeMeetingReportNotification($meetingreport);

        $meetingreport = $this->meetingReportRepository->with('notices')->find($id);

        return $this->sendResponse($meetingreport, 'Report Reply Notice shared successfully');
    }

    public function getLatest()
    {

        $user = Auth::user();

        $meetingReport = new \stdClass();

        $reportShare = ReportShare::where(['shared_to_id' => $user->id, 'status' => 1, 'share_status' => 1])->latest('created_at')->first();

        if ($reportShare) {
            $meetingReport = MeetingReport::with(['committee', 'meeting'])->find($reportShare->report_id);
        }

        return $this->sendResponse($meetingReport, 'meeting report retrived successfully');
    }

    /**
     * Notify to Sign the specified MeetingReport.
     * POST /meetingreports/notify-to-sign/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function notifyToSign($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        /** @var MeetingReport $meetingreport */
        $meetingReport = $this->meetingReportRepository->find($id);

        if (empty($meetingReport)) {
            return $this->sendError('MeetingReport not found');
        }

        $this->meetingReportService->meetingReportSignNotification($meetingReport, $user->id);

        $meetingreport = $this->meetingReportRepository->with('notices')->find($id);

        return $this->sendResponse($meetingReport, 'Report Notify Sign shared successfully');
    }
}
