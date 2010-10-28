<?php

class Hydrate_error
{
    static function show($method, $message)
    {
        // Reset CI AR to a clear state, in case we messed it up and some page exit handlers want to use it
        $CI =& get_instance();
        $CI->db->_reset_select();
        
        $error =& load_class('Exceptions');
		echo $error->show_error("{$method}() error:", $message, 'error_db');
		exit;
    }
}

class Hydrate_schema
{
    static function get()
    {
        $currentSchema = file_get_contents(APPPATH . "schema/current.schema");
        if (empty($currentSchema))
            Hydrate_error::show( __METHOD__, "Could not load \"schema/current.schema\""); 
        
        $schemaJSON = file_get_contents(APPPATH . "schema/{$currentSchema}.schema");
        if (empty($schemaJSON))
            Hydrate_error::show(__METHOD__, "Could not load \"schema/{$currentSchema}.schema\""); 
        
        $schema = json_decode($schemaJSON, TRUE);
        if ($schema === NULL)
            Hydrate_error::show(__METHOD__, "Illegal schema file \"schema/{$currentSchema}.schema\" (JSON formatting error(s))"); 
        
        return self::prepare($schema);
    }
    
    // Runs autocompletes in schema (fills missing fields with default entries)
    static function prepare($schema)
    {
        
        foreach ($schema as $table_k => $table)
        {
            // First we need to have PKs set in all tables, before doing other changes
            
            // Default Primary Key
            if (!isset($table["primary"]))
                $table["primary"] = Array("id");
            
            // Primary Key fields are always in an array for convenience (even if almost always there will
            // be only one field in PK, we don't want to create a special case for that, so we always treat
            // PK as an array of fields).
            if (! is_array($table["primary"]))
                $table["primary"] = Array($table["primary"]);
            
            // Make sure Primary Key fields are in the fields list
            $columns = array_keys($table["columns"]);
            foreach ($table["primary"] as $PKField)
                if (! in_array($PKField, $columns))
                    $table["columns"] = array_merge(Array($PKField => Array()), $table["columns"]);
            
            // Create an empty relations array where necessary (so foreach does not throw notices later)
            if (!isset($table["relations"]))
                $table["relations"] = Array();
            
            
            // Save
            $schema[$table_k] = $table;
        }
        
        // Proceed with other changes
        foreach ($schema as $table_k => $table)
        {
            // Need to fetch table directly from array here, because we manipulate $schema array directly here
            // and this foreach will give us the original version of $table, so in later iterations
            // of this foreach, $table and $schema[$table_k] may actually be different.
            // And we need the version of table with all changes done sofar, so we take $schema[$table_k]
            $table = $schema[$table_k];
            
            // Relations
            if (empty($table["relations"]))
                $table["relations"] = Array();
            
            
            foreach ($table["relations"] as $rel_k => $rel)
            {
                // Default relation type is "one"
                if (empty($rel["type"]))
                    $rel["type"] = "one";
                
                // Default table name is the relation's name
                if (empty($rel["table"]))
                    $rel["table"] = $rel_k;
                
                // Default local field for non many-to-many relations is the primary field of the local table
                if (! Hydrate_schema::isManyToMany($rel) AND empty($rel["local"]))
                {
                    if (count($table["primary"]) > 1)
                        Hydrate_error::show( __METHOD__,
                                            "trying to setup a non many-to-many relation '{$table_k}.{$rel_k}', "
                                          . "without specifying the local key from a table {$table_k}, which does not have a "
                                          . "one-field Primary Key.");
                    else
                        $rel["local"] = $table["primary"][0];
                }
                    
                
                // Default foreign field for non many-to-many relations is the primary field of the foreign table
                if (! Hydrate_schema::isManyToMany($rel) AND empty($rel["foreign"]))
                {
                    if (count($schema[$rel["table"]]["primary"]) > 1)
                        Hydrate_error::show( __METHOD__,
                                            "trying to setup a non many-to-many relation '{$table_k}.{$rel_k}', "
                                          . "without specifying the foreign key to a table '{$rel_k}', which does not have a "
                                          . "one-field Primary Key.");
                    else
                        $rel["foreign"] = $schema[$rel["table"]]["primary"][0];
                }
                
                // Save
                $table["relations"][$rel_k] = $rel;
            }
            
            
            // In many-to-many relations, default fields in the refTable are named
            // <table_name>_<table_primary_key_field_name>
            foreach ($table["relations"] as $rel_k => $rel)
                if (Hydrate_schema::isManyToMany($rel))
                {
                    if (empty($rel["local"]))
                    {
                        if (count($table["primary"]) > 1)
                            Hydrate_error::show( __METHOD__,
                                                "trying to setup a many-to-many relation '{$table_k}.{$rel_k}', "
                                              . "without specifying the local key from a table '{$table_k}', which does not have a "
                                              . "one-field Primary Key.");
                        else
                            $rel["local"] = "{$table_k}_{$table["primary"][0]}";
                    }
                    
                    
                    if (empty($rel["foreign"]))
                    {
                        if (count($schema[$rel["table"]]["primary"]) > 1)
                            Hydrate_error::show( __METHOD__,
                                                "trying to setup a non many-to-many relation '{$table_k}.{$rel_k}', "
                                              . "without specifying the foreign key to a table '{$rel_k}', which does not have a "
                                              . "one-field Primary Key.");
                        else
                            $rel["foreign"] = "{$rel["table"]}_{$schema[$rel["table"]]["primary"][0]}";
                    }
                    
                    
                    // Save
                    $table["relations"][$rel_k] = $rel;
                }
            
            // // Fill missing ends of many-to-one and many-to-many relations:
            // Fill missing ends of many-to-one relations:
            // For many-to-one relations:
            //      - you should only specify the relation in the child table (the -to-one relation),
            //      and the relation on the other end (one-to-many) will be created automatically.
            // // For many-to-many relations:
            // //      - the relation on another end will be created using the information on the end,
            // //      where the relation was specified.
            // You only really need to specify the relation on both ends for one-to-one relations.
            foreach ($table["relations"] as $rel_k => $rel)
            {
                if ($rel["type"] == "one")
                {
                    $foreignTableName = $rel["table"];
                    $foreignTable = $schema[$foreignTableName];
                    
                    $found = FALSE;
                    foreach ($foreignTable["relations"] as $v)
                        if ($v["table"] == $table_k && $v["foreign"] == $rel["local"])
                        {
                            $found = TRUE;
                            break;
                        }
                    
                    if ($found == FALSE)
                    {
                        // Now, if there are two relations specified, that relate to the same table (messages, for example
                        // have a sender and a receiver, both of which point to the users table), only the first one
                        // will be created on the other end. The other one, will have to be created manually if
                        // you are going to use it.
                        if (isset($foreignTable["relations"][$table_k]))
                        {
                            // Hydrate_error::show(__METHOD__, "Relation {$rel_k} specified in table {$table_k}, but could "
                                // . "not create this relation on the other end, because there is another relation "
                                // . "specified with the name {$table_k} on table {$rel["table"]}"); 
                        }
                        else
                        {
                            $foreignTable["relations"][$table_k] = Array(
                                "type"  => "many",
                                "table" => $table_k,
                                "local" => $rel["foreign"],
                                "foreign" => $rel["local"],
                            );
                            
                            // Save
                            $schema[$foreignTableName] = $foreignTable;
                        }
                    }
                }
            }
            
            
            // Save
            $schema[$table_k] = $table;
        }
        
        // Now check if relation names do not conflict with field names in all tables
        foreach ($schema as $table_k => $table)
        {
            foreach ($table["columns"] as $columnName => $column)
                foreach ($table["relations"] as $relationName => $relation)
                    if ($columnName == $relationName)
                        Hydrate_error::show( __METHOD__,
                                            "table '{$table_k}' contains both a field and a relation, named '{$columnName}'");
        }
        
        return $schema;
    }
    
