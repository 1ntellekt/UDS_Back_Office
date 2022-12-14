<?php

namespace App\Services\product;

use App\Components\MsClient;
use App\Components\UdsClient;
use App\Http\Controllers\BackEnd\BDController;
use App\Services\MetaServices\MetaHook\AttributeHook;
use App\Services\MetaServices\MetaHook\CurrencyHook;
use App\Services\MetaServices\MetaHook\PriceTypeHook;
use App\Services\MetaServices\MetaHook\UomHook;
use GuzzleHttp\Exception\ClientException;

class ProductCreateMsService
{

    private AttributeHook $attributeHookService;
    private CurrencyHook $currencyHookService;
    private PriceTypeHook $priceTypeHookService;
    private UomHook $uomHookService;

    /**
     * @param AttributeHook $attributeHookService
     * @param CurrencyHook $currencyHookService
     * @param PriceTypeHook $priceTypeHookService
     * @param UomHook $uomHookService
     */
    public function __construct(
        AttributeHook $attributeHookService,
        CurrencyHook $currencyHookService,
        PriceTypeHook $priceTypeHookService,
        UomHook $uomHookService
    )
    {
        $this->attributeHookService = $attributeHookService;
        $this->currencyHookService = $currencyHookService;
        $this->priceTypeHookService = $priceTypeHookService;
        $this->uomHookService = $uomHookService;
    }

    //Add products to MS from UDS
    public function insertToMs($data)
    {
        //dd($data);
        $folderMeta = $this->getFolderMetaById($data['folder_id'],$data['tokenMs']);

        return $this->notAddedInMs(
            $data['tokenMs'],
            $data['apiKeyUds'],
            $data['companyId'],
            $folderMeta,
            $data['accountId']
        );
    }

    private function getMsCheck($apiKeyMs)
    {
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/product";
        $client = new MsClient($apiKeyMs);
        $json = $client->get($url);
        $propertyIds = [];

        foreach ($json->rows as $row){
            if (property_exists($row, 'attributes')){
                foreach ($row->attributes as $attribute){
                    if ($attribute->name == "id (UDS)"){
                        $propertyIds[] = $attribute->value;
                    }
                }
            }
        }
        return $propertyIds;
    }

    private function getUds($url,$companyId, $apiKeyUds)
    {
        //$url = "https://api.uds.app/partner/v2/goods?max=50";
        $client = new UdsClient($companyId,$apiKeyUds);
        return $client->get($url);
    }

    private function notAddedInMs($apiKeyMs,$apiKeyUds,$companyId, $parentFolder, $accountId)
    {
        //$productsMs = $this->getMsCheck($apiKeyMs);
        $hrefAttrib = $this->attributeHookService->getProductAttribute("id (UDS)",$apiKeyMs)->href;
        $offset = 0;
        while ($this->haveRowsInResponse($url,$offset,$companyId,$apiKeyUds)){
            $productsUds = $this->getUds($url,$companyId,$apiKeyUds);
            foreach ($productsUds->rows as $productUds){
                $currId = "".$productUds->id;
                //echo $productUds->name."<br>";
                //if (!$this->isProductExistsMs($currId,$hrefAttrib,$apiKeyMs)){
                if ($productUds->data->type == "ITEM"){
                    if (!$this->isProductExistsMs($currId,$hrefAttrib,$apiKeyMs)){
                        $this->createProductMs($apiKeyMs,$productUds,$accountId,$parentFolder);
                    }
                    // $count++;
                }
                elseif ($productUds->data->type == "VARYING_ITEM"){
                    if (!$this->isProductExistsMs($currId,$hrefAttrib,$apiKeyMs)){
                        $this->createVariantProduct($apiKeyMs,$productUds,$accountId,$parentFolder);
                    }
                    // $count++;
                }
                elseif ($productUds->data->type == "CATEGORY"){
                    $category = $this->createCategoryMs(
                        $apiKeyMs,
                        $productUds->name,
                        $productUds->id,
                        $accountId,
                        $parentFolder
                    );
                    //dd($category);
                    set_time_limit(3600);
                    $this->addProductsByCategoryUds(
                        $hrefAttrib,
                        $category->meta,
                        $productUds->id,
                        $companyId,
                        $apiKeyUds,
                        $apiKeyMs,
                        $accountId
                    );
                }
                // }
            }
            $offset += 50;
        }
        return [
            "message" => "Successful export products to MS",
        ];
    }

