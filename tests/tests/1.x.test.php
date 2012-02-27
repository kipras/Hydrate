<?php

require_once dirname(__FILE__) . "/../libs/app.test.php";

/**
 * These are tests for the 1.x branch (excluding branch 1.2.x).
 */


class TestHydrate_1_x extends AppTestCase
{
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
}

$test = &new GroupTest('Test Hydrate 1.x (> 1.2)');
$test->addTestCase(new TestHydrate_1_x());
$test->run(new HtmlReporter());
