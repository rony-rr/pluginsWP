<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\KbOption;

class SaveDataController extends Controller
{
    public function SaveData(Request $request){
        $type = $request->input('type');
        /// Actualizar estado de Automatic Hiperloop;
        if($type  == 'state_hiperloop'){
            $data = $request->input('automatic-hiper');
                
            $update = KbOption::where('option_name', 'automatic-hiperloop')->update(['option_value' => $data]);
            
        }

        return back()->with('info','You added new items, follow next step!');
    }
}
