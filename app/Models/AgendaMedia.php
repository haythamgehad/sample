<?php

namespace App\Models;


/**
 * Class AgendaMedia
 * @package App\Models
 * @version March 10, 2020, 2:12 pm UTC
 *
 * @property integer agenda_id
 * @property integer media_id
 */
class AgendaMedia extends Model
{

    public $table = 'agendas_medias';
    



    public $fillable = [
        'agenda_id',
        'media_id'
    ];


     /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     **/
    public function media()
    {
        return $this->hasOne(Media::class, 'id', 'media_id');
    }

    
}
