<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Language;
use Closure;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
       
        $languages_CODEs=array(Language::CODE_AR,Language::CODE_EN);
        $languages_ids=array(
            'ar'=>Language::ID_AR,
            'en'=>Language::ID_EN,
        );
        if(in_array(strtolower($request->header('local')),$languages_CODEs)){
            app()->setLocale(strtolower($request->header('local')));
            return $next($request);
        }else{
            app()->setLocale(Language::CODE_AR);
            return $next($request);
        }
        
    }
}
