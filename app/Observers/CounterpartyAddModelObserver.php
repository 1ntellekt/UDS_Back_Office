<?php

namespace App\Observers;

use App\Http\Controllers\BackEnd\BDController;
use App\Models\counterparty_add;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Promise\Utils;

class CounterpartyAddModelObserver
{
    public function created(counterparty_add $infoLogModel)
    {


        $all = counterparty_add::all('tokenMC');

        foreach($all as $item){

            $query = counterparty_add::query();
            $logs = $query->where('tokenMC',$item->tokenMC)->get();
            if(count($logs) > 1){
                DB::table('counterparty_adds')
                    ->where('tokenMC','=',$item->tokenMC)
                    ->orderBy('created_at', 'ASC')
                    ->limit(1)
                    ->delete();
            }

        }

        //agent add
        //set Attributes
        $client = new Client(['base_uri' => 'http://uds/api/']);

        $promises = [
            'agents' => $client->postAsync('agentMs',[
                "tokenMs" => '',
                "companyId" => "",
                "apiKeyUds" => "",
                "accountId" => ""
            ]),
            'attributes' => $client->postAsync('attributes',[
                "tokenMs" => 'required|string',
                "accountId" => "required|string"
            ])
        ];

/*        try {
            //$responses = Utils::unwrap($promises);
        } catch ()*/


        $infoLogModel->delete();
    }


    public function updated(counterparty_add $infoLogModel)
    {
        //
    }

    public function deleted(counterparty_add $infoLogModel)
    {
        //
    }

    public function restored(counterparty_add $infoLogModel)
    {
        //
    }

    public function forceDeleted(counterparty_add $infoLogModel)
    {
        //
    }
}