    // Checks if relation is many-to-many
    static function isManyToMany($rel)
    {
        return $rel["type"] == "many" && !empty($rel["refTable"]) ? TRUE : FALSE;
    }
}

class Hydrate_query
{
    // Variables available in an instance of Hydrate_query:
    
    // High level (Hydrate) stuff:
    var $count;         // Is this a COUNT() query?
    var $table;         // Table from which we want the top-level results be, in the format:
                        // Array(
                        //      name => table name
                        //      prefix => prefix used for the table
                        // )
    var $relations;     // Relations hierarchy we want to get
    
    var $prefixes;      // Prefixes used, while building the hydrate query
    
    // Low level (CI AR) stuff:
    // This gets formed by both Hydrate and user calls.
    var $select;        // Array of entries in CI AR select() clause
    
    // These get formed by Hydrate only : hydrate->formQuery()
    var $join;          // Array of CI AR join() clauses
    
    // These are added by user calls only : hydrate->where(), hydrate->where_in(), etc.
    var $where;         // Array of CI AR where() clauses
    var $where_in;      // Array of CI AR where_in() clauses
    var $order_by;      // CI AR order_by() clause
    var $limit;         // CI AR limit() clause
    
    var $returnNothing;
    
    
    
    function getRelations()
    {
        return $this->relations;
    }
    
