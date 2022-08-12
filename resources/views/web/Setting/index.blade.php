
@extends('layout')

@section('content')


    <div class="content p-4 mt-2 bg-white text-Black rounded">
        <h4> <i class="fa-solid fa-gears text-orange"></i> Данные для интеграции</h4>

        <br>
        @isset($message)

            <div class="{{$message['alert']}}"> {{ $message['message'] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            </div>

        @endisset


        <form action="  {{ route( 'setSettingIndex' , [ 'accountId' => $accountId,  'isAdmin' => $isAdmin ] ) }} " method="post">
        @csrf <!-- {{ csrf_field() }} -->
            <div class="mb-3 row mx-1">
                <div class="col-sm-6">
                    <div class="row">
                        <label class="mx-1">
                            <button type="button" class="btn btn-new fa-solid fa-circle-info myPopover1"
                                    data-toggle="popover" data-placement="right" data-trigger="focus"
                                    data-content="Данный ID находится в UDS &#8594; Настройки &#8594; Интеграция &#8594; Данные для интеграции ">
                            </button> ID компании </label>

                        <script> $('.myPopover1').popover(); </script>

                        <div class="col-sm-10">
                            <input type="text" name="companyId" id="companyId" placeholder="ID компании"
                                   class="form-control form-control-orange" required maxlength="255" value="{{$companyId}}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <label class="mx-1">
                            <button type="button" class="btn btn-new fa-solid fa-circle-info myPopover2"
                                    data-toggle="popover" data-placement="right" data-trigger="focus"
                                    data-content="Данный ID находится в UDS &#8594; Настройки &#8594; Интеграция &#8594; Данные для интеграции ">
                            </button>  API Key </label>

                        <script> $('.myPopover2').popover(); </script>

                        <div class="col-sm-10">
                            <input type="text" name="TokenUDS" id="TokenUDS" placeholder="API Key"
                                   class="form-control form-control-orange" required maxlength="255" value="{{$TokenUDS}}">
                        </div>
                    </div>
                </div>

            </div>

            <div class="mb-3 row mx-1">
                <div class="col-sm-6">
                    <label class="mx-1">
                        <button type="button" class="btn btn-new fa-solid fa-circle-info myPopover3"
                                data-toggle="popover" data-placement="right" data-trigger="focus"
                                data-content="По данному складу будут отправляться остатки в UDS и на данный склад будет создаваться заказ">
                        </button>  Выберите склад, для остатков товара:  </label>

                    <script> $('.myPopover3').popover(); </script>


                    <div class="col-sm-10">
                        <select name="Store" class="form-select text-black " >
                            @foreach($Body_store as $Body_store_item)
                                @if ( $Store == $Body_store_item->name )
                                    <option selected value="{{ $Body_store_item->name }}"> {{ ($Body_store_item->name) }} </option>
                                @else
                                    <option value="{{ $Body_store_item->name }}"> {{ ($Body_store_item->name) }} </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <hr class="href_padding">



            <button class="btn btn-outline-dark textHover" data-bs-toggle="modal" data-bs-target="#modal">
                <i class="fa-solid fa-arrow-down-to-arc"></i> Сохранить </button>


        </form>
    </div>





@endsection



<style>
    .selected {
        margin-right: 0px !important;
        background-color: rgba(17, 17, 17, 0.14) !important;
        border-radius: 3px !important;
    }
    .dropdown-item:active {
        background-color: rgba(123, 123, 123, 0.14) !important;
    }

    .block {
        display: none;
        margin: 10px;
        padding: 10px;
        border: 2px solid orange;
    }

</style>
