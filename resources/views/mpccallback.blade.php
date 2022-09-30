<meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
</style>
    <div class="py-12" id="result" style="display:none;">
    <input class='form-check-input' type='text' name='sa' id='response' value='{{$response}}'>
    </div>
    <?php 
    $response2 = json_encode($response);
    // dd($response2);
    ?>
    <script>
</script>
<script>
        var response = $('#response').val();
        // var response =  '{!! $response2 !!}';
        // var cons = JSON.stringify(response);
        // var cons = JSON.parse(response);
        // console.log(cons);
        console.log(response);
        // console.log(JSON.parse(cons));
          setTimeout(function () {window.ReactNativeWebView.postMessage(response)}, 100);
</script>