    static function _getNextPrefix($prefixes)
    {
        $prefix = "a";
        
        while (in_array($prefix, $prefixes))
            $prefix++;
        
        return $prefix;
    }
    
    static function build($schema, $table, $relations)
    {
        $hq = new Hydrate_query();
        
        $hq->count = FALSE;
        
        $hq->prefixes       = Array();
        
        // Table to select from
        $prefix         = Hydrate_query::_getNextPrefix($hq->prefixes);
        $hq->prefixes[] = $prefix;
        $hq->table      = Array(
            "name"          => $table,
            "prefix"        => $prefix,
            "fields"        => Array(), // Additional fields may be specified, in the format:
                                        // Array (alias => fieldname).
                                        // You can use this to call functions, etc.
                                        // NOTE: for fieldname you can use hydrate->getFieldName()
        );
        
        // Relationships to include in the result set
        if (! is_array($relations))
            $relations = Array($relations);
        
        $hq->relations = Hydrate_query::buildRelations($schema, $table, $relations, $hq->prefixes);
        
        $hq->select = Array();
        $hq->join = Array();
        $hq->where = Array();
        $hq->where_in = Array();
        $hq->order_by = FALSE;
        $hq->limit = FALSE;
        $hq->returnNothing = FALSE;
        
        return $hq;
    }
    
    // Recursively builds relations
    static function buildRelations($schema, $table, $relTables, &$prefixes)
    {
        $relations = Array();
        
        foreach ($relTables as $k => $v)
        {
            // Relations can have children (relName => relChildren), or just be specified as plain text (relName)
            // In both situations we transform them to format relName => relChildren
            if (is_array($v))
            {
                $relName    = $k;
                $relChildren    = $v;
            }
            else
            {
                $relName    = $v;
                $relChildren    = Array();
            }
            
            if (!isset($schema[$table]["relations"][$relName]))
                Hydrate_error::show( __METHOD__, "Relation \"{$relName}\" not found in table \"{$table}\""); 
            $relTable           = $schema[$table]["relations"][$relName]["table"];
            $prefix             = Hydrate_query::_getNextPrefix($prefixes);
            $prefixes[]         = $prefix;
            $rel                = Array(
                "name"      => $relName,
                "prefix"    => $prefix,
                "fields"    => Array(), // Additional fields may be specified, in the format:
                                        // Array (alias => fieldname).
                                        // You can use this to call functions, etc.
                                        // NOTE: for fieldname you can use hydrate->getFieldName()
                                        
                "children"  => Hydrate_query::buildRelations($schema, $relTable, $relChildren, $prefixes),
                "query"     => Array(), // Query format is the same as in CI Active Record, the only
                                        // difference is that you MUST pass in an array
            );
            if (Hydrate_schema::isManyToMany($schema[$table]["relations"][$relName]))
            {
                $prefix                 = Hydrate_query::_getNextPrefix($prefixes);
                $prefixes[]             = $prefix;
                $rel["refTablePrefix"]  = $prefix;
            }
            
            $relations[$relName] = $rel;
        }
        
        return $relations;
    }
}

class Hydrate
{
    const DEBUG = FALSE;
    
    // CI AR database object
    var $db = FALSE;
    
    // Hydrate query that we are building
    var $hq = FALSE;
    
    var $selectFormed = FALSE;
    
    
    
    function _debug($str)
    {
        if (self::DEBUG)
            e($str);
    }
    
    
    static $_schema = FALSE;
    function getSchema()
    {
        if (self::$_schema === FALSE)
            self::$_schema = Hydrate_schema::get();
        
        return self::$_schema;
    }
    
    // Returns field name for additional WHERE query parts, that can be
    // entered from outside of Hydrate
    function getFieldName($table, $field)
    {
        return "{$table["prefix"]}.{$field}";
    }
    
    function _getHqTable($tableName)
    {
        $schema = $this->getSchema();
        $hq =& $this->hq;
        
        $relations = explode(".", $tableName);
        
        $queryTableName = $hq->table["name"];
        if (empty($tableName) OR count($relations) == 0)
            $queryTable =& $hq->table;
        else
        {
            $queryTable = Array(
                "children" => &$hq->relations,
            );
            foreach ($relations as $relName)
                if (!isset($queryTable["children"][$relName]))
                    Hydrate_error::show( __METHOD__, "current hydration query has no relation '{$relName}' "
                        . "from relation '" . (!empty($queryTable["name"]) ? $queryTable["name"] : "")
                        . "' (which is table '{$queryTableName}')");
                else
                {
                    // _buildRelations will throw an error incase invalid relations are specified, while building
                    // a hydrate query, so this is unnecessary (and never called)
                    // if (!isset($schema[$queryTableName]["relations"][$relName]))
                        // Hydrate_error::show( __METHOD__,
                          // "relation '{$queryTable["name"]}'(which is table '{$queryTableName}'), "
                        // . "has no relation '{$relname}' defined in the schema file. "
                        // . "The relation is referenced in where clause 'where({$originalKey}, {$originalValue})'");
                        
                    $queryTableName = $schema[$queryTableName]["relations"][$relName]["table"];
                    $queryTable =& $queryTable["children"][$relName];
                }
        }
        
        return Array(
            "tableName" => $queryTableName,
            "hqTable" => &$queryTable,
        );
    }
    
