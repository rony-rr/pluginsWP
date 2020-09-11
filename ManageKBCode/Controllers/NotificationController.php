<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Group;
use App\Domain;
use Mail;
use App\PostQueue;
use App\Mail\RSSNotification;

//
use GuzzleHttp\Client;
//

class NotificationController extends Controller
{
    public function sendNotification(Request $res){
        $to = [
            //['email' => 'florian.felsing@googlemail.com', 'name' => 'Florian Felsing'],
            ['email' => 'dennisalvarado89@gmail.com', 'name' => 'Dennis Alvarado']
        ];
        Mail::to($to)->send(new RSSNotification(''));
        return "meh";
    }
}
