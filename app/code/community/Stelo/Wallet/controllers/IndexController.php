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
class Stelo_Wallet_IndexController extends Mage_Core_Controller_Front_Action{

    public function indexAction(){
       
    }

    //Aguarda uma chamada da Stelo para alterar o status de um pedido
    public function listenerAction(){
//baseurl /wallet/index/listener

        
        $var = $GLOBALS['HTTP_RAW_POST_DATA'];
        if(!empty($var)){
        $var = json_decode($var);
        
        $steloId = $var->steloId;
        
        Mage::getModel('wallet/api')->checkStatus($steloId);
    
        }
        
    }


}