    // This is used to transform field names from the format "relation1.relation2.field_name"
    // into a field name, which ca be used in the query (from Hydrate->getFieldName())
    // NOTE: right now, this function preserves backwards-compatibility, and checks for the possibility
    // of the field name already being passed through Hydrate->getFieldName() and leaves it unmodified,
    // if this is the case.
    // NOTE: that, this backwards compatibility introduces a possible problem: if there would be a relation
    // in the database with a name which is used as a prefix in queries, then if you pass in a field name
    // in the format "relation.field" - then this relation's name would remain unmodified, which would cause
    // either a database error or wrong results.
    // NOTE: that, we can check for this possibility in the prefix generation function - always go through
    // all table names and all relation names, and if one of them is equal to the new prefix name - throw an error.
    // NOTE: that once this backwards compatibility is removed (2.0) - this will no longer be an issue.
    function _getFieldName($originalKey)
    {
        $schema = $this->getSchema();
        $hq = $this->hq;
        
        $relationsArr = explode(".", $originalKey);
        
        $fieldWithOperator = array_pop($relationsArr);
        $relations = $relationsArr;
        $fieldArr = explode(" ", trim($fieldWithOperator));
        $fieldName = array_shift($fieldArr);
        $afterField = FALSE;
        if (count($fieldArr) > 0)
            $afterField = join(" ", $fieldArr);
        
        // DEPRECATED: This is for backwards compatibility: try to see if the fieldname passed is already
        // passed through Hydrate->getFieldName()
        $passedThroughGetFieldName = FALSE;
        if (count($relations) == 1)
            foreach ($hq->prefixes as $prefix)
                if ($prefix == $relations[0])
                    return $originalKey;
        
        $hqTable = $this->_getHqTable(join(".", $relations));
        
        $hydratedFieldName = FALSE;
        if (isset($schema[$hqTable["tableName"]]["columns"][$fieldName]))
            $hydratedFieldName = $this->getFieldName($hqTable["hqTable"], $fieldName);
        else
        if (isset($hqTable["hqTable"]["fields"][$fieldName]))
            $hydratedFieldName = $hqTable["hqTable"]["fields"][$fieldName];
        else
            Hydrate_error::show( __METHOD__, "could not find field '{$fieldName}' in table '{$hqTable["tableName"]}', "
                . "which is referenced in where clause key '{$originalKey}'");
        
        if ($afterField === FALSE)
            return $hydratedFieldName;
        else
            return "{$hydratedFieldName} {$afterField}";
    }
    
    
    // Starts a new Hydrate query
    function start($table, $relations = Array(), $countQuery = FALSE)
    {
        $this->_debug(__METHOD__);
        
        $this->selectFormed = FALSE;
        
        $schema = $this->getSchema();
        
        // Building $this->hq
        $hq = Hydrate_query::build($schema, $table, $relations);
        if ($countQuery)
            $hq->count = TRUE;
        
        $this->hq = $hq;
        
        $CI =& get_instance();
        $this->db = $CI->db;
        
        // $this for chaining
        return $this;
    }
    
    function addField($intoTable, $field, $fieldAlias)
    {
        $hqTable =& $this->_getHqTable($intoTable);
        $fieldName = $this->_getFieldName($field, $fieldAlias);
        $hqTable["hqTable"]["fields"][$fieldAlias] = $fieldName;
        
        // $this for chaining
        return $this;
    }
    
    // Bypasses field existence checking - useful for adding fields that call functions for example.
    function addCustomField($intoTable, $field, $fieldAlias)
    {
        $hqTable =& $this->_getHqTable($intoTable);
        $hqTable["hqTable"]["fields"][$fieldAlias] = $field;
        
        // $this for chaining
        return $this;
    }
    
    // Forms (if not yet formed) the CI Active Record query from the current Hydrate query
    // and sets it in the CI DB layer.
    // This gets called right before results are fetched from CI AR and not sooner.
    //
    // NOTE: If you need to do manual fetching directly from CI AR (i.e. db->get()->results_array()),
    // then you need to call hydrate->setQuery() before that, to set the hydrate query in CI AR.
    function setQuery()
    {
        if ($this->selectFormed  == FALSE)
            $this->_formQuery();
        
        $this->_setQueryParts();
    }
    
