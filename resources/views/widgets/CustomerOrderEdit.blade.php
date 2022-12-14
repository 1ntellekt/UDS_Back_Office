
@extends('widgets.index')

@section('counterparty')

    <script>
        var GlobalobjectId;
        var GlobalURL;
        var GlobalxRefURL;
        var GlobalUDSOrderID;
        window.addEventListener("message", function(event) {
            var receivedMessage = event.data;
            GlobalobjectId = receivedMessage.objectId;
            if (receivedMessage.name === 'Open') {
                var oReq = new XMLHttpRequest();
                document.getElementById("success").style.display = "none";
                document.getElementById("danger").style.display = "none";
                oReq.addEventListener("load", function() {
                    var responseTextPars = JSON.parse(this.responseText);
                    var StatusCode = responseTextPars.StatusCode;
                    var message = responseTextPars.message;
                    GlobalUDSOrderID = message.id;
                    var BonusPoint = message.BonusPoint;
                    var points = message.points;

                    if (StatusCode == 200) {
                        document.getElementById("activated").style.display = "block";
                        document.getElementById("undefined").style.display = "none";

                        GlobalxRefURL = "https://admin.uds.app/admin/orders?order="+message.id;
                        window.document.getElementById("OrderID").innerHTML = message.id;
                        var icon = message.icon.replace(/\\/g, '');
                        window.document.getElementById("icon").innerHTML = icon;

                        window.document.getElementById("cashBack").innerHTML = BonusPoint;
                        window.document.getElementById("points").innerHTML = points;

                        if (message.state == "NEW") {
                            document.getElementById("ButtonComplete").style.display = "block";
                            document.getElementById("Complete").style.display = "none";
                            document.getElementById("Deleted").style.display = "none";
                        }

                        if (message.state == "COMPLETED") {
                            document.getElementById("Complete").style.display = "block";
                            document.getElementById("ButtonComplete").style.display = "none";
                            document.getElementById("Deleted").style.display = "none";
                        }

                        if (message.state == "DELETED") {
                            document.getElementById("Deleted").style.display = "block";
                            document.getElementById("Complete").style.display = "none";
                            document.getElementById("Complete").style.display = "none";
                        }


                    } else {
                        document.getElementById("activated").style.display = "none";
                        document.getElementById("undefined").style.display = "block";
                    }
                });
                GlobalURL = "{{$getObjectUrl}}" + receivedMessage.objectId;
                oReq.open("GET", GlobalURL);
                oReq.send();
            }
        });

        function xRefURL(){
            window.open(GlobalxRefURL);
        }

        function ButtonComplete(){
            var xmlHttpRequest = new XMLHttpRequest();
            xmlHttpRequest.addEventListener("load", function() {
                var responseTextPars = JSON.parse(this.responseText);
                var StatusCode = responseTextPars.StatusCode;
                if (StatusCode == 200) {
                    document.getElementById("success").style.display = "block";
                    document.getElementById("danger").style.display = "none";
                } else {
                    document.getElementById("success").style.display = "none";
                    document.getElementById("danger").style.display = "block";
                }
            });
            xmlHttpRequest.open("GET", "https://smartuds.kz/CompletesOrder/{{$accountId}}/" + GlobalUDSOrderID);
            xmlHttpRequest.send();
        }

        function update(){
            var oReq = new XMLHttpRequest();
            document.getElementById("success").style.display = "none";
            document.getElementById("danger").style.display = "none";
            oReq.addEventListener("load", function() {
                var responseTextPars = JSON.parse(this.responseText);
                var StatusCode = responseTextPars.StatusCode;
                var message = responseTextPars.message;
                GlobalUDSOrderID = message.id;
                var BonusPoint = message.BonusPoint;
                var points = message.points;

                if (StatusCode == 200) {

                    GlobalxRefURL = "https://admin.uds.app/admin/orders?order="+message.id;
                    window.document.getElementById("OrderID").innerHTML = message.id;
                    var icon = message.icon.replace(/\\/g, '');
                    window.document.getElementById("icon").innerHTML = icon;

                    window.document.getElementById("cashBack").innerHTML = BonusPoint;
                    window.document.getElementById("points").innerHTML = points;

                    if (message.state == "NEW") {
                        document.getElementById("ButtonComplete").style.display = "block";
                        document.getElementById("Complete").style.display = "none";
                        document.getElementById("Deleted").style.display = "none";
                    }

                    if (message.state == "COMPLETED") {
                        document.getElementById("Complete").style.display = "block";
                        document.getElementById("ButtonComplete").style.display = "none";
                        document.getElementById("Deleted").style.display = "none";
                    }

                    if (message.state == "DELETED") {
                        document.getElementById("Deleted").style.display = "block";
                        document.getElementById("ButtonComplete").style.display = "none";
                        document.getElementById("Complete").style.display = "none";
                    }


                } else {

                }
            });
            GlobalURL = "{{$getObjectUrl}}" + receivedMessage.objectId;
            oReq.open("GET", GlobalURL);
            oReq.send();
        }

    </script>

    @php
        $View = true;
    @endphp




    <div id="activated" class="content bg-white text-Black rounded" style="display: none">
        <div class="row uds-gradient p-2">
            <div class="col-2">
                <img src="https://smartuds.kz/Config/UDS.png" width="35" height="35" >
            </div>
            <div class="col-10 text-white mt-1 row">
                    <div class="col-11">
                        <label onclick="xRefURL()" style="cursor: pointer">
                            ?????????? ??? <span id="OrderID"></span> <span class="mx-1"></span>
                        </label>
                    </div>
                    <div class="col-1">
                        <i onclick="xRefURL()" class="fa-solid fa-arrow-up-right-from-square" style="cursor: pointer"></i>
                    </div>
            </div>

            <div class="row">
                <div class="col-8 row">
                    <div class="mx-1 mt-1">
                        <button type="submit" onclick="update()" class="myButton btn "> <i class="fa-solid fa-arrow-rotate-right"></i> </button>
                    </div>
                </div>
                <div class="col-4 bg-light rounded-pill s-min mt-1 p-1">
                    <span class="mx-1 mt-2" id="icon"></span>
                </div>
            </div>
        </div>

        <div class="row mt-3 s-min">
            <div class="col-1">

            </div>
            <div class="col-11 row">
                <div id="ButtonComplete" class="row text-center" style="display: none;">
                    <div class="row mx-1">
                        <div class="col-5 mx-1 rounded-pill bg-success">
                            <button onclick="ButtonComplete()" class="btn btn-success ">?????????????????? </button>
                        </div>
                        <div class="col-5 mx-3 rounded-pill bg-danger">
                            <button onclick="xRefURL()" class="btn btn-danger ">???????????????? </button>
                        </div>
                    </div>
                    <div id="success" class="mt-2" style="display: none">
                        <div class="row">
                            <div class="col-1"></div>
                            <div class="col-10">
                                <div class=" alert alert-success fade show in text-center "> ?????????? ???????????????? </div>
                            </div>
                        </div>
                    </div>
                    <div id="danger" class="mt-2" style="display: none">
                        <div class="row">
                            <div class="col-1"></div>
                            <div class="col-10">
                                <div id="error" class=" alert alert-danger alert-danger fade show in text-center ">  </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="Complete" class="row" style="display: none;">
                    <div class="row mt-2">
                        <div class="col-10">
                            ?????????????? ??????????????????:
                        </div>
                        <div class="col-1">
                            <span class="p-1 px-3 text-white bg-primary rounded-pill" id="points"></span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-10">
                            ?????????????? ??????????????????:
                        </div>
                        <div class="col-1">
                            <span class="p-1 px-3 text-white bg-success rounded-pill" id="cashBack"></span>
                        </div>
                    </div>

                </div>
                <div id="Deleted" class="row" style="display: none;">
                    <div class="bg-white text-Black rounded">
                        <div class="text-center">
                            <div class="p-3 mb-2 bg-danger rounded text-white">
                                ?????????? ?????? ?????????????? ?? UDS
                                <i class="fa-solid fa-delete-left"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div id="undefined" class="bg-white text-Black rounded" style="display: none">
        <div class="text-center">
            <div class="p-3 mb-2 bg-danger text-white">
                <i class="fa-solid fa-ban text-danger "></i>
                ?????????????? ???????????? ?????? ?? UDS
                <i class="fa-solid fa-ban text-danger "></i>
            </div>
        </div>
    </div>







