
@extends('widgets.index')

@section('counterparty')

    <script>
        var GlobalobjectId;
        var GlobalURL;
        var GlobalxRefURL;
        var UDSClientID;
        window.addEventListener("message", function(event) {
            var receivedMessage = event.data;
            GlobalobjectId = receivedMessage.objectId;
            document.getElementById("success").style.display = "none";
            document.getElementById("danger").style.display = "none";



            if (receivedMessage.name === 'Open') {

                var oReq = new XMLHttpRequest();

                oReq.addEventListener("load", function() {
                    clr()

                    try {
                        var responseTextPars = JSON.parse(this.responseText);
                        document.getElementById("activated").style.display = "block";
                        document.getElementById("undefined").style.display = "none";
                    } catch (error){
                        document.getElementById("activated").style.display = "none";
                        document.getElementById("undefined").style.display = "block";
                    }

                    var participant = responseTextPars.participant;
                    var membershipTier = participant.membershipTier
                    UDSClientID = participant.id;
                    GlobalxRefURL = "https://admin.uds.app/admin/customers/"+participant.id+'/info';

                    window.document.getElementById("displayName").innerHTML = responseTextPars.displayName;
                    window.document.getElementById("lastTransactionTime").innerHTML = participant.lastTransactionTime.substr(0,10);
                    window.document.getElementById("points").innerHTML = participant.points;
                    window.document.getElementById("membershipTierName").innerHTML = membershipTier.name;
                    window.document.getElementById("membershipTierRate").innerHTML = membershipTier.rate;
                });
                GlobalURL = "{{$getObjectUrl}}" + receivedMessage.objectId;
                oReq.open("GET", GlobalURL);
                oReq.send();
            }
        });
        //???????????????? ?????????? ???????????????????? ????????????
        function update(){
            var xmlHttpRequest = new XMLHttpRequest();
            xmlHttpRequest.addEventListener("load", function() {

                try {
                    var responseTextPars = JSON.parse(this.responseText);
                    document.getElementById("activated").style.display = "block";
                    document.getElementById("undefined").style.display = "none";
                } catch (error){
                    document.getElementById("activated").style.display = "none";
                    document.getElementById("undefined").style.display = "block";
                }

                var participant = responseTextPars.participant;
                var membershipTier = participant.membershipTier
                UDSClientID = participant.id;
                GlobalxRefURL = "https://admin.uds.app/admin/customers/"+participant.id+'/info';

                window.document.getElementById("displayName").innerHTML = responseTextPars.displayName;
                window.document.getElementById("lastTransactionTime").innerHTML = participant.lastTransactionTime.substr(0,10);
                window.document.getElementById("points").innerHTML = participant.points;
                window.document.getElementById("membershipTierName").innerHTML = membershipTier.name;
                window.document.getElementById("membershipTierRate").innerHTML = membershipTier.rate;

            });

            xmlHttpRequest.open("GET", GlobalURL);
            xmlHttpRequest.send();
        }

        function xRefURL(){
            window.open(GlobalxRefURL);
        }

        function Accrue(){
          var input = document.getElementById("inputAccrue").value;
          var xmlHttpRequest = new XMLHttpRequest();
            xmlHttpRequest.addEventListener("load", function() {

                var statusCode = this.responseText;
                if (statusCode === "200") {
                    document.getElementById("success").style.display = "block";
                    document.getElementById("danger").style.display = "none";
                    document.getElementById("NotSuccess").style.display = "none";
                } else if (statusCode === "400") {
                    document.getElementById("success").style.display = "none";
                    document.getElementById("NotSuccess").style.display = "none";
                    document.getElementById("danger").style.display = "block";
                }

            });
            xmlHttpRequest.open("GET", "https://smartuds.kz/Accrue/{{$accountId}}/" + input + "/" + UDSClientID);
            xmlHttpRequest.send();
            update();
        }

        function Cancellation(){
            var input = document.getElementById("inputCancellation").value;
            var xmlHttpRequest = new XMLHttpRequest();
            xmlHttpRequest.addEventListener("load", function() {

                var statusCode = this.responseText;
                if (statusCode === "200") {
                    document.getElementById("NotSuccess").style.display = "block";
                    document.getElementById("success").style.display = "none";
                    document.getElementById("danger").style.display = "none";
                } else if (statusCode === "400") {
                    document.getElementById("NotSuccess").style.display = "none";
                    document.getElementById("success").style.display = "none";
                    document.getElementById("danger").style.display = "block";
                }

            });
            xmlHttpRequest.open("GET", "https://smartuds.kz/Cancellation/{{$accountId}}/" + input + "/" + UDSClientID);
            xmlHttpRequest.send();
            update();
        }

        function Bonus() {
            var select = document.getElementById('Bonus');
            var option = select.options[select.selectedIndex];
            if (option.value == 1) {
                document.getElementById("Accrue").style.display = "block";
                document.getElementById("Cancellation").style.display = "none";
            }else if (option.value == 2) {
                document.getElementById("Cancellation").style.display = "block";
                document.getElementById("Accrue").style.display = "none";
            }
            else {
                document.getElementById("Cancellation").style.display = "none";
                document.getElementById("Accrue").style.display = "none";
            }
        }


        function clr(){
            var select = document.getElementById('Bonus');
            select.value = 0;
            Bonus()

            document.getElementById("success").style.display = "none";
            document.getElementById("danger").style.display = "none";
        }

    </script>

    @php
    $View = true;
    @endphp




    <div id="activated" class="content bg-white text-Black rounded">
        <div class="row uds-gradient mx-2">
            <div class="mx-2 p-2 col-9 text-white">
                <img src="https://smartuds.kz/Config/UDS.png" width="30" height="30" >
                <label onclick="xRefURL()" style="cursor: pointer"> ???????????? </label>
            </div>
            <div class="mx-2 col-2 p-2">
                <button type="submit" onclick="update()" class="myButton btn "> <i class="fa-solid fa-arrow-rotate-right"></i> </button>
            </div>
            <div class="row mx-3 text-white">
                <div class="col-7">
                    <h6 id="displayName" class=""></h6>
                </div>
                <div class="col-5">
                    <div class="s-min-8 my-bg-gray p-1 px-2">
                        <span> ??????????????: </span>
                        <span id="membershipTierName"></span>
                        <span id="membershipTierRate"></span>
                        <span>%</span>
                    </div>
                </div>
            </div>
        </div>



        <div class="row mx-2 text-black mt-1">
            <div class="col-8">
                <div class="s-min ">?????????????????? ?????????????? </div>
            </div>
            <div class="col-4">
                <span id="lastTransactionTime" class="s-min"></span>
            </div>

            <div class="col-8 mt-2">
                <div class="s-min">???????????????? ??????????: </div>
            </div>
            <div class="col-4 bg-success my-bg-success text-white p-1">
                <span id="points" class="s-min mx-2"></span>
            </div>
        </div>


        <br>



        <div class="row">
            <div class="col-1"> </div>
            <div class="col-10">
                <select onclick="Bonus()" class="p-1 form-select" id="Bonus">
                    <option value="0" selected> ???????????????? ?? ?????????????? </option>
                    <option value="1"> ?????????????????? ?????????? </option>
                    <option value="2"> ?????????????? ?????????? </option>
                </select>
            </div>
            {{--????????????????????--}}
            <div id="Accrue" class="mx-2 mt-2 row" style="display: none; background-color: #f7f7f7">
                <div class="col-1"> </div>
                <div class="col-11">
                    <div class="row mt-2 mb-2 ">

                        @isset($admin)
                            <div class="bg-white text-Black rounded" >
                                <div class="text-center">
                                    <div class="p-3 mb-2 bg-danger text-white">
                                        <i class="fa-solid fa-user-shield"></i> ???? ???? ?????????????????? ??????????????????????????????
                                    </div>
                                </div>
                            </div>
                            @php $View = false; @endphp
                        @endisset

                        @if ($View == true)
                            <label class="s-min"> ???????????????????? ???????????? </label>
                            <div class="col-8">
                                <input type="text" name="Accrue" id="inputAccrue" class="form-control"
                                       required maxlength="10" >
                            </div>
                            <div class="col-4">
                                <button onclick="Accrue()" class="btn btn-success rounded-pill">??????????????????</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{--????????????????--}}
            <div id="Cancellation" class="mx-2 mt-2 row" style="display: none; background-color: #f7f7f7">
                <div class="col-1"> </div>
                <div class="col-11">
                    <div class="row mt-2 mb-2 ">

                        @isset($admin)
                            <div class="bg-white text-Black rounded" >
                                <div class="text-center">
                                    <div class="p-3 mb-2 bg-danger text-white">
                                        <i class="fa-solid fa-user-shield"></i> ???? ???? ?????????????????? ??????????????????????????????
                                    </div>
                                </div>
                            </div>
                            @php $View = false; @endphp
                        @endisset
                        @if ($View == true)
                            <label class="s-min"> ???????????????????? ???????????? </label>
                            <div class="col-8">
                                <input type="text" name="Cancellation" id="inputCancellation" class="form-control"
                                       required maxlength="10" >
                            </div>
                            <div class="col-4">
                                <button onclick="Cancellation()" class="btn btn-danger rounded-pill">??????????????</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>



        <div id="success" class="mt-2" style="display: none">
            <div class="row">
                <div class="col-1"></div>
                <div class="col-10">
                    <div class=" alert alert-success fade show in text-center "> ?????????????????????? ?????????? !</div>
                </div>
            </div>
        </div>
        <div id="NotSuccess" class="mt-2" style="display: none">
            <div class="row">
                <div class="col-1"></div>
                <div class="col-10">
                    <div class=" alert alert-success fade show in text-center "> ?????????? ?????????????? !</div>
                </div>
            </div>
        </div>
        <div id="danger" class="mt-2" style="display: none">
            <div class="row">
                <div class="col-1"></div>
                <div class="col-10">
                    <div class=" alert alert-danger alert-danger fade show in text-center "> ???????????? 400 </div>
                </div>
            </div>

        </div>



        <script>

        </script>
    </div>

    <div id="undefined" class="bg-white text-Black rounded" style="display: none">
        <div class="text-center">
            <div class="p-3 mb-2 bg-danger text-white">
                <i class="fa-solid fa-ban text-danger "></i>
                ?????????????? ?????????????????????? ?????? ?? UDS
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