    // Sets all the query parts, besides SELECT (because we need a custom SELECT
    // in other functions)
    function _setQueryParts()
    {
        $hq = $this->hq;
        
        $schema = $this->getSchema();
        
        // If we want to impose an offset/limit to the query, the standard SQL limit/offset will not
        // work, because we usually want to impose a limit for the top level of the returned results.
        // However, there will usually be more than one row returned by CI AR for a top level result (result and
        // it's relations go in separate rows). Therefore the standard limit would return less rows
        // than we want.
        // So we do it differently - we first get the distinct id's of the returned top level rows for this
        // limit/offset, and then only return those rows.
        if ($hq->limit)
        {
            $limit = $hq->limit[0];
            $offset = $hq->limit[1];
            
            if ($limit >= 0)
            {
                // First of, for MS SQL we need an order_by clause to be present, if we are going to use limit().
                // Since you HAVE to call limit() last, we can safely check here if such a clause is already
                // added to hq, and if not - we throw an error here. The error is purely for convenience, so
                // we would know beforehand about limit() usage with unspecified order_by().
                // NOTE: for MySQL we don't need this, so perhaps we could make a way to ignore this check
                // when using the MySQL driver.. ?
                if ($hq->order_by === FALSE)
                {
                    Hydrate_error::show( __METHOD__, "trying to set a limit() clause, without order_by(). "
                        . "In MS SQL limit() must go tegether with order_by().");
                }
                
                $table = $schema[$hq->table["name"]];
                
                if (count($table["primary"]) > 1)
                    Hydrate_error::show( __METHOD__,
                                        "trying to set a limit() clause on a table '{$hq->table["name"]}', which "
                                      . "does not have a one-field Primary Key.");
                
                $this->db->select("{$hq->table["prefix"]}.{$table["primary"][0]} AS {$hq->table["prefix"]}_{$table["primary"][0]}");
                
                // Hydrate query part
                $this->_setQueryPartsInner();
                
                $this->db
                    ->group_by(Array("{$hq->table["prefix"]}.{$table["primary"][0]}", $hq->order_by[0]))
                    ->limit($limit, $offset)
                ;
                
                $ids = $this->db
                    ->get()
                    ->result_array()
                ;
                
                if (! $ids)
                    $hq->returnNothing = TRUE;
                {
                    $primaryIn = Array();
                    foreach ($ids as $id)
                        $primaryIn[] = $id["{$hq->table["prefix"]}_{$table["primary"][0]}"];
                    $primaryInStr = join(",", $primaryIn);
                    $hq->where[] = Array("{$hq->table["prefix"]}.{$table["primary"][0]} IN ({$primaryInStr})");
                }
            }
        }
        
        if ($hq->returnNothing === FALSE)
        {
            $this->db->select(join(",", $hq->select));
            $this->_setQueryPartsInner();
        }
    }
    
    function _setQueryPartsInner()
    {
        $this->_debug(__METHOD__);
        
        $hq = $this->hq;
        
        $this->db
            ->from("{$hq->table["name"]} AS {$hq->table["prefix"]}");
        foreach ($hq->join as $join)
            call_user_func_array(Array($this->db, "join"), $join);
        // foreach ($hq->where as $where)
            // call_user_func_array(Array($this->db, "where"), Array($this->_getWhereKey($where[0]), $where[1]));
        // foreach ($hq->where_in as $where_in)
            // call_user_func_array(Array($this->db, "where_in"), Array($this->_getWhereKey($where_in[0]), $where_in[1]));
        
        foreach ($hq->where as $where)
            if (is_array($where) AND count($where) > 1)
                call_user_func_array(Array($this->db, "where"), Array($this->_getFieldName($where[0]), $where[1]));
            else
                call_user_func_array(Array($this->db, "where"), Array($this->_getFieldName($where[0])));
        foreach ($hq->where_in as $where_in)
            if (is_array($where_in) AND count($where_in) > 1)
                call_user_func_array(Array($this->db, "where_in"), Array($this->_getFieldName($where_in[0]), $where_in[1]));
            else
                call_user_func_array(Array($this->db, "where_in"), Array($this->_getFieldName($where_in[0])));
        if ($hq->order_by)
            call_user_func_array(Array($this->db, "order_by"), $hq->order_by);
    }
    
