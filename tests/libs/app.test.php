<?php

// Fills in standard properties

class AppTestCase extends UnitTestCase
{
    var $CI;
    
    var $env;
    var $helpers;
    
    var $object1;
    var $object2;
    var $company;
    var $campaign1;
    var $campaign2;
    var $campaign3;
    var $campaignObjectMap;
    var $clip1;
    var $clip2;
    var $clip1Object1Map;
    var $clip1Object2Map;
    var $clip2Object1Map;
    var $clip2Object2Map;
    
    var $clientUser;
    var $objectUser;
    var $adminUser;
    
    var $companyStatusList;
    var $campaignStatusList;
    // var $objectStatusList;
    var $objectMapStatusList;
    var $clipStatusList;
    
    
    function __construct()
    {
        parent::__construct();
        
        $this->helpers = new TestHelpers();
        
        $this->env = TestEnvironment::getInstance();
        
        $this->clearSandbox();
    }
    
    function clearSandbox()
    {
        $this->env->sandboxStart();
        
        $this->object1 = $this->env->getInsertedItem("adresatai", "object1");
        $this->object2 = $this->env->getInsertedItem("adresatai", "object2");
        
        $this->company = $this->env->getInsertedItem("companies", "client1_company1");
        $this->campaign1 = $this->env->getInsertedItem("campaigns", "client1_company1_campaign1");
        $this->campaign2 = $this->env->getInsertedItem("campaigns", "client1_company1_campaign2");
        $this->campaign3 = $this->env->getInsertedItem("campaigns", "client1_company1_campaign3");
        $this->campaignObjectMap = $this->env->getInsertedItem("campaigns_objects_map", "campaign1_object1");
        
        $this->clip1 = $this->env->getInsertedItem("reklama", "client1_company1_campaign1_clip1");
        $this->clip2 = $this->env->getInsertedItem("reklama", "client1_company1_campaign1_clip2");
        
        $this->clip1Object1Map = $this->env->getInsertedItem("reklama_kur", "clip1_object1");
        $this->clip1Object2Map = $this->env->getInsertedItem("reklama_kur", "clip1_object2");
        $this->clip2Object1Map = $this->env->getInsertedItem("reklama_kur", "clip2_object1");
        $this->clip2Object2Map = $this->env->getInsertedItem("reklama_kur", "clip2_object2");
        
        $this->clientUser = $this->env->getInsertedItem("users", "client1");
        $this->objectUser = $this->env->getInsertedItem("users", "object1");
        $this->adminUser = $this->env->getInsertedItem("users", "admin1"); 
        
        $this->CI =& get_instance();
    }
    
}

