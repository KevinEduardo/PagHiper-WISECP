<?php
require_once('vendor/autoload.php');

use Rexlabs\HyperHttp\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\NullLogger;

$qrcode = null;
$copiacola = null;
$trans_id = null;
$callback_url   = Controllers::$init->CRLink("payment",['PagHiperPix',$module->get_auth_token(),'callback']);
$actionatual = "";
$timeout = 5000;

$hyper = new Client(new GuzzleClient(), new NullLogger(), [
    'base_uri' => 'https://pix.paghiper.com',
    'headers' => [
        'Accept' => 'application/json',
        'Accept-Charset' => 'UTF-8',
        'Accept-Encoding' => 'application/json',
        'Content-Type' => 'application/json;charset=UTF-8'
    ],
]);

// $links["successful-page"],$links["failed-page"]
$fields = $module->get_fields();

if(isset($_POST["trans_id"]) === true) {
    $trans_id = $_POST["trans_id"];
    // é uma atualização
    $consulta = $hyper->post('/invoice/status/', array(
        'token' => $module->get_token(),
        'apiKey' => $fields['apiKey'],
        'transaction_id' => htmlspecialchars($_POST["trans_id"]),
    ));
    if($consulta->getStatusCode() === 201 && $consulta->status_request->result === 'success') {
        $qrcode = $consulta->status_request->pix_code->qrcode_base64;
        $copiacola = $consulta->status_request->pix_code->emv;
        // achou o status
        if($consulta->status_request->status === "paid") {
            // foi pago
            $empacotado = $consulta->toArray();
            array_push($empacotado, ['trans_id' => $trans_id]);
            array_push($empacotado, $fields);
            // faz um request pro callback com os dados e redireciona o user pra tela de sucesso
            $apiinterna = Hyper::post($callback_url, $empacotado);
            if($apiinterna->getStatusCode() === 200) {
                header("Location: " . $links["successful-page"]);
            }
        }
        if($consulta->status_request->status === "canceled") {
            // foi cancelado
            $empacotado = $consulta->toArray();
            array_push($empacotado, ['trans_id' => $trans_id]);
            array_push($empacotado, $fields);
            // faz um request pro callback com os dados e redireciona o user pra tela de sucesso
            $apiinterna = Hyper::post($callback_url, $empacotado);
            if($apiinterna->getStatusCode() === 200) {
                header("Location: " . $links["failed-page"]);
            }
        }
    }

} else {
    $response = $hyper->post('/invoice/create/', $fields);

    if($response->getStatusCode() === 201 && $response->pix_create_request->result === 'success') {
        // sucesso
        $qrcode = $response->pix_create_request->pix_code->qrcode_base64;
        $copiacola = $response->pix_create_request->pix_code->emv;
        $trans_id = $response->pix_create_request->transaction_id;
    } else {
        // falha
    }
}
?>
<div align="center">
    <script type="text/javascript">
        setTimeout(function(){
            $("#PagHiperRefresh").submit();
        },<?= $timeout ?>);
    </script>
    <p style="font-weight: bold;"><?= $module->lang["make-the-pix"] ?></p>
    <div id="qrcodedisplay">
        <img src="data:image/jpeg;base64,<?= $qrcode ?>" alt="QRCode Pix">
    </div>
    <div id="pixcopiaecola">
        <p style="font-weight: bold;"><?= $module->lang["pix-copy-paste"] ?>:</p>
        <p><?= $copiacola ?></p>
    </div>
    <form action="<?= $actionatual ?>" method="post" id="PagHiperRefresh">
        <input type="hidden" name="trans_id" value="<?= $trans_id ?>"> 
    </form>
</div>