    // Forms the actual query from $this->hq parameters.
    function _formQuery()
    {
        $schema = $this->getSchema();
        
        $hq = $this->hq;
        
        // Building SELECT
        $hq->select = Array();
        
        $localTable     = $schema[$hq->table["name"]];
        $tablePrefix    = $hq->table["prefix"];
        // $select[]       = "{$tablePrefix}.id AS {$tablePrefix}_id";
        
        if ($hq->count)
        {
            if (count($localTable["primary"]) > 1)
                Hydrate_error::show( __METHOD__,
                                    "trying to do a count query on a table, which "
                                  . "does not have a one-field Primary Key.");
            $hq->select[] = "COUNT(DISTINCT {$tablePrefix}.{$localTable["primary"][0]})";
        }
        else
        {
            foreach ($localTable["columns"] as $name => $v)
            {
                if (isset($v["type"]) AND $v["type"] == "datetime")
                    $hq->select[] = "Convert(char(19),{$tablePrefix}.{$name},120) AS {$tablePrefix}_{$name}";
                else
                    $hq->select[] = "{$tablePrefix}.{$name} AS {$tablePrefix}_{$name}";
            }
            
            // Additional, user-specified custom fields
            foreach ($hq->table["fields"] as $alias => $field)
                $hq->select[] = "{$field} AS {$tablePrefix}_{$alias}";
        }
        
        // Adding relations
        $hq->join = Array();
        $hq = $this->_formAddRelations($hq, $hq->relations, $localTable, $tablePrefix);
        
        $this->hq = $hq;
        
        $this->selectFormed = TRUE;
        
        // $this for chaining
        return $this;
    }
    
    // Recursively adds relations to current query
    function _formAddRelations($hq, $relations, $localTable, $tablePrefix)
    {
        $schema = $this->getSchema();
        
        foreach ($relations as $rel)
        {
            $relationPrefix = $rel["prefix"];
            if (isset($rel["refTablePrefix"]))
                $refTablePrefix = $rel["refTablePrefix"];
            
            $relation       = $localTable["relations"][$rel["name"]];
            $tableName      = $relation["table"];
            $foreignTable   = $schema[$tableName];
            
            // $select[]       = "{$relationPrefix}.id AS {$relationPrefix}_id";
            if (! $hq->count)
            {
                foreach ($foreignTable["columns"] as $name => $v)
                    $hq->select[] = "{$relationPrefix}.{$name} AS {$relationPrefix}_{$name}";
                
                // Additional, user-specified fields
                foreach ($rel["fields"] as $alias => $field)
                    $hq->select[] = "{$field} AS {$relationPrefix}_{$alias}";
            }
            
            $query = "";
            
            foreach ($rel["query"] as $k => $v)
            {
                $fieldName = $this->_getFieldName($k);
                $fieldNameArr = explode(" ", $fieldName);
                $fieldNameParts = 0;
                foreach ($fieldNameArr as $v2)
                {
                    $v2 = trim($v2);
                    if (!empty($v2))
                        $fieldNameParts++;
                }
                
                // if (!preg_match ("/ !\=$/", $k) && !preg_match ("/ \<$/", $k) && !preg_match ("/ \<\=$/", $k)
                    // && !preg_match ("/ \>$/", $k) && !preg_match ("/ \>\=$/", $k) && !preg_match ("/ \=$/", $k))
                if ($fieldNameParts == 1)
                {
                    if (is_array($v))
                        $query .= " AND {$fieldName} IN (".join(",", $v).")";
                    else if ($v === NULL)
                        $query .= " AND {$fieldName} IS NULL";
                    else
                        $query .= " AND {$fieldName}=" . $this->db->escape($v);
                }
                else
                {
                    if (is_array($v))
                        Hydrate_error::show( __METHOD__,
                                            "trying to pass an array of values to an non-equal relation query clause {$k} ");
                    
                    $query .= " AND {$fieldName} {$v}";
                }
            }
            
            // If many-to-many relation
            if (Hydrate_schema::isManyToMany($relation))
            {
                if (! $hq->count)
                {
                    $hq->select[] = "{$refTablePrefix}.{$relation["local"]} AS {$refTablePrefix}_{$relation["local"]}";
                    $hq->select[] = "{$refTablePrefix}.{$relation["foreign"]} AS {$refTablePrefix}_{$relation["foreign"]}";
                }
                
                // Join refTable - many-to-many relations cannot connect to multi-field primary keys,
                // so we only take the first field 
                if (count($localTable["primary"]) > 1)
                    Hydrate_error::show( __METHOD__,
                                        "trying to do a many-to-many relation from a table, which "
                                      . "does not have a one-field Primary Key.");
                $hq->join[] = Array("{$relation["refTable"]} AS {$refTablePrefix}",
                    "{$refTablePrefix}.{$relation["local"]}={$tablePrefix}.{$localTable["primary"][0]}",
                    "left");
                
                // Join foreign table- many-to-many relations cannot connect to multi-field primary keys,
                // so we only take the first field 
                if (count($foreignTable["primary"]) > 1)
                    Hydrate_error::show( __METHOD__,
                                        "trying to do a many-to-many relation to a table, which "
                                      . "does not have a one-field Primary Key.");
                $hq->join[] = Array("{$tableName} AS {$relationPrefix}",
                    "{$refTablePrefix}.{$relation["foreign"]}={$relationPrefix}.{$foreignTable["primary"][0]} {$query}",
                    "left");
            }
            
            // If one-to-one or one-to-many relation:
            else
                $hq->join[] = Array("{$tableName} AS {$relationPrefix}",
                    "{$relationPrefix}.{$relation["foreign"]}={$tablePrefix}.{$relation["local"]} {$query}",
                    "left");
            
            
            // Do recursion here
            $hq = $this->_formAddRelations($hq, $rel["children"], $foreignTable, $rel["prefix"]);
        }
        
        return $hq;
    }
    
