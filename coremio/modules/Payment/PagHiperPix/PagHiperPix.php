<?php
    class PagHiperPix {
        public $checkout_id,$checkout;
        public $name,$commission=true;
        public $config=[],$lang=[],$page_type = "in-page",$callback_type="server-sided";
        public $payform=false;

        function __construct(){
            $this->config     = Modules::Config("Payment",__CLASS__);
            $this->lang       = Modules::Lang("Payment",__CLASS__);
            $this->name       = __CLASS__;
            $this->payform   = __DIR__.DS."pages".DS."payform";
        }

        public function get_auth_token(){
            $syskey = Config::get("crypt/system");
            $token  = md5(Crypt::encode("PagHiperPix-Auth-Token=".$syskey,$syskey));
            return $token;
        }

        public function set_checkout($checkout){
            $this->checkout_id = $checkout["id"];
            $this->checkout    = $checkout;
        }

        public function commission_fee_calculator($amount){
            $rate = $this->get_commission_rate();
            if(!$rate) return 0;
            $calculate = Money::get_discount_amount($amount,$rate);
            return $calculate;
        }


        public function get_commission_rate(){
            return $this->config["settings"]["commission_rate"];
        }

        public function cid_convert_code($id=0){
            Helper::Load("Money");
            $currency   = Money::Currency($id);
            if($currency) return $currency["code"];
            return false;
        }

        public function get_ip(){
            return UserManager::GetIP();
        }

        public function get_token(){
            return $this->config["settings"]["apitoken"];
        }

        public function get_fields(){

            $checkout_items         = $this->checkout["items"];
            $checkout_data          = $this->checkout["data"];
            $user_data              = $checkout_data["user_data"];

            $callback_url           = Controllers::$init->CRLink("payment",['PagHiperPix',$this->get_auth_token(),'callback']);

            $query = WDB::select("*");
            $query->from("users_informations");
            $query->where("owner_id", "=", $user_data["id"], "AND");
            $query->where("name", "=", $this->config["settings"]["cpfcnpjfield"]);
            $query = $query->build(true)->fetch_assoc();
            $cpfcnpjfield = $query[0]['content'];

            $fields                 = [ // novo do PagHiper
                'apiKey' => $this->config["settings"]["apikey"],
                'order_id' => $this->checkout_id, // código interno do lojista para identificar a transacao.
                'payer_email' => $user_data["email"],
                'payer_name' => $user_data["full_name"], // nome completo ou razao social
                'payer_cpf_cnpj' => $cpfcnpjfield, // cpf ou cnpj
                'payer_phone' => $user_data['gsm'], // fixou ou móvel
                //'notification_url' => $callback_url,
                'fixed_description' => true,
                'days_due_date' => $this->config["settings"]["pixduedate"], // dias para vencimento do Pix
                'items' => array(), // logo em seguida vamos loopar e atualizar
            ];

            foreach ($checkout_items as $key => $value) {
                array_push($fields['items'], array (
                    'description' => $checkout_items[$key]['name'],
                    'quantity' => $checkout_items[$key]['quantity'],
                    'item_id' => $checkout_items[$key]['id'],
                    'price_cents' => number_format($checkout_items[$key]['total_amount'], 2, '', '')));
            }

            return $fields;
        }

        public function payment_result(){
            $request    = file_get_contents('php://input');
            $respostaRAW = json_decode($request);

            $checkout_id        = (int) $respostaRAW['order_id'];
            $checkout           = Basket::get_checkout($checkout_id);

            $this->set_checkout($checkout);

            $invoice = Invoices::search_pmethod_msg('"transaction_id":"'.$respostaRAW['trans_id'].'"');

            if($invoice){
                $checkout["data"]["invoice_id"] = $invoice;
                $invoice = Invoices::get($invoice);
            }

            if($invoice && $invoice["status"] == "paid"){
                Basket::set_checkout($checkout["id"],['status' => "paid"]);
                return [
                    'status' => "SUCCESS",
                    'return_msg' => "OK",
                ];
            }

            if($status === "paid") {
                // foi pago
                $valorpago = $respostaRAW['status_request']["value_cents_paid"];
                Basket::set_checkout($checkout["id"],['status' => "paid"]);
                if($invoice){
                    Invoices::paid($checkout,"SUCCESS",$invoice["pmethod_msg"]);
                    return [
                        'status' => "SUCCESS",
                        'return_msg' => "OK",
                    ];
                } else {
                    return [
                        'status' => "SUCCESS",
                        'checkout'    => $checkout,
                        'status_msg' => Utility::jencode([
                            'transaction_id' => $respostaRAW['trans_id'],
                            'amount_paid' => $valorpago . " BRL",
                        ]),
                        'return_msg' => "OK",
                    ];
                }
            }

            if($status === "canceled") {
                // foi cancelado
                Basket::set_checkout($checkout["id"],['status' => "cancelled"]);
                return [
                    'checkout'       => $checkout,
                    'status'         => "ERROR",
                    'status_msg'     => "Payment Cancelled",
                    'return_msg'     => "Payment Cancelled",
                ];
            }

        }

    }
