<?php

namespace App\Http\Controllers;

use App\Components\UdsClient;
use App\Http\Controllers\Config\getSettingVendorController;
use App\Http\Controllers\GuzzleClient\ClientMC;
use Faker\Provider\File;
use Illuminate\Http\Request;
use Throwable;
use function Symfony\Component\Translation\t;

class postController extends Controller
{
    public function postClint(Request $request, $accountId){
        $Setting = new getSettingVendorController($accountId);
        $TokenMC = $Setting->TokenMoySklad;

        $url = "https://online.moysklad.ru/api/remap/1.2/entity/counterparty";

        $Clint = new ClientMC($url, $TokenMC);

        $participant = $request->participant;

        $email = $this->ClintNullable($request->email);
        //dd($email);


        $body = [
            "name" => $request->displayName,
            "phone" => $request->phone,
            "email" => $email,
            "externalCode" => (string) $participant['id'],
        ];
        try {
            $Clint->requestPost($body);
        } catch (Throwable $exception){
            dd($exception);
        }



    }

    public function ClintNullable($item){
        if ($item == null){
            return '';
        } else {
            return $item;
        }
    }


    public function postOrder(Request $request, $accountId){
        $Setting = new getSettingVendorController($accountId);
        $TokenMC = $Setting->TokenMoySklad;

        if ($Setting->creatDocument == "1"){
            $url = "https://online.moysklad.ru/api/remap/1.2/entity/customerorder";
            $Clint = new ClientMC($url, $TokenMC);

            $organization = $this->metaOrganization($TokenMC, $Setting->Organization);
            $organizationAccount = $this->metaOrganizationAccount($TokenMC, $Setting->PaymentAccount, $Setting->Organization);
            $agent = $this->metaAgent($TokenMC, $request->customer['id']);
            $state = $this->metaState($TokenMC, $Setting->NEW);
            $store = $this->metaStore($TokenMC, $Setting->Store);
            $salesChannel = $this->metaSalesChannel($TokenMC, $Setting->Saleschannel);
            $project = $this->metaProject($TokenMC, $Setting->Project);

            $positions = $this->metaPositions($TokenMC, $request->items, $request->purchase, $request->customer['membershipTier']['maxScoresDiscount']);
            $shipmentAddress = $this->ShipmentAddress($request->delivery);
            $externalCode = $this->CheckExternalCode($TokenMC, $request->id);

            //dd($organization);

            $body = [
                "organization" => $organization,
                "organizationAccount" => $organizationAccount,
                "agent" => $agent,//Создавать АГЕНТА НАДО
                "state" => $state,
                "store" => $store,
                "salesChannel" => $salesChannel,
                "project" => $project,

                "positions" => $positions,
                "shipmentAddress" => $shipmentAddress,
                "externalCode" => $externalCode,
            ];
            //dd(($body));
            try {
                if ($externalCode != null) $Clint->requestPost($body);
            } catch (Throwable $exception){
                dd($exception);
            }

        }



    }

    public function metaOrganization($apiKey, $Organization){
        $url_organization = "https://online.moysklad.ru/api/remap/1.2/entity/organization/".$Organization;
        $Clint = new ClientMC($url_organization, $apiKey);
        $Body = $Clint->requestGet()->meta;
        $href = $Body->href;
        $type = $Body->type;
        $mediaType = $Body->mediaType;
        return [
           'meta' => [
               'href'=> $href,
               'type'=> $type,
               'mediaType'=> $mediaType,
           ]
        ];
    }

    public function metaOrganizationAccount($apiKey, $PaymentAccount, $Organization){

        if ($PaymentAccount == null) return null;

        $url = "https://online.moysklad.ru/api/remap/1.2/entity/organization/".$Organization."/accounts";
        $Clint = new ClientMC($url, $apiKey);
        $Body = $Clint->requestGet()->rows;
        foreach ($Body as $item){
            if ($item->accountNumber == $PaymentAccount){
                $href = $item->meta->href;
                $type = $item->meta->type;
                $mediaType = $item->meta->mediaType;
                break;
            } else {
                $href = null;
            }
        }

        if ($href == null) return null;

        return [
           'meta' => [
               'href'=> $href,
               'type'=> $type,
               'mediaType'=> $mediaType,
           ]
        ];
    }

    public function metaAgent($apiKey, $agent){
        $url_organization = "https://online.moysklad.ru/api/remap/1.2/entity/counterparty?filter=externalCode~".$agent;
        $Clint = new ClientMC($url_organization, $apiKey);
        $Body = $Clint->requestGet()->rows[0]->meta; //Может не быть

        $href = $Body->href;
        $type = $Body->type;
        $mediaType = $Body->mediaType;

        return [
            'meta' => [
                'href'=> $href,
                'type'=> $type,
                'mediaType'=> $mediaType,
            ]
        ];
    }