    function where()
    {
        $args = func_get_args();
        if (is_array($args[0]))
        {
            foreach ($args[0] as $k => $v)
                $this->where($k, $v);
        }
        else
        {
            if (count($args) > 1 AND is_array($args[1]))
            {
                $this->hq->where_in[] = $args;
                if (count($args[1]) == 0)
                    $this->hq->returnNothing = TRUE;
            }
            else
                $this->hq->where[] = $args; 
        }
        
        // $this for chaining
        return $this;
    }
    
    // function where_in()
    // {
        // $args = func_get_args();
        // $this->hq->where_in[] = $args;
    // }
    
    function order_by()
    {
        $args = func_get_args();
        $args[0] = $this->_getFieldName($args[0]);
        
        $this->hq->order_by = $args;
        
        // $this for chaining
        return $this;
    }
    
    function limit()
    {
        $args = func_get_args();
        
        $limit = $args[0];
        if ($limit >= 0)
            $this->hq->limit = $args;
        
        // $this for chaining
        return $this;
    }
    
    // Directly selects the first row from the result set, bypassing hydration.
    // This is useful to return the result of, for example COUNT() queries.
    function rawRowArray()
    {
        $this->setQuery();
        
        if ($this->hq->returnNothing === TRUE)
        {
            return Array();
        }
        
        return $this->db->get()->row_array();
    }
    
    function rawVal()
    {
        $row = $this->rawRowArray();
        return array_pop($row);
    }
    
    // We have to go through the entire result set, to get the first hydrated row, because
    // of the fact that we need to go through all of records that are related to the first one,
    // to perform hydration.
    function rowArray()
    {
        $hydratedArray = $this->resultArray();
        
        if (isset($hydratedArray[0]))
            return $hydratedArray[0];
        else
            return Array();
    }
    
    // Hydrates a result array set according to a given Hydrate query
    function resultArray()
    {
        $this->setQuery();
        
        if ($this->hq->returnNothing === TRUE)
        {
            return Array();
        }
        
        $schema = $this->getSchema();
        
        $hq             = $this->hq;
        $results_array  = $this->db->get()->result_array();
        
        $hydratedArray  = Array();
        
        $localTable     = $schema[$hq->table["name"]];
        $tablePrefix    = $hq->table["prefix"];
        
        foreach ($results_array as $row)
        {
            $hydratedRow        = Array();
            
            $hydrateRes         = $this->hydrateRow($row, $localTable, $tablePrefix, $hq->table, $hydratedArray);
            $hydratedArray      = $hydrateRes["array"];
            $hydratedRow        = $hydratedArray[$hydrateRes["rowIndex"]];
            
            // $hydratedRowIndex   = 
            // $hydratedRow        = $hydratedArray[$hydratedRowIndex];
            
            // Now recursively Hydrate relationships
            $hydratedRow = $this->hydrateRelations($hq->relations, $row, $hydratedRow, $localTable, $tablePrefix);
            
            $hydratedArray[$hydrateRes["rowIndex"]] = $hydratedRow;
        }
        
        return $hydratedArray;
    }
    