@endsection

<style>

    .uds-gradient{
        background: rgb(145,0,253);
        background: linear-gradient(34deg, rgba(145,0,253,1) 0%, rgba(232,0,141,1) 100%);
    }
    .s-min{
        font-size: 10pt;
    }
    .s-min-8{
        font-size: 8px;
    }

    .myPM{
        padding-left: 4px !important;
        margin: 2px !important;
        margin-right: 11px !important;
    }

    .myButton {
        box-shadow: 0px 4px 5px 0px #5d5d5d !important;
        background-color: #00a6ff !important;
        color: white !important;
        border-radius:50px !important;
        display:inline-block !important;
        cursor:pointer !important;
        padding:5px 5px !important;
        text-decoration:none !important;
    }
    .myButton:hover {
        background-color: #fffdfd !important;
        color: #111111 !important;
    }
    .myButton:active {
        position: relative !important;
        top: 1px !important;
    }

    .myButton {
        box-shadow: 0px 4px 5px 0px #5d5d5d !important;
        background-color: #00a6ff !important;
        color: white !important;
        border-radius:50px !important;
        display:inline-block !important;
        cursor:pointer !important;
        padding:5px 5px !important;
        text-decoration:none !important;
    }
    .myButton:hover {
        background-color: #fffdfd !important;
        color: #111111 !important;
    }
    .myButton:active {
        position: relative !important;
        top: 1px !important;
    }
    .my-bg-gray{
        background-color: #ebefff !important;
        color: #3b3c65;
        border-radius: 14px !important;
        overflow: hidden !important;
    }

    .my-bg-success{
        border-radius: 14px !important;
        overflow: hidden !important;
    }

</style>
