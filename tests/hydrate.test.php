<?php

require_once dirname(__FILE__) . "/libs/app.test.php";

class TestHydrate extends AppTestCase
{
    // Test of complex hydrations
    function test1()
    {
        $hq = $this->CI->hydrate->start("campaigns",
            Array("reklama" => Array("reklama_kur" => Array("object")))
        );
        $hq->where($hq->getFieldName($hq->hq->table, "id"), $this->campaign["id"]);
        
        // $expectedCampaign = $this->campaign;
        // $expectedCampaign["reklama"] = Array(
            // $this->clip1, $this->clip2,
        // );
        
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->campaign);
        unset($expected[0]["__name"]);
        $expected[0]["reklama"] = Array(
            $this->clip1, $this->clip2,
        );
        unset($expected[0]["reklama"][0]["__name"]);
        unset($expected[0]["reklama"][1]["__name"]);
        
        $expected[0]["reklama"][0]["reklama_kur"] = Array($this->clip1Object1Map, $this->clip1Object2Map);
        unset($expected[0]["reklama"][0]["reklama_kur"][0]["__name"]);
        unset($expected[0]["reklama"][0]["reklama_kur"][1]["__name"]);
        $expected[0]["reklama"][0]["reklama_kur"][0]["object"] = $this->object1;
        unset($expected[0]["reklama"][0]["reklama_kur"][0]["object"]["__name"]);
        $expected[0]["reklama"][0]["reklama_kur"][1]["object"] = $this->object2;
        unset($expected[0]["reklama"][0]["reklama_kur"][1]["object"]["__name"]);
        
        $expected[0]["reklama"][1]["reklama_kur"] = Array($this->clip2Object1Map, $this->clip2Object2Map);
        unset($expected[0]["reklama"][1]["reklama_kur"][0]["__name"]);
        unset($expected[0]["reklama"][1]["reklama_kur"][1]["__name"]);
        $expected[0]["reklama"][1]["reklama_kur"][0]["object"] = $this->object1;
        unset($expected[0]["reklama"][1]["reklama_kur"][0]["object"]["__name"]);
        $expected[0]["reklama"][1]["reklama_kur"][1]["object"] = $this->object2;
        unset($expected[0]["reklama"][1]["reklama_kur"][1]["object"]["__name"]);
        
        e($result);
        
        // e($expected);
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    
}

$test = &new GroupTest('Test Hydrate');
$test->addTestCase(new TestHydrate());
$test->run(new HtmlReporter());