    function hydrateRow($rowData, $rowTable, $rowTablePrefix, $hqRelation, $array)
    {
        if ($rowTable["primary"] === FALSE)
            Hydrate_error::show( __METHOD__,
                                "trying to hydrate a row of a table, which "
                              . "does not have a one-field Primary Key.");
        // $PKFields = Array();
        // foreach ($rowTable["primary"] as $PKf)
            // $PKFields[] = "{$rowTablePrefix}_{$PKf}";
        // $PKField = "{$rowTablePrefix}_{$rowTable["primary"]}";
        // $rowId      = $rowData[$PKField];
        
        $foundExisting = FALSE;
        $rowIndex = 0;
        foreach ($array as $k => $v)
        {
            // Hydration is done, by checking for a match in all of the Primary Key fields
            $allPKFieldsMatch = TRUE;
            foreach ($rowTable["primary"] as $PKf)
                if ($v[$PKf] != $rowData["{$rowTablePrefix}_{$PKf}"])
                {
                    $allPKFieldsMatch = FALSE;
                    break;
                }
            
            if ($allPKFieldsMatch)
            {
                $foundExisting  = TRUE;
                $rowIndex       = $k;
                break;
            }
        }
        
        if ($foundExisting === FALSE)
        {
            foreach ($rowTable["columns"] as $colName => $v)
                $hydratedRow[$colName]  = $rowData["{$rowTablePrefix}_{$colName}"];
            
            // Additional, user-specified fields
            foreach ($hqRelation["fields"] as $alias => $field)
                $hydratedRow[$alias]  = $rowData["{$rowTablePrefix}_{$alias}"];
            
            $rowIndex   = count($array);
            $array[]    = $hydratedRow;
        }
        
        return Array(
            "array"     => $array,
            "rowIndex"  => $rowIndex,
        );
        
        // return $rowIndex;
    }
    
    function hydrateRelations($relations, $rowData, $hydratedRow, $localTable, $localTablePrefix)
    {
        $schema = $this->getSchema();
        
        foreach ($relations as $rel)
        {
            $hydratedRelationsRow = Array();
            
            $relationPrefix = $rel["prefix"];
            
            $relation = $localTable["relations"][$rel["name"]];
            
            $foreignTableName   = $relation["table"];
            $foreignTable       = $schema[$foreignTableName];
            
            // If this is a *-to-many relation, and there isn't yet a single row for it
            // in this Hydrated row - enter an empty array (there should always be
            // an array for *-to-many relations in Hydrated result sets)
            if ($relation["type"] == "many" && ! isset($hydratedRow[$rel["name"]]))
                $hydratedRow[$rel["name"]] = Array();
            
            // If this is a *-to-one relation and we have already entered a result for it -
            // no need to Hydrate
            if ($relation["type"] != "one" OR empty($hydratedRow[$rel["name"]]))
            {
                // Only Hydrate if there is a related table entry in the result set
                // (foreign primary key fields !== NULL)
                $doHydrate = FALSE;
                $PKFieldsNull = FALSE;
                
                foreach ($foreignTable["primary"] as $PKField)
                    if ($rowData["{$relationPrefix}_{$PKField}"] === NULL)
                    {
                        $PKFieldsNull = TRUE;
                        break;
                    }
                
                if ($PKFieldsNull == FALSE)
                {
                    // We must make sure, that we are assigning foreign rows to a correct local row.
                    // We do that by checking local row id in the $hydratedRow array.
                    if (Hydrate_schema::isManyToMany($relation))
                    {
                        if ($rowData["{$rel["refTablePrefix"]}_{$relation["local"]}"] == $hydratedRow[$localTable["primary"][0]])
                            $doHydrate = TRUE;
                    }
                    else
                    {
                        if ($rowData["{$rel["prefix"]}_{$relation["foreign"]}"] == $hydratedRow[$relation["local"]])
                            $doHydrate = TRUE;
                    }
                }
                
                if ($doHydrate)
                {
                    // This is for *-to-one relations, for *-to-many relations we already
                    // created an empty array above
                    if (!isset($hydratedRow[$rel["name"]]))
                        $hydratedRow[$rel["name"]] = Array();
                    
                    // Hydrate into $hydratedRow[$rel["name"]], using data from $row
                    $hydrateRes = $this->hydrateRow($rowData, $foreignTable, $relationPrefix, $rel, $hydratedRow[$rel["name"]]);
                    $hydratedRow[$rel["name"]] = $hydrateRes["array"];
                    if ($relation["type"] == "one")
                        $hydratedRow[$rel["name"]] = $hydratedRow[$rel["name"]][0];
                }
            }
            
            // Do recursion here
            if ($relation["type"] == "one")
            {
                // for *-to-one relations, there might be nothing related to this row, in that case - dont do the recursion
                if (isset($hydratedRow[$rel["name"]]))
                    $hydratedRow[$rel["name"]] = $this->hydrateRelations($rel["children"], $rowData, $hydratedRow[$rel["name"]], $foreignTable, $relationPrefix);
            }
            else
                foreach ($hydratedRow[$rel["name"]] as $k => $v)
                    $hydratedRow[$rel["name"]][$k] = $this->hydrateRelations($rel["children"], $rowData, $hydratedRow[$rel["name"]][$k], $foreignTable, $relationPrefix);
        }
        
        return $hydratedRow;
    }
    
}