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
    var $campaign;
    var $campaignObjectMap;
    var $clip1;
    var $clip2;
    var $clip1Object1Map;
    var $clip1Object2Map;
    var $clip2Object1Map;
    var $clip2Object2Map;
    var $clientUser;
    
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
        
        $this->object1 = $this->env->getInsertedItem("adresatai", "object1");
        $this->object2 = $this->env->getInsertedItem("adresatai", "object2");
        
        $this->company = $this->env->getInsertedItem("companies", "client1_company1");
        $this->campaign = $this->env->getInsertedItem("campaigns", "client1_company1_campaign1");
        $this->campaignObjectMap = $this->env->getInsertedItem("campaigns_objects_map", "campaign1_object1");
        
        $this->clip1 = $this->env->getInsertedItem("reklama", "client1_company1_campaign1_clip1");
        $this->clip2 = $this->env->getInsertedItem("reklama", "client1_company1_campaign1_clip2");
        
        $this->clip1Object1Map = $this->env->getInsertedItem("reklama_kur", "clip1_object1");
        $this->clip1Object2Map = $this->env->getInsertedItem("reklama_kur", "clip1_object2");
        $this->clip2Object1Map = $this->env->getInsertedItem("reklama_kur", "clip2_object1");
        $this->clip2Object2Map = $this->env->getInsertedItem("reklama_kur", "clip2_object2");
        
        $this->clientUser = $this->env->getInsertedItem("users", "client1");
        
        $this->CI =& get_instance();
        
        $this->companyStatusList = $this->CI->campaigns->getCompanyStatusList();
        $this->campaignStatusList = $this->CI->campaigns->getStatusList();
        // $this->objectStatusList = $this->CI->campaigns->getObjectStatusList();
        $this->objectMapStatusList = $this->CI->campaigns->getObjectMapStatusList();
        $this->clipStatusList = $this->CI->campaigns->getClipStatusList();
    }
    
}

