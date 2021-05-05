<?php

namespace App\Models;

use IonGhitun\MysqlEncryption\Models\BaseModel;



/**
 * Class Model
 *
 * Each model should extend this class.
 *
 * @package App\Models
 */
class Model extends BaseModel
{
    /** @var array */
    protected $visible = [];

    /** @var array */
    protected $sortable = [];

    /** @var array */
    protected $searchable = [];

    /** @var array */
    protected $filterable = [];

    /**
     * Get sortable columns
     *
     * @return array
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * Get searchable columns
     *
     * @return array
     */
    public function getSearchable()
    {
        return $this->searchable;
    }

    /**
     * Get filterable columns
     *
     * @return array
     */
    public function getFilterable()
    {
        return $this->filterable;
    }

    public function getLangIdFromLocale()
    {
        return (app()->getLocale() == 'ar') ? 1 : 2;
    }
  

    public function getUpdatedAtAttribute($date)
    {
        return $this->convertDate($date);   
    }

    public function getCreatedAtAttribute($date)
    {
        return $this->convertDate($date);   
    }

    public function getStartAtAttribute($date)
    {
        return $this->convertDate($date);   
    }

    public function getEndAtAttribute($date)
    {
        return $this->convertDate($date);   
    }

    public function getBirthDateAttribute($date)
    {
        return $this->convertDate($date);
    }
    

    public function convertDate($date){
        
        if($date && true){

            $languages_codes=array(1=>'ar',2=>'en','ar'=>'ar','en'=>'en');

            \Carbon\Carbon::setLocale(app()->getLocale());
            \Alkoumi\LaravelHijriDate\Hijri::setLang(app()->getLocale());

            $dates['gregorian']['date']=\Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM, YYYY');
            $dates['gregorian']['time']=\Carbon\Carbon::parse($date)->isoFormat('h:mm A');


            $dates['hijri']['date']=\Alkoumi\LaravelHijriDate\Hijri::Date('l, j F, Y', $date);
            $dates['hijri']['time']=\Alkoumi\LaravelHijriDate\Hijri::Date('h:i a', $date);

            $dates['full']=$date;
        }else{
            $dates=NULL;
        }
        
        return $dates;
        
    }
}
