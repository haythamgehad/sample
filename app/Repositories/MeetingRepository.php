<?php

namespace App\Repositories;

use App\Models\Meeting;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
/**
 * Class MeetingRepository
 * @package App\Repositories
 * @version March 10, 2020, 1:41 pm UTC
*/

class MeetingRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'id',
        'committee_id',
        'account_id',
        'meeting_id',
        'type',
        
        'time_voting_end_at',

        'remote_meeting',
        'remote_meeting_url',

        'is_second_to_id',
        'meeting_name',
        'creator_id',
        'location_id',
        'number',
        'start_at',
        'end_at',
        'duration',
        'invited_count',
        'confirmed_count',
        'canceled_count',
        'title',
        'brief',
        'content',
        'quorum',
        'is_valid_quorum',
        'status',
        'has_approved_report'
    ];

    protected $fieldsShowInMeeting = [
        'id',
        'committee_id',
        'account_id',
        'location_id',
        'number',
        'title',
        'brief',
        'remote_meeting_creator_id',
        'start_at',
        'end_at',
        'remote_meeting',
        'remote_meeting_url',
        'invited_count',
        'confirmed_count',
        'canceled_count',
        'quorum',
        'type',
        'is_valid_quorum',
        'status',
        'has_approved_report',
        'remote_meeting_id',
        'meeting_association_type',
        'meeting_key'
    ];

    protected $fieldsShowInTime = [
        'id',
        'committee_id',
        'account_id',
        'location_id',
        'number',
        'title',
        'brief',
        'start_at',
        'end_at',
        'status',
        'invited_count',
        'has_approved_report'
    ];

    protected $fieldsShowInAssociation = [
        'id',
            'committee_id',
            'account_id',
            'location_id',

            'remote_meeting',
            'remote_meeting_url',

            'is_association',
            'procedure_id',
            'procedure_id',
            'is_first_time',
            'is_second_to_id',
            'role_text',

            'attendees_minimum_shares_count',
            'shares_for_one_vote',
            
            'allow_electronic_voting',
            'electronic_voting_start_at',
            'electronic_voting_end_at',

            'number',
            'title',
            'brief',
            'start_at',
            'end_at',
            'invited_count',
            'confirmed_count',
            'canceled_count',
            'quorum',
            'is_valid_quorum',
            'status',
            'meeting_key'
    ];

    protected $fieldsShowInList = [
        'id',
        'committee_id',
        'number',
        'start_at',
        'end_at',
        'location_id',
        'invited_count',
        'confirmed_count',
        'canceled_count',
        'title',
        'brief',
        'content',
        'quorum',
        'is_valid_quorum',
        'type',
        'status',
        'has_approved_report',
        'meeting_key'
    ];

    protected $relationsShowInCommitteeMeeting = [
        'agendas:total_voted_count,can_acccess_list,accept_voted_count,reject_voted_count,agendas.id,refrain_voted_count,agendas.meeting_id,assignee_id,duration,title,brief,content,status,is_work_agenda,has_voting,has_hidden_voting,has_visable_voting,collection_included',
        'agendas.assignee:id',
        'agendas.attachments',
        'agendas.attachments.media',
        'agendas.attachments.media.annotations',
        'agendas.attachments.medias',
        'agendas.actions:type_id,vote_started_at,vote_ended_at,total_voted_count,accept_voted_count,reject_voted_count,refrain_voted_count,id,agenda_id,title,brief,content,status,due_date,assignee_id,meeting_id,voting_visibility,is_private,show_to,committee_id,voting_result,action_number,boss_weighting,boss_vote_weight_doubled,voting_type',
        'agendas.actions.committee',
        'agendas.actions.committee.members',
        'agendas.actions.committee.translation',
        'agendas.actions.votingElements',
        'agendas.actions.medias:media_id',
        'agendas.actions.medias.media:id',
        'agendas.actions.medias.media.annotations',
        'agendas.actions.votings',
        'agendas.actions.reopen_votes',
        'agendas.actions.votings',
        'agendas.actions.agenda',
        'agendas.actions.agenda.attachments',
        'agendas.actions.agenda.assignee:id',
        'agendas.actions.agenda.assignee.translation:user_id,title,name',
        'agendas.actions.assignee:id',
        'agendas.actions.assignee.translation:user_id,title,name',
        'agendas.actions.comments',
        'agendas.actions.comments.creator',
        'agendas.actions.comments.creator.translation',
        'agendas.actions.votings.creator',
        'agendas.actions.votings.creator.translation',
        'agendas.actions.type:id,translation_id,name',
        'agendas.actions.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,start_date,due_date',
        'agendas.actions.tasks.assignee.translations:user_id,language_id,name',
        'agendas.actions.tasks.assignee.translations.language',
        'agendas.actions.tasks.assignee:id',
        'agendas.actions.tasks.assignee.translation:user_id,title,name',
        'agendas.actions.tasks.meeting:id,title',
    ];

    protected $relationsShowInMeeting = [
            'location',
            'attendees',
            'actions',
            'actions.committee',
            'actions.committee.members',
            'actions.committee.translation',
            'actions.votingElements',
            'actions.medias:media_id',
            'actions.medias.media:id',
            'actions.medias.media.annotations',
            'actions.votings',
            'actions.reopen_votes',
            'actions.votings',
            'actions.agenda',
            'actions.agenda.attachments',
            'actions.agenda.assignee:id',
            'actions.agenda.assignee.translation:user_id,title,name',
            'actions.assignee:id',
            'actions.assignee.translation:user_id,title,name',
            'actions.comments',
            'actions.comments.creator',
            'actions.comments.creator.translation',
            'actions.votings.creator',
            'actions.votings.creator.translation',
            'actions.type:id,translation_id,name',
            'actions.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,start_date,due_date',
            'actions.tasks.assignee.translations:user_id,language_id,name',
            'actions.tasks.assignee.translations.language',
            'actions.tasks.assignee:id',
            'actions.tasks.assignee.translation:user_id,title,name',
            'actions.tasks.meeting:id,title',

            'account',
            'attendees.position',
            'attendees.membership',
            
            'attendees.user:id,picture_id',
            'attendees.user.translation:user_id,title,name',

            'attendees.user.translations:user_id,language_id,title,name',
            'attendees.user.translations.language',

            'organizers:id,meeting_id,member_id,capabilities,expires_at',
            'organizers.user:id',
            'organizers.user.translation:user_id,title,name',

            'organizers.user.translations:user_id,language_id,title,name',
            'organizers.user.translations.language',

            'committee:parent_id,type,id,parent_id,secretary_id,managing_director_id,amanuensis_id,is_permanent,start_at,end_at',
            'committee.translation:committee_id,name',

            'committee.translations:committee_id,language_id,name',
            'committee.translations.language',
            'committee.governances:committee_id,user_id',
            'committee.members:committee_id,member_id,position_id',
            'committee.members.user:id',
            'committee.members.user.translation:user_id,title,name',

            'committee.members.user.translations:user_id,language_id,title,name',
            'committee.members.user.translations.language',

            'committee.members.position:id,translation_id,name',
            'committee.secretary',
            'committee.amanuensis:id',
            'committee.amanuensis.translation:user_id,title,name',
    
            'committee.amanuensis.translations:user_id,language_id,title,name',
            'committee.amanuensis.translations.language',
            'committee.secretary.translation',
            'committee.accountconfiguration',
            'attachments',
            'attachments.medias',
            'attachments.media',
            'attachments.media.annotations',
            'attachments.medias.media',
            'attachments.medias.media.annotations',
            'agendas:total_voted_count,can_acccess_list,accept_voted_count,reject_voted_count,agendas.id,refrain_voted_count,agendas.meeting_id,assignee_id,duration,title,brief,content,status,is_work_agenda,has_voting,has_hidden_voting,has_visable_voting,collection_included',
            'agendas.assignee:id',
            'agendas.assignee.translation:user_id,title,name',
            'agendas.assignee.translations:user_id,language_id,title,name',
            'agendas.assignee.translations.language',
            'agendas.attachments',
            'agendas.comments',
            'agendas.comments.creator',
            'agendas.comments.creator.translation',
            'agendas.comments.replies',
            'agendas.comments.replies.creator',
            'agendas.comments.replies.creator.translation',
            'agendas.attachments.media',
            'agendas.attachments.media.annotations',
            'agendas.attachments.medias',
            'agendas.actions:type_id,vote_started_at,vote_ended_at,total_voted_count,accept_voted_count,reject_voted_count,refrain_voted_count,id,agenda_id,title,brief,content,status,due_date,assignee_id,meeting_id,voting_visibility,is_private,show_to,committee_id,voting_result,action_number,boss_weighting,boss_vote_weight_doubled,voting_type',
            'agendas.actions.committee',
            'agendas.actions.committee.members',
            'agendas.actions.committee.translation',
            'agendas.actions.votingElements',
            'agendas.actions.medias:media_id',
            'agendas.actions.medias.media:id',
            'agendas.actions.medias.media.annotations',
            'agendas.actions.votings',
            'agendas.actions.reopen_votes',
            'agendas.actions.votings',
            'agendas.actions.agenda',
            'agendas.actions.agenda.attachments',
            'agendas.actions.agenda.assignee:id',
            'agendas.actions.agenda.assignee.translation:user_id,title,name',
            'agendas.actions.assignee:id',
            'agendas.actions.assignee.translation:user_id,title,name',
            'agendas.actions.comments',
            'agendas.actions.comments.creator',
            'agendas.actions.comments.creator.translation',
            'agendas.actions.comments.replies',
            'agendas.actions.comments.replies.creator',
            'agendas.actions.comments.replies.creator.translation',
            'agendas.actions.votings.creator',
            'agendas.actions.votings.creator.translation',
            'agendas.actions.type:id,translation_id,name',
            'agendas.actions.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,start_date,due_date',
            'agendas.actions.tasks.assignee.translations:user_id,language_id,name',
            'agendas.actions.tasks.assignee.translations.language',
            'agendas.actions.tasks.assignee:id',
            'agendas.actions.tasks.assignee.translation:user_id,title,name',
            'agendas.actions.tasks.meeting:id,title',
            'agendas.notices',
            'agendas.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,agenda_id,start_date',
            'agendas.tasks.assignee.translations:user_id,language_id,name',
            'agendas.tasks.assignee.translations.language',
            'agendas.tasks.assignee:id',
            'agendas.tasks.assignee.translation:user_id,title,name',
            'agendas.tasks.meeting:id,title',
            'collections',
            'collections.media',
            'collections.media.annotations',
            'collections.annotations',
            'IsSecondTo',
            'directories'
    ];


    protected $relationsShowInTime = [
        'committee',
        'committee.translation',
        'location',
        'times',
        'times.votes.creator:id',
        'times.votes.creator.translation:user_id,title,name',

        'times.votes.creator.translations:user_id,language_id,title,name',
        'times.votes.creator.translations.language',
        
        'timescomments.creator.translations',
        'attendees',
        'directories'
    ];

    protected $relationsShowInAssociation = [
        'location',
        'attendees',
        'attendees.votingCard',
        'attendees.votingCard.annotations',
        'actions',
        'actions.committee',
        'actions.committee.members',
        'actions.committee.translation',
        'actions.votingElements',
        'actions.medias:media_id',
        'actions.medias.media:id',
        'actions.medias.media.annotations',
        'actions.votings',
        'actions.reopen_votes',
        'actions.votings',
        'actions.agenda',
        'actions.agenda.attachments',
        'actions.agenda.assignee:id',
        'actions.agenda.assignee.translation:user_id,title,name',
        'actions.assignee:id',
        'actions.assignee.translation:user_id,title,name',
        'actions.comments',
        'actions.comments.creator',
        'actions.comments.creator.translation',
        'actions.votings.creator',
        'actions.votings.creator.translation',
        'actions.type:id,translation_id,name',
        'actions.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,start_date,due_date',
        'actions.tasks.assignee.translations:user_id,language_id,name',
        'actions.tasks.assignee.translations.language',
        'actions.tasks.assignee:id',
        'actions.tasks.assignee.translation:user_id,title,name',
        'actions.tasks.meeting:id,title',

        'account',
        'attendees.position',
        'attendees.membership',
        
        'attendees.user:id,picture_id',
        'attendees.user.translation:user_id,title,name',

        'attendees.user.translations:user_id,language_id,title,name',
        'attendees.user.translations.language',

        'organizers:id,meeting_id,member_id,capabilities,expires_at',
        'organizers.user:id',
        'organizers.user.translation:user_id,title,name',

        'organizers.user.translations:user_id,language_id,title,name',
        'organizers.user.translations.language',

        'committee:parent_id,type,id,parent_id,secretary_id,managing_director_id,amanuensis_id,is_permanent,start_at,end_at',
        'committee.translation:committee_id,name',

        'committee.translations:committee_id,language_id,name',
        'committee.translations.language',
        'committee.governances:committee_id,user_id',
        'committee.members:committee_id,member_id,position_id',
        'committee.members.user:id',
        'committee.members.user.translation:user_id,title,name',

        'committee.members.user.translations:user_id,language_id,title,name',
        'committee.members.user.translations.language',

        'committee.members.position:id,translation_id,name',
        'committee.secretary',
        'committee.amanuensis:id',
        'committee.amanuensis.translation:user_id,title,name',

        'committee.amanuensis.translations:user_id,language_id,title,name',
        'committee.amanuensis.translations.language',
        'committee.secretary.translation',
        'attachments',
        'attachments.medias',
        'attachments.media',
        'attachments.media.annotations',
        'attachments.medias.media',
        'attachments.medias.media.annotations',
        'agendas:total_voted_count,can_acccess_list,accept_voted_count,reject_voted_count,agendas.id,refrain_voted_count,agendas.meeting_id,assignee_id,duration,title,brief,content,status,is_work_agenda,has_voting,has_hidden_voting,has_visable_voting,collection_included',
        'agendas.assignee:id',
        'agendas.assignee.translation:user_id,title,name',
        'agendas.assignee.translations:user_id,language_id,title,name',
        'agendas.assignee.translations.language',
        'agendas.attachments',
        'agendas.comments',
        'agendas.comments.creator',
        'agendas.comments.creator.translation',
        'agendas.comments.replies',
        'agendas.comments.replies.creator',
        'agendas.comments.replies.creator.translation',
        'agendas.attachments.media',
        'agendas.attachments.media.annotations',
        'agendas.attachments.medias',
        'agendas.actions:type_id,vote_started_at,vote_ended_at,total_voted_count,accept_voted_count,reject_voted_count,refrain_voted_count,id,agenda_id,title,brief,content,status,due_date,assignee_id,meeting_id,voting_visibility,is_private,show_to,committee_id,voting_result,action_number,boss_weighting,boss_vote_weight_doubled,voting_type',
        'agendas.actions.committee',
        'agendas.actions.committee.members',
        'agendas.actions.committee.translation',
        'agendas.actions.votingElements',
        'agendas.actions.medias:media_id',
        'agendas.actions.medias.media:id',
        'agendas.actions.medias.media.annotations',
        'agendas.actions.votings',
        'agendas.actions.reopen_votes',
        'agendas.actions.votings',
        'agendas.actions.agenda',
        'agendas.actions.agenda.attachments',
        'agendas.actions.agenda.assignee:id',
        'agendas.actions.agenda.assignee.translation:user_id,title,name',
        'agendas.actions.assignee:id',
        'agendas.actions.assignee.translation:user_id,title,name',
        'agendas.actions.comments',
        'agendas.actions.comments.creator',
        'agendas.actions.comments.creator.translation',
        'agendas.actions.comments.replies',
        'agendas.actions.comments.replies.creator',
        'agendas.actions.comments.replies.creator.translation',
        'agendas.actions.votings.creator',
        'agendas.actions.votings.creator.translation',
        'agendas.actions.type:id,translation_id,name',
        'agendas.actions.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,start_date,due_date',
        'agendas.actions.tasks.assignee.translations:user_id,language_id,name',
        'agendas.actions.tasks.assignee.translations.language',
        'agendas.actions.tasks.assignee:id',
        'agendas.actions.tasks.assignee.translation:user_id,title,name',
        'agendas.actions.tasks.meeting:id,title',
        'agendas.notices',
        'agendas.tasks:id,title,content,todo_id,due_date,assignee_id,meeting_id,status,action_id,agenda_id,start_date',
        'agendas.tasks.assignee.translations:user_id,language_id,name',
        'agendas.tasks.assignee.translations.language',
        'agendas.tasks.assignee:id',
        'agendas.tasks.assignee.translation:user_id,title,name',
        'agendas.tasks.meeting:id,title',
        'collections',
        'collections.media',
        'collections.media.annotations',
        'collections.annotations',
        'IsSecondTo',
        'directories'
    ];



    protected $relationsShowInList = [
            'location',
            'committee:parent_id,type,id,parent_id,secretary_id,managing_director_id,amanuensis_id,is_permanent,start_at,end_at',
            'committee.translation:committee_id,name',
            'committee.secretary',
            'committee.amanuensis',
            'attendees',
            'collections',
            'collections.media:id',
            'collections.annotations',
            'committee.translations:committee_id,language_id,name',
            'committee.translations.language',
            'directories',
            'reports',
            'reports.media:id',
            'reports.shares'
    ];
    

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFields($action)
    {
        if($action=='meeting'){

            return $this->fieldsShowInMeeting;

        }elseif($action=='list'){

            return $this->fieldsShowInList;

        }elseif($action=='time'){

            return $this->fieldsShowInTime;

        }elseif($action=='association'){

            return $this->fieldsShowInAssociation;

        }
        
    }


     /**
     * Return searchable fields
     *
     * @return array
     */
    public function getRelations($action)
    {
        if($action=='meeting'){

            return $this->relationsShowInMeeting;

        }elseif($action=='list'){

            return $this->relationsShowInList;

        }elseif($action=='time'){

            return $this->relationsShowInTime;

        }elseif($action=='association'){

            return $this->relationsShowInAssociation;

        }elseif($action=='committeeMeetings'){

            return $this->relationsShowInCommitteeMeeting;

        }
    }

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Retrieve all records with given filter criteria
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all($search = [], $skip = null, $limit = null, $columns = ['*'], $sort = null)
    {
        $user = Auth::user();

        //dd($search);

        $query = $this->allQuery($search, $skip, $limit, $sort);

        $query->where(function($query2) use ($user){
            $query2->Where(function($query) use ($user){
                $query->whereHas('attendees', function ($query3) use ($user){
                    return $query3->where('member_id', '=', $user->id);
                })->where('status', '!=', 0);
            })
            ->orWhere(function($query4) use ($user){
                $query4->whereHas('committee', function ($query5) use ($user){
                    return $query5->where('secretary_id', '=', $user->id)
                        ->orWhere('amanuensis_id', '=', $user->id);
                });
            })
            ->orWhere(function($query) use ($user){
                $query->whereHas('committee', function ($query) use ($user){
                    $query->whereHas('governances', function ($query) use ($user){
                        $query->whereHas('user', function ($query) use ($user){
                            return $query->where('id', '=', $user->id);
                        });
                    });
                });
            })
            ->orWhere(function($query) use($user){
                $query->whereHas('reports', function ($query) use($user){
                    return $query->whereHas('shares', function ($query) use($user){
                        return $query->where('shared_to_id', '=', $user->id)
                            ->where('share_status', 2);
                    });
                })->where('status', 5);
            });
        });
        
        return $query->get($columns);
    }

  

    /**
     * Return prepare search fields
     *
     * @return array
     */
    public function prepareSearchFields($request, $user)
    {
        $search = array('account_id'=>$user->account_id);

        if($request->get('number')){
            $search['number']=$request->get('number');
         }

        // if($request->get('meeting_name') && $request->get('meeting_date')){

        //     $meeting_date=\Carbon\Carbon::parse($request->get('meeting_date'));

        //     $idsForMeetingNameSearch=\App\Models\MeetingTranslations::where('name','like', '%'.$request->get('meeting_name').'%')->get('meeting_id')->pluck('meeting_id')->toArray() ;
           
        //     $idsForMeetingDateSearch=Meeting::where('start_at','>=', $meeting_date)->get('id')->pluck('id')->toArray() ;
            
        //     $search['id']=array_intersect($idsForMeetingNameSearch,$idsForMeetingDateSearch);

        // }
        // else if($request->get('meeting_name')){
        //     $search['id']=\App\Models\Meeting::where('title','like', '%'.$request->get('meeting_name').'%')->get()->toArray() ;
        //   }
         
        // else if($request->get('meeting_date')){

        //     $meeting_date=\Carbon\Carbon::parse($request->get('meeting_date'));

        //     $search['id']=Meeting::where('start_at','>=', $meeting_date)->get('id')->pluck('id')->toArray() ;
   
        //   }

        if($request->get('title')){
            $search['title']=Crypt::encryptString($request->get('title'));
        }


        if($request->get('status')){
            $search['status']=$request->get('status');
        }

        
        return $search;
    }


        /**
     * Return prepare search fields
     *
     * @return array
     */
    public function prepareIndexSearchFields($request, $user)
    {
        $search=array('account_id'=>$user->account_id);

        if($request->get('number')){
            $search['number']=$request->get('number');
            }

        if($request->get('title')){
            $search['title']='%'.$request->get('title').'%';
            }

        if($request->get('committee_id')){
            $search['committee_id']=$request->get('committee_id');
            }

        if($request->has('status')){
            $search['status']=$request->get('status');
            } 
        
        return $search; 

    }


    /**
     * Configure the Model
     **/
    public function model()
    {
        return Meeting::class;
    }
}