    private function haveRowsInResponse(&$url,$offset,$companyId,$apiKeyUds,$nodeId=0): bool
    {
        $url = "https://api.uds.app/partner/v2/goods?max=50&offset=".$offset;
        if ($nodeId > 0){
            $url = $url."&nodeId=".$nodeId;
        }
        $client = new UdsClient($companyId,$apiKeyUds);
        $json = $client->get($url);
        return count($json->rows) > 0;
    }

    private function isProductExistsMs($nodeId, $hrefMsAttribProduct, $apiKeyMs): bool
    {
        $urlToFind = "https://online.moysklad.ru/api/remap/1.2/entity/product?filter="
            .$hrefMsAttribProduct."=".$nodeId;
        //dd($urlToFind);
        $client = new MsClient($apiKeyMs);
        $json = $client->get($urlToFind);
        return ($json->meta->size > 0);
    }

    private function addProductsByCategoryUds(
        $hrefProductId,$parentCategoryMeta,$nodeId, $companyId, $apiKeyUds,$apiKeyMs,$accountId
    ){
        //$url = "https://api.uds.app/partner/v2/goods?max=50&nodeId=".$nodeId;
        //$client = new UdsClient($companyId,$apiKeyUds);
        //$json = $client->get($url);
        //if (count($json->rows) == 0) return;
        $offset = 0;
        while ($this->haveRowsInResponse($url,$offset,$companyId,$apiKeyUds,$nodeId)){
            $client = new UdsClient($companyId,$apiKeyUds);
            $json = $client->get($url);
            foreach ($json->rows as $row){
                $currId = "".$row->id;
                if ($row->data->type == "CATEGORY"){
                    $category = $this->createCategoryMs($apiKeyMs,$row->name,$row->id,$accountId,$parentCategoryMeta);
                    // dd($category->pathName);
                    if ($category != null)
                    $this->addProductsByCategoryUds(
                        $hrefProductId,
                        $category->meta,
                        $row->id,
                        $companyId,
                        $apiKeyUds,
                        $apiKeyMs,
                        $accountId
                    );
                }
                elseif ($row->data->type == "ITEM"){
                    if (!$this->isProductExistsMs($currId,$hrefProductId,$apiKeyMs)){
                        $this->createProductMs($apiKeyMs,$row,$accountId,$parentCategoryMeta);
                    }
                }
                elseif ($row->data->type == "VARYING_ITEM"){
                    if (!$this->isProductExistsMs($currId,$hrefProductId,$apiKeyMs)){
                        $this->createVariantProduct($apiKeyMs,$row,$accountId,$parentCategoryMeta);
                    }
                }
            }
            $offset += 50;
        }
    }

    private function createCategoryMs($apiKeyMs, $nameFolder,$externalCode,$accountId,$parentFolder = null)
    {
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/productfolder";
        $client = new MsClient($apiKeyMs);

        $jsonToCheck = $client->get($url);

        $foundedCategory = null;
        foreach ($jsonToCheck->rows as $row){
            if ($row->externalCode == $externalCode){
                $foundedCategory = $row;
                break;
            }
        }

        if ($foundedCategory != null){
            return $foundedCategory;
        } else {
            //dd($nameFolder,$pathName);
            $bodyCategory["name"]= $nameFolder;
            $bodyCategory["externalCode"] = "".$externalCode;
            if ($parentFolder != null){
                $bodyCategory["productFolder"] = [
                    "meta" => $parentFolder,
                ];
            }
            try {
                return $client->post($url,$bodyCategory);
            }catch (ClientException $e){
                $bd = new BDController();
                $bd->errorProductLog($accountId,$e->getMessage());
                return null;
            }
        }
    }

