<?php
/* 
 * Stelo Payment module
 * Developed By Rodrigo Ribeiro  
 * https://br.linkedin.com/in/rodrigoferreirasantosribeiro
 * 
 * Funded by Stelo
 * http://www.stelo.com.br/
 * 
 * License: OSL 3.0
 */

class Stelo_Wallet_Model_Api extends Varien_Object {

    public function SendTemplate($url, $header, $body, $method) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($method == "CURLOPT_CUSTOMREQUEST"){
           curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }else{
        curl_setopt($ch, $method, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($method == "CURLOPT_POST")
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        curl_close($ch);  

        return $result;
    }

    public function createNewSteloOrder($order, $steloId, $status, $urlWallet) {
        $newSteloId = Mage::getModel('wallet/walletcustom');
        $newSteloId->setMageId($order);
        $newSteloId->setSteloId($steloId);
        $newSteloId->setStatus($status);
        $newSteloId->setSteloUrl($urlWallet);
        $newSteloId->save();
    }

    public function cancelOrder($orderId) {

        
        
        $collection = Mage::getModel('wallet/walletcustom')->getCollection();
        $collection->addFieldToSelect('id');
        $collection->addFieldToSelect('mage_id');
        $collection->addFieldToSelect('stelo_id');
        $collection->addFieldToSelect('status');
        $collection->addFieldToFilter('mage_id', array('like' => $orderId));

        $clientId = Mage::getStoreConfig('payment/wallet/clientId');
        $clientSecret = Mage::getStoreConfig('payment/wallet/clientSecret');
        $auth = base64_encode($clientId . ":" . $clientSecret);

        $header = array(
            "Authorization: " . $auth,
            "Content-Type: application/json"
        );

        foreach ($collection as $item) {
            $idTableStelo = $item->getData("id");
            $steloId = $item->getData('stelo_id');
            $mageId = $item->getData('mage_id');
            
             

             
            $url =  Mage::getStoreConfig('payment/wallet/apiEnd');
            $url .= "ec/";
            $url .= Mage::getStoreConfig('payment/wallet/apiVer');
            $url .= "/orders/transactions/" . $steloId;
            

            $returnRequest = $this->SendTemplate($url, $header, $body, "CURLOPT_CUSTOMREQUEST");
            //Mage::log($returnRequest, null, "cancel.log", true);
            $returnRequest = json_decode($returnRequest);
                
        }
        
        return $returnRequest;
    }

    public function checkStatus($steloId) {
        
        if($steloId == ""){
            $collection = Mage::getModel('wallet/walletcustom')->getCollection();
            $collection->addFieldToSelect('id');
            $collection->addFieldToSelect('mage_id');
            $collection->addFieldToSelect('stelo_id');
            $collection->addFieldToSelect('status');
            $collection->addFieldToFilter('status', array('nlike' => 'processing'));
            $collection->addFieldToFilter('status', array('nlike' => 'canceled'));
        }else{
            $collection = Mage::getModel('wallet/walletcustom')->getCollection();
            $collection->addFieldToSelect('id');
            $collection->addFieldToSelect('mage_id');
            $collection->addFieldToSelect('stelo_id');
            $collection->addFieldToSelect('status');
            $collection->addFieldToFilter('stelo_id', array('like' => $steloId));
            
            if(empty($collection)){
                echo '{"message" :"id não encontrado" }';
                return;
            }
                
        }
        
        $clientId = Mage::getStoreConfig('payment/wallet/clientId');
        $clientSecret = Mage::getStoreConfig('payment/wallet/clientSecret');
        $auth = base64_encode($clientId . ":" . $clientSecret);


        $header = array(
            "Authorization: " . $auth,
            "Content-Type: application/json"
        );

        $body = "";
        foreach ($collection as $item) {
            $idTableStelo = $item->getData("id");
            $steloId = $item->getData('stelo_id');
            $mageId = $item->getData('mage_id');
            $stateAct = $item->getData('status');

            $url =  Mage::getStoreConfig('payment/wallet/apiEnd');
            $url .= "ec/";
            $url .= Mage::getStoreConfig('payment/wallet/apiVer');
            $url .= "/orders/transactions/" . $steloId;
           

            
            $returnRequest = $this->SendTemplate($url, $header, $body, "CURLOPT_GET");
           

            $returnRequest = json_decode($returnRequest);
            if(property_exists($returnRequest, "steloStatus")){
                $statusCode = $returnRequest->steloStatus->statusCode;
            }else{
                $statusCode = "N";
            }
            


            $this->changeStatus($mageId, $statusCode, $idTableStelo, $stateAct);
        }
    }

    public function changeStatus($mageId, $steloStatus, $idTableStelo, $stateAct) {

        switch ($steloStatus) {
            case "I":
                $status = "pending";
                break;
            case "E":
                $status = "pending_payment";
                break;
            case "A":
                $status = "processing";
                break;
            case "C":
                $status = "canceled";
                break;
            case "N":
                $status = "canceled";
                break;
            case "NE":
                $status = "canceled";
                break;
            case "S":
                $status = "payment_review";
                break;
            case "SP":
                $status = "payment_review";
                break;
            default:
                $status = "canceled";
        }


        $order = Mage::getModel('sales/order')->loadByIncrementId($mageId);
            
       if($status != $stateAct){
  
        $order->setState($status, true);
        $order->setStatus($status, true)->save();

        $steloOrder = Mage::getModel('wallet/walletcustom');
        $steloOrder = $steloOrder->load($idTableStelo);
        $steloOrder->setData("status", $status)->save();
       }
    }
    
     public function getWalletUrl(){

        $urlPortal = Mage::getSingleton('core/session')->getWalletUrl();
       
        
        return $urlPortal;
    }


}
