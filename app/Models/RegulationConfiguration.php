<?php

namespace App\Models;

/**
 * Class RegulationConfiguration
 *
 * @property int $id
 * @property int $language_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|User[] $users
 *
 * @package App\Models
 */
class RegulationConfiguration extends Model
{

    /** @var bool */
    public $timestamps = true;


    const MembersCount = 'MembersCount';
    const IndependentsCount = 'IndependentsCount';
    const ExecutivesCount = 'ExecutivesCount';
    const BoardDuration = 'BoardDuration';
    const TimeBeforeInvitation = 'TimeBeforeInvitation';
    const YearlyMeetingsCount = 'YearlyMeetingsCount';
    const StartMeetingQuorum = 'StartMeetingQuorum';
    const VotingWeightPresident = 'VotingWeightPresident';
    const VotingDelegate = 'VotingDelegate';
    const ExternalVotingDelegate = 'ExternalVotingDelegate';
   
    
    const StartFirstSecondQuorum = 'StartFirstSecondQuorum';
    const StartFirstAssociationQuorum = 'StartFirstAssociationQuorum';
    const YearlyAssociationsCount = 'YearlyAssociationsCount';
    const SharesAssociationsAttendee = 'SharesAssociationsAttendee';
    const TimeBeforeFirstAssociationInvitation = 'TimeBeforeFirstAssociationInvitation';
    const TimeBeforeSecondAssociationInvitation = 'TimeBeforeSecondAssociationInvitation';
    

    /** @var string */
    protected $table = 'regulations_configurations';

    /** @var array */
    protected $fillable = [
        'id',
        'language_id',
        'regulation_id',
        'translation_id',
        'is_board',
        'is_committee',
        'is_association',
        'association_code',
        'name',
        'code',
        'values_count',
        'status'

    ];

    /**
     * Language.
     *
     * @return BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id','id');
    }

    /**
     * Language.
     *
     * @return BelongsTo
     */
    public function regulation()
    {
       // return $this->belongsTo(Regulation::class, 'regulation_id','id');
       // return $this->hasOne(Regulation::class, 'id', 'regulation_id');
       return $this->hasOne(Regulation::class, 'translation_id', 'regulation_id')->where('language_id',$this->getLangIdFromLocale())->latest();

    }

}
