<?php

require_once dirname(__FILE__) . "/libs/app.test.php";

class TestHydrate_1 extends AppTestCase
{
    // Test of complex hydrations
    function test1_1()
    {
        // $this->clearSandbox();
        
        $hq = $this->CI->hydrate->start("campaigns",
            Array("reklama" => Array("reklama_kur" => Array("object")))
        );
        $hq->where($hq->getFieldName($hq->hq->table, "id"), $this->campaign["id"]);
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama"], "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama"]["children"]["reklama_kur"], "id"), "asc");
        
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
        $hq->where("id", $this->campaign["id"]);
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
        $expected[0]["campaign"] = $this->campaign;
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
            ->where("campaign_id", $this->campaign["id"])
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
            ->where("campaign_id", $this->campaign["id"])
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
            ->where("campaign_id", $this->campaign["id"])
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
            "r_user" => "sksads",
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
        // $hq->where($hq->getFieldName($hq->hq->table, "id"), $this->campaign["id"]);
        $hq->order_by($hq->getFieldName($hq->hq->table, "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama_kur"], "id"), "asc");
        $hq->order_by($hq->getFieldName($hq->hq->relations["reklama_kur"]["children"]["object"], "ID"), "asc");
        
        // $expectedCampaign = $this->campaign;
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
    