    private function createProductMs($apiKeyMs, $productUds,$accountId, $productFolderMeta = null)
    {
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/product";
        $bodyProduct["name"] = $productUds->name;

        $bodyProduct["salePrices"] = [
            0 => [
                "value" => $productUds->data->price * 100,
                "currency" => $this->currencyHookService->getKzCurrency($apiKeyMs),
                "priceType" => $this->priceTypeHookService->getPriceType("???????? ??????????????",$apiKeyMs),
            ],
        ];

        $nameUom = $this->getUomMsByUds($productUds->data->measurement);
        $bodyProduct["uom"] = $this->uomHookService->getUom($nameUom,$apiKeyMs);

        if ($productUds->data->sku != null){
            $bodyProduct["article"] = $productUds->data->sku;
        }

        $countAttribute = 0;
        if ($productUds->data->offer != null){
            $bodyProduct["attributes"][$countAttribute] = [
                "meta" => $this->attributeHookService
                    ->getProductAttribute("?????????????????? ?????????? (UDS)", $apiKeyMs),
                "name" => "?????????????????? ?????????? (UDS)",
                "value" => true,
            ];
            $countAttribute++;
            if ($productUds->data->offer->skipLoyalty){
                $bodyProduct["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("???? ?????????????????? ???????????????? ?????????????????? (UDS)", $apiKeyMs),
                    "name" => "???? ?????????????????? ???????????????? ?????????????????? (UDS)",
                    "value" => true,
                ];
                $countAttribute++;
            }
            $bodyProduct["salePrices"][1] = [
                "value" => $productUds->data->offer->offerPrice * 100,
                "currency" => $this->currencyHookService->getKzCurrency($apiKeyMs),
                "priceType" => $this->priceTypeHookService->getPriceType("??????????????????",$apiKeyMs),
            ];
        }

        if ($productUds->data->increment != null){

            $measurement = $productUds->data->measurement;
            $increment = 0;

            if ($measurement == "MILLILITRE" || $measurement == "GRAM"){
                $increment = $productUds->data->increment / 1000.0;
            } elseif($measurement == "CENTIMETRE"){
                $increment = $productUds->data->increment / 100.0;
            }

            $bodyProduct["attributes"][$countAttribute] = [
                "meta" => $this->attributeHookService
                    ->getProductAttribute("?????? ???????????????? ???????????????? (UDS)",$apiKeyMs),
                "name" => "?????? ???????????????? ???????????????? (UDS)",
                "value" => floatval($increment),
            ];
            $countAttribute++;
        }

        if ($productUds->data->minQuantity != null){

            $measurement = $productUds->data->measurement;
            $minQuantity = 0;

            if ($measurement == "MILLILITRE" || $measurement == "GRAM"){
                $minQuantity = $productUds->data->minQuantity / 1000.0;
            } elseif($measurement == "CENTIMETRE"){
                $minQuantity = $productUds->data->minQuantity / 100.0;
            }

            $bodyProduct["attributes"][$countAttribute] = [
                "meta" => $this->attributeHookService
                    ->getProductAttribute("?????????????????????? ???????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                "name" => "?????????????????????? ???????????? ???????????? ???????????????? ???????????? (UDS)",
                "value" => floatval($minQuantity),
            ];
            $countAttribute++;
            $bodyProduct["attributes"][$countAttribute] = [
                "meta" => $this->attributeHookService
                    ->getProductAttribute("?????????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                "name" => "?????????????? ???????????????? ???????????? (UDS)",
                "value" => true,
            ];
            $countAttribute++;

            //up min and main price

            if($productUds->data->measurement == "MILLILITRE" || $productUds->data->measurement == "GRAM"){
                $bodyProduct["salePrices"][0]["value"] *= 1000;
                if($productUds->data->offer == null){
                    $bodyProduct["attributes"][$countAttribute] = [
                        "meta" => $this->attributeHookService
                            ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                        "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                        "value" => $productUds->data->price,
                    ];
                } else {
                    $bodyProduct["attributes"][$countAttribute] = [
                        "meta" => $this->attributeHookService
                            ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                        "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                        "value" => $productUds->data->offer->offerPrice / 1000.0,
                    ];
                }
                $countAttribute++;
            }
            elseif ($productUds->data->measurement == "CENTIMETRE"){
                $bodyProduct["salePrices"][0]["value"] *= 100;
                if($productUds->data->offer == null){
                    $bodyProduct["attributes"][$countAttribute] = [
                        "meta" => $this->attributeHookService
                            ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                        "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                        "value" => $productUds->data->price,
                    ];
                } else {
                    $bodyProduct["attributes"][$countAttribute] = [
                        "meta" => $this->attributeHookService
                            ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                        "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                        "value" => $productUds->data->offer->offerPrice / 100.0,
                    ];
                }
                $countAttribute++;
            }

        }

        if ($productUds->data->measurement == "METRE"){

            //if ($countAttribute == 0) $countAttribute++;
            if ($productUds->data->offer == null){
                $bodyProduct["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                    "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                    "value" => $productUds->data->price / 100.0,
                ];
            } else {
                $bodyProduct["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                    "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                    "value" => $productUds->data->offer->offerPrice / 100.0,
                ];
            }

            $countAttribute++;
        }
        elseif ($productUds->data->measurement == "LITRE" || $productUds->data->measurement == "KILOGRAM"){

           // if ($countAttribute == 0) $countAttribute++;

            if($productUds->data->offer == null){
                $bodyProduct["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                    "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                    "value" => $productUds->data->price / 1000.0,
                ];
            } else {
                $bodyProduct["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",$apiKeyMs),
                    "name" => "???????? ???????????????????????? ?????????????? ???????????? ???????????????? ???????????? (UDS)",
                    "value" => $productUds->data->offer->offerPrice / 1000.0,
                ];
            }


            $countAttribute++;
        }

        //if ($countAttribute == 0) $countAttribute++;

        $bodyProduct["attributes"][$countAttribute] = [
            "meta" => $this->attributeHookService
                ->getProductAttribute("id (UDS)",$apiKeyMs),
            "name" => "id (UDS)",
            "value" => "".$productUds->id,
        ];

        $bodyProduct["externalCode"] = "".$productUds->id;

        if ($productFolderMeta != null){
            $bodyProduct["productFolder"] = [
                "meta" => $productFolderMeta,
            ];
        }

        //dd($bodyProduct);

        $client = new MsClient($apiKeyMs);
        try {
            $client->post($url,$bodyProduct);
        }catch (ClientException $e){
            $bd = new BDController();
            $bd->errorProductLog($accountId,$e->getMessage());
        }

    }

    private function createVariantProduct($apiKeyMs, $productVar,$accountId, $productFolderMeta = null){
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/product";
        $client = new MsClient($apiKeyMs);
        foreach ($productVar->data->variants as $variant){
            $bodyProductVar["name"] = $variant->name."(".$productVar->name.")";
            if ($variant->sku != null){
                $bodyProductVar["article"] = $variant->sku;
            }
            $bodyProductVar["salePrices"] = [
                0 => [
                    "value" => $variant->price * 100,
                    "currency" => $this->currencyHookService->getKzCurrency($apiKeyMs),
                    "priceType" => $this->priceTypeHookService->getPriceType("???????? ??????????????",$apiKeyMs),
                ],
            ];
            $bodyProductVar["uom"] = $this->uomHookService->getUom("????",$apiKeyMs);
            $countAttribute = 0;
            if ($variant->offer != null){
                $bodyProductVar["attributes"][$countAttribute] = [
                    "meta" => $this->attributeHookService
                        ->getProductAttribute("?????????????????? ?????????? (UDS)", $apiKeyMs),
                    "name" => "?????????????????? ?????????? (UDS)",
                    "value" => true,
                ];
                $countAttribute++;
                if ($variant->offer->skipLoyalty){
                    $bodyProductVar["attributes"][$countAttribute] = [
                        "meta" => $this->attributeHookService
                            ->getProductAttribute("???? ?????????????????? ???????????????? ?????????????????? (UDS)", $apiKeyMs),
                        "name" => "???? ?????????????????? ???????????????? ?????????????????? (UDS)",
                        "value" => true,
                    ];
                    $countAttribute++;
                }
                $bodyProductVar["salePrices"][1] = [
                    "value" => $variant->offer->offerPrice * 100,
                    "currency" => $this->currencyHookService->getKzCurrency($apiKeyMs),
                    "priceType" => $this->priceTypeHookService->getPriceType("??????????????????",$apiKeyMs),
                ];
            }
            $bodyProductVar["attributes"][$countAttribute] = [
                "meta" => $this->attributeHookService
                    ->getProductAttribute("id (UDS)",$apiKeyMs),
                "name" => "id (UDS)",
                "value" => "".$productVar->id,
            ];

            if ($productFolderMeta != null){
                $bodyProductVar["productFolder"] = [
                    "meta" => $productFolderMeta,
                ];
            }

            try {
                $client->post($url,$bodyProductVar);
            }catch (ClientException $e){
                $bd = new BDController();
                $bd->errorProductLog($accountId,$e->getMessage());
            }
        }
    }

    private function getUomMsByUds($nameUom): string
    {
        $nameUomMs = "";
        switch ($nameUom){
            case "PIECE":
                $nameUomMs = "????";
                break;
            case "CENTIMETRE":
                $nameUomMs = "????";
                break;
            case "METRE":
                $nameUomMs = "??";
                break;
            case "MILLILITRE":
                $nameUomMs = "????";
                break;
            case "LITRE":
                $nameUomMs = "??; ????3";
                break;
            case "GRAM":
                $nameUomMs = "??";
                break;
            case "KILOGRAM":
                $nameUomMs = "????";
                break;
        }
        return $nameUomMs;
    }

    private function getFolderMetaById($folderId, $apiKeyMs)
    {
        $url = "https://online.moysklad.ru/api/remap/1.2/entity/productfolder/".$folderId;
        $client = new MsClient($apiKeyMs);
        $json = $client->get($url);
        return $json->meta;
    }

}