    public function metaState($apiKey, $Status){

        if ($Status == null){
            return null;
        }
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/customerorder/metadata/";
        $Clint = new ClientMC($url, $apiKey);
        $Body = $Clint->requestGet()->states;
        foreach ($Body as $item){
            if ($item->name == $Status) {
                $href = $item->meta->href;
                $type = $item->meta->type;
                $mediaType = $item->meta->mediaType;
                break;
            } else $href = null;
        }
        if ($href == null) return null;
        return [
            'meta' => [
                'href'=> $href,
                'type'=> $type,
                'mediaType'=> $mediaType,
            ]
        ];
    }

    public function metaStore($apiKey, $StoreName){
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/store/";
        $Clint = new ClientMC($url, $apiKey);
        $body = $Clint->requestGet()->rows;
        foreach ($body as $item){
            if ($item->name == $StoreName){
                $href = $item->meta->href;
                $type = $item->meta->type;
                $mediaType = $item->meta->mediaType;
                break;
            } else $href = null;
        }
        if ($href == null) return null;
        return [
            'meta' => [
                'href'=> $href,
                'type'=> $type,
                'mediaType'=> $mediaType,
            ]
        ];
    }

    public function metaSalesChannel($apiKey, $salesChannelName){

        if ($salesChannelName == null) return null;

        $url = "https://online.moysklad.ru/api/remap/1.2/entity/saleschannel?search=".$salesChannelName;
        $Clint = new ClientMC($url, $apiKey);
        $Body = $Clint->requestGet()->rows[0]->meta;
        $href = $Body->href;
        $type = $Body->type;
        $mediaType = $Body->mediaType;
        return [
            'meta' => [
                'href'=> $href,
                'type'=> $type,
                'mediaType'=> $mediaType,
            ]
        ];
    }

    public function metaProject($apiKey, $Project){
        if ($Project == null) return null;

        $url = "https://online.moysklad.ru/api/remap/1.2/entity/project?search=".$Project;
        $Clint = new ClientMC($url, $apiKey);
        $Body = $Clint->requestGet()->rows[0]->meta;
        $href = $Body->href;
        $type = $Body->type;
        $mediaType = $Body->mediaType;
        return [
            'meta' => [
                'href'=> $href,
                'type'=> $type,
                'mediaType'=> $mediaType,
            ]
        ];
    }

    public function metaPositions($apiKey, $UDSitem, $purchase, $maxScoresDiscount){
        $urlMeta = "https://online.moysklad.ru/api/remap/1.2/entity/product/metadata/attributes";
        $Client = new ClientMC($urlMeta, $apiKey);
        $BodyMeta = $Client->requestGet()->rows;
        foreach ($BodyMeta as $BodyMeta_item){
            if ($BodyMeta_item->name == 'id (UDS)'){ $BodyMeta = $BodyMeta_item->meta->href;
                break;
            }
        }

        $total = $purchase["total"] - $purchase["skipLoyaltyTotal"];
        $pointsPercent = $purchase["points"] * 100 / $total;

        $Result = [];
        $priceResult = 0;
        foreach ($UDSitem as $id=>$item){
            $urlProduct = 'https://online.moysklad.ru/api/remap/1.2/entity/product?filter='.$BodyMeta.'='.$item['id'];
            $Client = new ClientMC($urlProduct, $apiKey);
            $body = $Client->requestGet()->rows;
            //dd($body);
            $bodyIndex = 0;
            if  (isset($body[1])) {
                foreach ($body as $bodyCheckID=>$bodyCheckItem){
                    //$tmp = $item['variantName']."(".$item['name'].")";
                    if ($item['variantName']."(".$item['name'].")" == $bodyCheckItem->name ) {
                        $bodyIndex = $bodyCheckID;
                        break;
                    } else $bodyIndex = 0 ;
                }
            } else $bodyIndex = 0;
            $body = $body[$bodyIndex];
            foreach ($body->attributes as $attributesItem){
                if ('Не применять бонусную программу (UDS)' == $attributesItem->name){
                    if ($attributesItem->value == false) $discount = $pointsPercent;
                    break;
                } else $discount = 0;
            }

            $assortment = [ 'meta' => [
                     'href' => $body->meta->href,
                     'type' => $body->meta->type,
                     'mediaType' => $body->meta->mediaType,
                ]
            ];
            $ArrayItem = [
                'quantity' => $item['qty'],
                'price' => $item['price']*100,
                'assortment' => $assortment,
                'discount' => $discount,
                'reserve' => $item['qty'],
            ];
            $Result[] = $ArrayItem;

        }


       return $Result;
    }

    public function ShipmentAddress($delivery){
        $DELIVERY = "";
        $PICKUP = "";
        if ($delivery["branch"]) { $PICKUP = "(САМОВЫВОЗ) ".$delivery["branch"]["displayName"];
            return $PICKUP;
        }
        if ($delivery["address"]) {
            $DELIVERY = $delivery["address"];
            $deliveryCase = $delivery["deliveryCase"];
            return $DELIVERY;
        }
        return null;
    }

    public function CheckExternalCode($apiKey, $externalCode){
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/customerorder?filter=externalCode~".$externalCode;
        $Clint = new ClientMC($url, $apiKey);
        $body = $Clint->requestGet()->rows;
        if (!$body) return (string) $externalCode;
        else return null;
    }

}
