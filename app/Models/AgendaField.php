<?php

namespace App\Models;


/**
 * Class AgendaField
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
class AgendaField extends Model
{

    /** @var bool */
    public $timestamps = true;

    /** @var string */
    protected $table = 'agendas_fields';

    /** @var array */
    protected $fillable = [
        'id',
        'agenda_id',
        'field_id',
        'value',
        'status'

    ];

   

}
