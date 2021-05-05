<?php

namespace App\Repositories;

use App\Models\Committee;
use App\Repositories\BaseRepository;

/**
 * Class CommitteeRepository
 * @package App\Repositories
 * @version March 10, 2020, 11:48 am UTC
*/

class CommitteeRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'creator_id',
        'account_id',
        'has_sub',
        'is_permanent',
        'parent_id',
        'type',
        'amanuensis_id',
        'secretary_id',
        'managing_director_id',
       // 'board_type_id',
      //  'committee_type_id',
        'start_at',
        'end_at',
        /*
        'quorum',
        'independents_percentage',
        'meetings_number_yearly',
        'attendees_members_count',
        'executive_members_count',
        'non_executive_members_count',
        */
        'commercial_register',
        'capital',
        'subscribed_capital',
        'accountant_id',
        'location_id',
        'status'
    ];

    protected $fieldsShowInItem = [
        'id',
        'account_id',
        'amanuensis_id',
        'secretary_id',
        'managing_director_id',
        'type',
        'is_permanent',
        'parent_id',
        'created_at',
        'start_at',
        'commercial_register',
        'company_system',
        'shares',
        'capital',
        'end_at',
    ];

    protected $fieldsShowInLogs = [
        'id',
    ];

    protected $fieldsShowInList = [
        'id',
        'account_id',
        'amanuensis_id',
        'secretary_id',
        'managing_director_id',
        'type',
        'edit_access',
        'is_permanent',
        'parent_id',
        'created_at',
        'start_at',
        'end_at',
    ];

    protected $relationsShowInItem = [
        'accountconfiguration',
        'accountconfiguration.regulationconfiguration',
        'translation:committee_id,name',
        'translations:committee_id,language_id,name',
        'translations.language',
        'commercialregistermedia',
        'secretary:id',
        'secretary.translation:user_id,title,name',
        'governances.user.translation:user_id,title,name',
        'secretary.translations:user_id,language_id,title,name',
        'secretary.translations.language',

        'amanuensis:id',
        'amanuensis.translation:user_id,title,name',

        'amanuensis.translations:user_id,language_id,title,name',
        'amanuensis.translations.language',

        'medias:committee_id,media_id',
        'medias.media:id,name',
        'authorities',
        'committees:parent_id,type,id,parent_id,secretary_id,managing_director_id,is_permanent,start_at,end_at',
        'committees.translation:committee_id,name',

        'committees.translations:committee_id,language_id,name',
        'committees.translations.language',

        'committees.secretary:id',
        'committees.secretary.translation:user_id,title,name',

        'committees.secretary.translations:user_id,language_id,title,name',
        'committees.secretary.translations.language',

        'committees.managingDirector:id',
        'committees.managingDirector.translation:user_id,title,name',

        'committees.managingDirector.translations:user_id,language_id,title,name',
        'committees.managingDirector.translations.language',

        'committees.amanuensis:id',
        'committees.amanuensis.translation:user_id,title,name',

        'committees.amanuensis.translations:user_id,language_id,title,name',
        'committees.amanuensis.translations.language',
        


        'committees.members:committee_id,member_id,created_at,membership_id,position_id,shares,organization_name',
        'committees.members.position:id,translation_id,name',
        'committees.members.membership:id,translation_id,name',
        'committees.members.user:id',
        'committees.members.user.translation:user_id,title,name',
        'committees.members.user.translations:user_id,language_id,title,name',
        'committees.members.user.translations.language',



        

        'members:committee_id,member_id,created_at,membership_id,position_id,joining_date,shares,organization_name',
        'members.position:id,translation_id,name',
        'members.membership:id,translation_id,name',
        'members.user:id',
        'members.user.translation:user_id,title,name',
        'members.user.translations:user_id,language_id,title,name',
        'members.user.translations.language',

        'meetings:id,committee_id,number,title,start_at,status',
        
    ];

    protected $relationsShowInAssociationItem = [
        'accountconfiguration',
        'accountconfiguration.regulationconfiguration',
        'translation:committee_id,name',
        'translations:committee_id,language_id,name',
        'translations.language',
        'commercialregistermedia',
        'companySystemMedia',
        'secretary:id',
        'secretary.translation:user_id,title,name',
        'governances',
        'secretary.translations:user_id,language_id,title,name',
        'secretary.translations.language',

        'amanuensis:id',
        'amanuensis.translation:user_id,title,name',

        'amanuensis.translations:user_id,language_id,title,name',
        'amanuensis.translations.language',

        'medias:committee_id,media_id',
        'medias.media:id,name',
        'authorities',

        'board:parent_id,type,id,parent_id,secretary_id,managing_director_id,is_permanent,start_at,end_at',
        'board.translation:committee_id,name',

        'board.translations:committee_id,language_id,name',
        'board.translations.language',

        'board.secretary:id',
        'board.secretary.translation:user_id,title,name',

        'board.secretary.translations:user_id,language_id,title,name',
        'board.secretary.translations.language',

        'board.managingDirector:id',
        'board.managingDirector.translation:user_id,title,name',

        'board.managingDirector.translations:user_id,language_id,title,name',
        'board.managingDirector.translations.language',

        'board.amanuensis:id',
        'board.amanuensis.translation:user_id,title,name',

        'board.amanuensis.translations:user_id,language_id,title,name',
        'board.amanuensis.translations.language',
        


        'board.members:committee_id,member_id,created_at,membership_id,position_id,joining_date,shares',
        'board.members.position:id,translation_id,name',
        'board.members.membership:id,translation_id,name',
        'board.members.user:id',
        'board.members.user.translation:user_id,title,name',
        'board.members.user.translations:user_id,language_id,title,name',
        'board.members.user.translations.language',



        

        'members:id,committee_id,member_id,created_at,membership_id,position_id,joining_date,shares',
        'members.position:id,translation_id,name',
        'members.membership:id,translation_id,name',
        'members.user:id,identification_number,email,mobile,nationality_id',
        'members.user.translation:user_id,title,name',
        'members.user.translations:user_id,language_id,title,name',
        'members.user.translations.language',

        'meetings:id,committee_id,number,title,start_at,status',
        
    ];

    protected $relationsShowInList = [
        'accountconfiguration',
        'accountconfiguration.regulationconfiguration',
        'translation:committee_id,name',

        'translations:committee_id,language_id,name',
        'translations.language',

        'secretary:id',
        'secretary.translation:user_id,title,name',

        'secretary.translations:user_id,language_id,title,name',
        'secretary.translations.language',

        'amanuensis:id',
        'amanuensis.translation:user_id,title,name',

        'amanuensis.translations:user_id,language_id,title,name',
        'amanuensis.translations.language',
        'governances',
        'medias:committee_id,media_id',
        'medias.media:id,name',
        'authorities',
        'accountconfiguration',
        'committees:parent_id,type,id,parent_id,secretary_id,amanuensis_id,managing_director_id,is_permanent,start_at,end_at',
        'committees.translation:committee_id,name',

        'committees.translations:committee_id,language_id,name',
        'committees.translations.language',

        'committees.secretary:id',
        'committees.secretary.translation:user_id,title,name',

        'committees.secretary.translations:user_id,language_id,title,name',
        'committees.secretary.translations.language',

        'committees.managingDirector:id',
        'committees.managingDirector.translation:user_id,title,name',

        'committees.managingDirector.translations:user_id,language_id,title,name',
        'committees.managingDirector.translations.language',

        'committees.amanuensis:id',
        'committees.amanuensis.translation:user_id,title,name',

        'committees.amanuensis.translations:user_id,language_id,title,name',
        'committees.amanuensis.translations.language',


        'committees.members:committee_id,member_id,created_at,membership_id,position_id',
        'committees.members.position:id,translation_id,name',
        'committees.members.membership:id,translation_id,name',
        'committees.members.user:id',
        'committees.members.user.translation:user_id,title,name',
        'committees.members.user.translations:user_id,language_id,title,name',
        'committees.members.user.translations.language',

        'members:committee_id,member_id,created_at,membership_id,position_id',
        'members.position:id,translation_id,name',
        'members.membership:id,translation_id,name',
        'members.user:id',
        'members.user.translation:user_id,title,name',

        'members.user.translations:user_id,language_id,title,name',
        'members.user.translations.language',

        'meetings:id,committee_id,number,title,start_at,status',
        
    ];

    protected $relationsShowInLogs = [
        'translation:committee_id,name',

        'translations:committee_id,language_id,name',
        'translations.language',

        'memberslogs:committee_id,member_id,created_at,status,comment',
        'members:committee_id,member_id',
        'members.user:id',
        'members.user.translation:user_id,title,name',

        'members.user.translations:user_id,language_id,title,name',
        'members.user.translations.language',
        
    ];
    

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFields($action)
    {
        if($action=='item'){
            return $this->fieldsShowInItem;
        }elseif($action=='list'){
            return $this->fieldsShowInList;
        }elseif($action=='logs'){
            return $this->fieldsShowInLogs;
        }
    }


     /**
     * Return searchable fields
     *
     * @return array
     */
    public function getRelations($action)
    {
        if($action=='item'){
            return $this->relationsShowInItem;
        }elseif($action=='list'){
            return $this->relationsShowInList;
        }elseif($action=='logs'){
            return $this->relationsShowInLogs;
        }elseif($action=='associationItem'){
            return $this->relationsShowInAssociationItem;
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
     * Configure the Model
     **/
    public function model()
    {
        return Committee::class;
    }
}