    // Test performance of complex hydrations, with big result sets
    // In this test, we will manually supply Hydrate with a HUGE result set (3363 rows),
    // and check the performance of hydration alone
    function testPerformanceWithCustomResult()
    {

        // The raw query that we are testing against:
        //
        // SELECT a.id AS a_id, a.company_id AS a_company_id, a.created AS a_created, a.approved_datetime AS a_approved_datetime, a.name AS a_name, a.status AS a_status, a.daily_budget_limit AS a_daily_budget_limit, a.track_objects AS a_track_objects, a.has_daily_budget AS a_has_daily_budget, b.id AS b_id, b.user_id AS b_user_id, b.status AS b_status, b.name AS b_name, b.address AS b_address, b.company_code AS b_company_code, b.pvm_code AS b_pvm_code, b.phone AS b_phone, b.has_funds AS b_has_funds, c.id AS c_id, c.username AS c_username, c.name AS c_name, c.email AS c_email, c.emails_messages AS c_emails_messages, c.messages_low_funds AS c_messages_low_funds, c.messages_out_of_funds AS c_messages_out_of_funds, c.messages_accepted_campaigns AS c_messages_accepted_campaigns, c.messages_accepted_payments AS c_messages_accepted_payments, c.messages_new_features AS c_messages_new_features, c.messages_new_bills AS c_messages_new_bills, c.type AS c_type, d.id AS d_id, d.campaign_id AS d_campaign_id, d.date_from AS d_date_from, d.date_to AS d_date_to, d.views AS d_views, d.amount AS d_amount, e.ID AS e_ID, e.ID_uzsakovo AS e_ID_uzsakovo, e.campaign_id AS e_campaign_id, e.data AS e_data, e.rodyti AS e_rodyti, e.pradzios_data AS e_pradzios_data, e.pabaigos_data AS e_pabaigos_data, e.rodyti_sekundziu AS e_rodyti_sekundziu, e.pavadinimas AS e_pavadinimas, e.komentaras AS e_komentaras, e.c_action AS e_c_action, e.add_date AS e_add_date, e.r_date AS e_r_date, e.r_user AS e_r_user, e.tipas AS e_tipas, e.status AS e_status, e.type AS e_type, e.approved_datetime AS e_approved_datetime, f.id AS f_id, f.ID_reklamos AS f_ID_reklamos, f.ID_adresato AS f_ID_adresato, f.darbo_laikas_nuo AS f_darbo_laikas_nuo, f.darbo_laikas_iki AS f_darbo_laikas_iki, f.c_action AS f_c_action, f.add_date AS f_add_date, f.r_date AS f_r_date, f.r_user AS f_r_user, f.kartai AS f_kartai, f.kartai_per_diena AS f_kartai_per_diena, f.status AS f_status, g.ID AS g_ID, g.user_ID AS g_user_ID, g.imones_kodas AS g_imones_kodas, g.filialas AS g_filialas, g.pavadinimas AS g_pavadinimas, g.adresas AS g_adresas, g.tipas AS g_tipas, g.ID_grupes AS g_ID_grupes, g.user_name AS g_user_name, g.user_psw AS g_user_psw, g.add_date AS g_add_date, g.r_user AS g_r_user, g.r_date AS g_r_date, g.lokacija AS g_lokacija, g.city_id AS g_city_id, g.joomla_user_id AS g_joomla_user_id, g.payout AS g_payout, g.payout_peak AS g_payout_peak, h.ID AS h_ID, h.pavadinimas AS h_pavadinimas, h.add_date AS h_add_date, i.ID_adresato AS i_ID_adresato, i.ID_lokacijos AS i_ID_lokacijos, j.id AS j_id, j.region_id AS j_region_id, j.name AS j_name
        // FROM campaigns AS a (NOLOCK)
        // LEFT JOIN companies AS b (NOLOCK) ON b.id=a.company_id
        // LEFT JOIN users AS c (NOLOCK) ON c.id=b.user_id
        // LEFT JOIN statistics_calendar AS d (NOLOCK) ON d.campaign_id=a.id AND d.date_from IS NULL AND d.date_to IS NULL
        // LEFT JOIN reklama AS e (NOLOCK) ON e.campaign_id=a.id
        // LEFT JOIN reklama_kur AS f (NOLOCK) ON f.ID_reklamos=e.ID
        // LEFT JOIN adresatai AS g (NOLOCK) ON g.ID=f.ID_adresato
        // LEFT JOIN adresato_lokacijos AS i (NOLOCK) ON i.ID_adresato=g.ID
        // LEFT JOIN lokacijos AS h (NOLOCK) ON i.ID_lokacijos=h.ID
        // LEFT JOIN cities AS j (NOLOCK) ON j.id=g.city_id
        // WHERE a.id IN (1) AND a.status IN (1, 2, 3, 4, 5)
        // ORDER BY a.status asc, a.name asc
        
        
        // Form the query
        $hq = $this->CI->hydrate->start(
            "campaigns",
            Array(
                "company" => Array("user"),
                "statistics_calendar" => Array(),
                "reklama" => Array("reklama_kur" => Array("object" => Array("types", "city"))),
            )
        );
        $hq->where('id', Array(1));
        $hq->where('status', Array(1, 2, 3, 4, 5));
        $hq->hq->relations["statistics_calendar"]["query"]["statistics_calendar.date_from"] = NULL;
        $hq->hq->relations["statistics_calendar"]["query"]["statistics_calendar.date_to"] = NULL;
        $hq->order_by('status', 'asc');
        $hq->order_by('name', 'asc');
        
        $hq->hq->relations["statistics_calendar"]["query"]["statistics_calendar.date_from"] = NULL;
        $hq->hq->relations["statistics_calendar"]["query"]["statistics_calendar.date_to"] = NULL;
        
        $hq->setQuery();
        
        // Supply Hydrate with the result and do hydration. The supplied result set conforms to the
        // query we formed above
        require dirname(__FILE__) . '/data/hydrate.PerformanceWithCustomResult.php';
        
        $start = microtime(TRUE);
        $result = $hq->hydrateResultArray($result_array);
        $time = microtime(TRUE) - $start;
        
        // Cleanup CI AR stuff
        Hydrate::$db->_reset_select();
        
        // e($time);
        
        // print_r($result);
        
        $this->assertTrue($time < 1.5 ? TRUE : FALSE);
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
    
}

$test = &new GroupTest('Test Hydrate 1.x');
$test->addTestCase(new TestHydrate_1());
$test->run(new HtmlReporter());
