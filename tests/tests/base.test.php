<?php

require_once dirname(__FILE__) . "/../libs/app.test.php";

/**
 * These are base tests for the entire 1.x branch (including branch 1.2.x).
 * Both 1.x and 1.2.x branches must pass all these tests.
 */


class TestHydrate_base extends AppTestCase
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
        
        $expected = Array($this->campaign1);
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
        
        $expected = Array($this->campaign1);
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
    
    // Tests, when where() contains a IN () clause, specified as text
    function testWhereInText1()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "campaigns"
          , Array("reklama")
        );
        $hq->where("reklama.ID IN ({$this->clip1["ID"]}, {$this->clip2["ID"]})");
        
        $result = db_decode($hq->resultArray());
        
        $this->_testWhereInResult($result);
    }
    
    function testWhereInText2()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start(
            "campaigns"
          , Array("reklama")
        );
        $hq->where($hq->getFieldName($hq->hq->relations["reklama"], "ID") . " IN ({$this->clip1["ID"]}, {$this->clip2["ID"]})");
        
        $result = db_decode($hq->resultArray());
        
        $this->_testWhereInResult($result);
    }
    
    function _testWhereInResult($result)
    {
        $expected = Array($this->campaign1);
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
    
    function testRawWhere1()
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
    
    // function testRawWhere2()
    // {
        // // $this->clearSandbox();
        
        // $hq = $this->CI->hydrate->start(
            // "users"
          // , Array("companies")
        // );
        // $hq
            // ->where("2 * (" . $hq->getFieldName($hq->hq->table, "id"). ") + 10 = 152")
            // ->order_by($hq->getFieldName($hq->hq->table, "id"), "ASC")
        // ;
        
        // $result = db_decode($hq->resultArray());
        
        // $expected = Array($this->objectUser);
        // unset($expected[0]["__name"]);
        
        // $expected[0]["companies"] = Array();
        
        // // e($result);
        
        // // e($expected);
        
        // // e(arrays_diff($result, $expected));
        
        // $this->assertTrue(arrays_identical($result, $expected));
    // }
    
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
    
    // ---------------------- ADDED IN 1.12 ----------------------
    
    // Test for custom field support regression, introduced in 1.10
    function testCustomField_1_10_regression___fixed_in_1_12()
    {
        $hq = $this->CI->hydrate->start(
            "users"
        );
        
        $hq->addCustomField("", "3*(" . $hq->getFieldName($hq->hq->table, "id"). " + 5)", "custom");
        $hq->where("custom =", 228);
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array($this->objectUser);
        unset($expected[0]["__name"]);
        $expected[0]['custom'] = '228';
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected));
        
        $this->assertTrue(arrays_identical($result, $expected));
    }
    
    // // Additional tests for custom field support:
    
    // function testCustomField2()
    // {
        // $hq = $this->CI->hydrate->start(
            // "users"
        // );
        
        // $hq->addCustomField("", "3 * (" . $hq->getFieldName($hq->hq->table, "id"). " + 5)", "custom");
        // $hq->where("custom", 228);
        
        // $result = db_decode($hq->resultArray());
        
        // $expected = Array($this->objectUser);
        // unset($expected[0]["__name"]);
        // $expected[0]['custom'] = '228';
        
        // // e($result);
        
        // // e($expected);
        
        // // e(arrays_diff($result, $expected));
        
        // $this->assertTrue(arrays_identical($result, $expected));
    // }
    
    // function testCustomField3()
    // {
        // $hq = $this->CI->hydrate->start(
            // "users"
        // );
        
        // $hq->addCustomField("", "3 * (" . $hq->getFieldName($hq->hq->table, "id"). " + 5)", "custom");
        // $hq->where("a.custom", 228);
        
        // $result = db_decode($hq->resultArray());
        
        // $expected = Array($this->objectUser);
        // unset($expected[0]["__name"]);
        // $expected[0]['custom'] = '228';
        
        // // e($result);
        
        // // e($expected);
        
        // // e(arrays_diff($result, $expected));
        
        // $this->assertTrue(arrays_identical($result, $expected));
    // }
    
    // function testCustomField4()
    // {
        // $hq = $this->CI->hydrate->start(
            // "users"
        // );
        
        // $hq->addCustomField("", "3 * (" . $hq->getFieldName($hq->hq->table, "id"). " + 5)", "custom");
        // $hq->where("custom = 228");
        
        // $result = db_decode($hq->resultArray());
        
        // $expected = Array($this->objectUser);
        // unset($expected[0]["__name"]);
        // $expected[0]['custom'] = '228';
        
        // // e($result);
        
        // // e($expected);
        
        // // e(arrays_diff($result, $expected));
        
        // $this->assertTrue(arrays_identical($result, $expected));
    // }
    
    // function testCustomField5()
    // {
        // $hq = $this->CI->hydrate->start(
            // "users"
        // );
        
        // $hq->addCustomField("", "3 * (" . $hq->getFieldName($hq->hq->table, "id"). " + 5)", "custom");
        // $hq->where("a.custom = 228");
        
        // $result = db_decode($hq->resultArray());
        
        // $expected = Array($this->objectUser);
        // unset($expected[0]["__name"]);
        // $expected[0]['custom'] = '228';
        
        // // e($result);
        
        // // e($expected);
        
        // // e(arrays_diff($result, $expected));
        
        // $this->assertTrue(arrays_identical($result, $expected));
    // }
    
    // ---------------------- ADDED IN 1.13 ----------------------
    
    function testPerformance()
    {
        // $this->clearSandbox();
        
        $created = $this->env->getTestDBDateTime();
        
        $maxItems = 10;
        
        // First - insert a crapload of elements to be hydrated by this hydration query
        $reklama = Array(
            "ID_uzsakovo" => "0",
            "campaign_id" => "[campaigns.client1_company1_campaign1]",
            "data" => $created,
            "rodyti" => NULL,
            "pradzios_data" => NULL,
            "pabaigos_data" => NULL,
            "rodyti_sekundziu" => NULL,
            "pavadinimas" => NULL,
            "komentaras" => NULL,
            "c_action" => "1",
            "add_date" => NULL,
            "r_date" => NULL,
            "r_user" => NULL,
            "tipas" => NULL,
            "status" => "1",
            "type" => "1",
            "approved_datetime" => NULL,
        );
        for ($i = 0; $i < $maxItems; $i++)
        {
            $reklama["__name"] = "temp_{$i}";
            $this->env->insertItem("reklama", $reklama);
        }
        
        $object = Array(
            "__name" => "object1",
            "user_ID" => NULL,
            "imones_kodas" => "imones kodas", 
            "filialas" =>  "filialas",
            "pavadinimas" => "test objekto 1 tasko 1 pavadinimas",
            "adresas" => NULL,
            "tipas" => NULL,
            "ID_grupes" => NULL,
            "user_name" => NULL,
            "user_psw" => NULL,
            "add_date" => $created,
            "r_user" => "user",
            "r_date" => NULL,
            "lokacija" => NULL,
            "city_id" => "[cities.test1]",
            // "status" => Campaigns::OBJECT_STATUS_DEFAULT,
            "joomla_user_id" => "[users.object1]",
            "payout" => "0",
            "payout_peak" => "0",
        );
        for ($i = 0; $i < $maxItems; $i++)
        {
            $object["__name"] = "temp_{$i}";
            $this->env->insertItem("adresatai", $object);
        }
        
        $reklama_kur = Array(
            "__name" => "clip1_object1",
            "ID_reklamos" => "[reklama.client1_company1_campaign1_clip1]",
            "ID_adresato" => "[adresatai.object1]",
            "darbo_laikas_nuo" => NULL,
            "darbo_laikas_iki" => NULL,
            "c_action" => "1",
            "add_date" => NULL,
            "r_date" => NULL,
            "r_user" => NULL,
            "kartai" => NULL,
            "kartai_per_diena" => NULL,
            "status" => "0",
        );
        
        for ($i = 0; $i < $maxItems; $i++)
        {
            for ($j = 0; $j < $maxItems; $j++)
            {
                $reklama_kur["__name"] = "temp_{$i}_{$j}";
                $reklama_kur["ID_reklamos"] = "[reklama.temp_{$i}]";
                $reklama_kur["ID_adresato"] = "[adresatai.temp_{$j}]";
                $this->env->insertItem("reklama_kur", $reklama_kur);
            }
        }
        
        $hq = $this->CI->hydrate->start("reklama",
            Array("reklama_kur" => Array("object"))
        );
        // $hq->where($hq->getFieldName($hq->hq->table, "id"), $this->campaign1["id"]);
        $hq->order_by($hq->getFieldName($hq->hq->table, "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama_kur"], "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama_kur"]["children"]["object"], "ID"), "asc");
        
        // $expectedCampaign = $this->campaign1;
        // $expectedCampaign["reklama"] = Array(
            // $this->clip1, $this->clip2,
        // );
        
        // xdebug_start_trace();
        $start = microtime(TRUE);
        $result = $hq->resultArray();
        $end = microtime(TRUE);
        // xdebug_stop_trace();
        
        $this->assertTrue($end - $start < 0.050); // Entire query must take less than 50 miliseconds
        
        // e($result);
        
        // Clear sandbox, because we made some changes to environment in this function
        $this->clearSandbox();
    }
    
    function testNoResults()
    {
        $hq = $this->CI->hydrate->start("campaigns",
            Array("reklama" => Array("reklama_kur" => Array("object")))
        );
        $hq->where('id', -1);
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array();
        
        // e($result);
        
        // e($expected);
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
    
    // ---------------------- ADDED IN 1.15 ----------------------
    
    /**
     * This tests for a bug (introduced in 1.14, when Hydrate went under major changes to improve performance),
     * which caused items in relationships (that use a mapping table) to only appear once in a result set.
     */
    function testMap_1_14_regression___fixed_in_1_15()
    {
        $hq = $this->CI->hydrate->start("campaigns", Array(
            "objects" => Array(),
        ));
        $hq->where('id', Array(
            $this->campaign1['id'],
            $this->campaign2['id'],
            $this->campaign3['id'],
        ));
        $hq->order_by("id", "asc");
        $hq->order_by("objects.ID", "asc");
        
        $result = db_decode($hq->resultArray());
        
        $expected = Array();
        
        $expected[] = $this->campaign1;
        unset($expected[0]["__name"]);
        $expected[0]["objects"] = Array(
            $this->object1, $this->object2,
        );
        unset($expected[0]["objects"][0]["__name"]);
        unset($expected[0]["objects"][1]["__name"]);
        
        $expected[] = $this->campaign2;
        unset($expected[1]["__name"]);
        $expected[1]["objects"] = Array(
            $this->object1,
        );
        unset($expected[1]["objects"][0]["__name"]);
        
        $expected[] = $this->campaign3;
        unset($expected[2]["__name"]);
        $expected[2]["objects"] = Array();
        
        // e($result);
        
        // e($expected);
        
        // e(arrays_diff($result, $expected, TRUE));
        
        $this->assertTrue(arrays_identical($result, $expected, TRUE));
    }
}

$test = &new GroupTest('Test Hydrate (base tests, for all versions)');
$test->addTestCase(new TestHydrate_base());
$test->run(new HtmlReporter());
