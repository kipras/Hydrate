<?php

require_once dirname(__FILE__) . "/../libs/app.test.php";

/**
 * These are tests for the 2.x branch.
 */


class TestHydrate_2 extends AppTestCase
{
    // Test of complex hydrations
    function test1_1()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start("campaigns",
            Array("reklama" => Array("reklama_kur" => Array("object")))
        );
        $hq->where($hq->getFieldName($hq->hq->table, "id"), $this->campaign1["id"]);
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama"], "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama"]["children"]["reklama_kur"], "id"), "asc");
        
        // $expectedCampaign = $this->campaign1;
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
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected, TRUE));
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    // Tests the same, only using different Hydrate->where() syntax
    function test1_2()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start("campaigns",
            Array("reklama" => Array("reklama_kur" => Array("object")))
        );
        $hq->where("id", $this->campaign1["id"]);
        $hq->where("reklama.ID", $this->clip1["ID"]);
        $hq->where("reklama.reklama_kur.object.ID !=", $this->object2["ID"]);
        
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->campaign);
        unset($expected[0]["__name"]);
        $expected[0]["reklama"] = Array(
            $this->clip1
        );
        unset($expected[0]["reklama"][0]["__name"]);
        
        $expected[0]["reklama"][0]["reklama_kur"] = Array($this->clip1Object1Map);
        unset($expected[0]["reklama"][0]["reklama_kur"][0]["__name"]);
        $expected[0]["reklama"][0]["reklama_kur"][0]["object"] = $this->object1;
        unset($expected[0]["reklama"][0]["reklama_kur"][0]["object"]["__name"]);
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected, TRUE));
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    // Test fetching child -> parent -> full child list (including the original child)
    function testChildParentChildList()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start("reklama",
            Array("campaign" => Array("reklama"))
        );
        $hq->where("ID", $this->clip1["ID"]);
        $hq->order_by($hq->getFieldName($hq->hq->relations["campaign"]["children"]["reklama"], "ID"), "asc");
        
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->clip1);
        unset($expected[0]["__name"]);
        $expected[0]["campaign"] = $this->campaign1;
        unset($expected[0]["campaign"]["__name"]);
        
        $expected[0]["campaign"]["reklama"] = Array($this->clip1, $this->clip2);
        unset($expected[0]["campaign"]["reklama"][0]["__name"]);
        unset($expected[0]["campaign"]["reklama"][1]["__name"]);
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected, TRUE));
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    // Test Hydrate's automatic where_in()
    function testWhereIn()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "campaigns"
          , Array("reklama")
        );
        $hq->where("reklama.ID", Array($this->clip1["ID"], $this->clip2["ID"]));
        
        $result = db_decode($hq->resultArray());
        
        $this->_testWhereInResult($result);
    }
    
    // // Tests, when where() contains a IN () clause, specified as text
    // function testWhereInText1()
    // {
        // // $this->clearSandbox();
        
        // $hq = $this->CI->hydrate->start(
            // "campaigns"
          // , Array("reklama")
        // );
        // $hq->where("reklama.ID IN ({$this->clip1["ID"]}, {$this->clip2["ID"]})", NULL, FALSE);
        
        // $result = db_decode($hq->resultArray());
        
        // $this->_testWhereInResult($result);
    // }
    
    // function testWhereInText2()
    // {
        // // $this->clearSandbox();
        
        // $hq = $this->CI->hydrate->start(
            // "campaigns"
          // , Array("reklama")
        // );
        // $hq->where($hq->getFieldName($hq->hq->relations["reklama"], "ID") . " IN ({$this->clip1["ID"]}, {$this->clip2["ID"]})", NULL, FALSE);
        
        // $result = db_decode($hq->resultArray());
        
        // $this->_testWhereInResult($result);
    // }
    
    function _testWhereInResult($result)
    {
        $expected = Array($this->campaign);
        unset($expected[0]["__name"]);
        $expected[0]["reklama"] = Array(
            $this->clip1, $this->clip2,
        );
        unset($expected[0]["reklama"][0]["__name"]);
        unset($expected[0]["reklama"][1]["__name"]);
        
        // e($result);
        
        // e($expected);
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    // Test limit() clause
    function testLimit()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "reklama"
        );
        $hq
            ->where("campaign_id", $this->campaign1["id"])
            ->limit(1, 0)
            ->order_by($hq->getFieldName($hq->hq->table, "ID"), "ASC");
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->clip1);
        unset($expected[0]["__name"]);
        
        // e($result);
        
        // e($expected);
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
        
        // ----------------------------------------------------------
        
        $hq = $this->CI->hydrate->start(
            "reklama"
        );
        $hq
            ->where("campaign_id", $this->campaign1["id"])
            ->limit(2, 0)
            ->order_by($hq->getFieldName($hq->hq->table, "ID"), "ASC");
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->clip1, $this->clip2);
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        
        // e($result);
        
        // e($expected);
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    function testAddField1()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "reklama"
          , Array("reklama_kur")
        );
        $hq
            ->addField(FALSE, "reklama_kur.id", "reklama_kur_id")
            ->addField("reklama_kur", "reklama_kur.id", "reklama_kur_id")
            ->where("campaign_id", $this->campaign1["id"])
            ->limit(1, 0)
            ->order_by($hq->getFieldName($hq->hq->table, "ID"), "ASC")
            ->order_by($hq->getFieldName($hq->hq->relations["reklama_kur"], "ID"), "ASC")
        ;
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->clip1);
        unset($expected[0]["__name"]);
        $expected[0]["reklama_kur_id"] = $this->clip1Object1Map["id"];
        
        $expected[0]["reklama_kur"] = Array($this->clip1Object1Map, $this->clip1Object2Map);
        unset($expected[0]["reklama_kur"][0]["__name"]);
        unset($expected[0]["reklama_kur"][1]["__name"]);
        $expected[0]["reklama_kur"][0]["reklama_kur_id"] = $this->clip1Object1Map["id"];
        $expected[0]["reklama_kur"][1]["reklama_kur_id"] = $this->clip1Object2Map["id"];
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected, TRUE));
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    function testRawWhere()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "users"
          , Array("companies")
        );
        $hq
            ->where("(" . 
                $hq->getFieldName($hq->hq->table, "name") . " LIKE " . $this->CI->db->escape("%client%") . "
                OR " . $hq->getFieldName($hq->hq->table, "name") . " LIKE " . $this->CI->db->escape("%object%")
          . ")")
            ->order_by($hq->getFieldName($hq->hq->table, "id"), "ASC")
        ;
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->clientUser, $this->objectUser);
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        
        $expected[0]["companies"] = Array($this->company);
        unset($expected[0]["companies"][0]["__name"]);
        
        $expected[1]["companies"] = Array();
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected));
        
        $this->assertTrue(arrays_identical($result, $expected));
    }
    
    function testNull()
    {
        $hq = $this->CI->hydrate
            ->start("cities")
            ->where("region_id", NULL)
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("cities", "region_null"));
        unset($expected[0]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("users")
            ->where("id !=", NULL)
            ->order_by("id")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->clientUser, $this->objectUser, $this->adminUser);
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        unset($expected[2]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("users")
            ->where("id <>", NULL)
            ->order_by("id")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->clientUser, $this->objectUser, $this->adminUser);
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        unset($expected[2]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        // -----------------------------------------------------------------------
        
        $result = $this->CI->db
            ->from("cities")
            ->where("region_id IS NULL")
            ->get()
            ->result_array()
            ;
        $result = db_decode($result);
        
        // The default behavior here, should be the same as that of CI :
        // if a custom string passed in - do NOT escape it
        $hq = $this->CI->hydrate
            ->start("cities")
            ->where("region_id IS NULL")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("cities", "region_null"));
        unset($expected[0]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("users")
            ->where("id IS NOT NULL")
            ->order_by("id")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->clientUser, $this->objectUser, $this->adminUser);
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        unset($expected[2]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
    }
    
    // test for xx = '' (a possible where clause)
    function testWhereEqualsEmptyString()
    {
        $result = $this->CI->db
            ->where("name", "")
            ->order_by("id")
            ->get("cities")
            ->result_array()
            ;
        $result = db_decode($result);
        $expected = Array($this->env->getInsertedItem("cities", "noname"), $this->env->getInsertedItem("cities", "namespace"));
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("cities")
            ->where("name", "")
            ->order_by("id")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("cities", "noname"), $this->env->getInsertedItem("cities", "namespace"));
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("cities")
            ->where("name", " ")
            ->order_by("id")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("cities", "noname"), $this->env->getInsertedItem("cities", "namespace"));
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
    }
    
    // See whether Hydrate escapes values passed in where() properly.
    function testWhereEscaping()
    {
        $hq = $this->CI->hydrate
            ->start("cities")
            ->where("name", "test'test")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("cities", "namequote"));
        unset($expected[0]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("regions", Array("cities"))
            ->where("cities.name", "test'test")
            ;
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("regions", "test2"));
        unset($expected[0]["__name"]);
        $expected[0]["cities"] = Array($this->env->getInsertedItem("cities", "namequote"));
        unset($expected[0]["cities"][0]["__name"]);
        $this->assertTrue(arrays_identical($result, $expected));
        
        $hq = $this->CI->hydrate
            ->start("regions", Array("cities"))
            ->order_by("id")
            ;
        $hq->hq->relations["cities"]["query"] = Array(
            "cities.name" => "test'test"
        );
        $result = db_decode($hq->resultArray());
        $expected = Array($this->env->getInsertedItem("regions", "test1"), $this->env->getInsertedItem("regions", "test2"));
        unset($expected[0]["__name"]);
        unset($expected[1]["__name"]);
        $expected[0]["cities"] = Array();
        $expected[1]["cities"] = Array($this->env->getInsertedItem("cities", "namequote"));
        unset($expected[1]["cities"][0]["__name"]);
        // e(arrays_diff($result, $expected));
        $this->assertTrue(arrays_identical($result, $expected));
    }
    
    
}

$test = &new GroupTest('Test Hydrate 2.x');
$test->addTestCase(new TestHydrate_2());
$test->run(new HtmlReporter());
