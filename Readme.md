# Hydrate

Hydrate is an object-relational mapping (ORM) library for PHP's framework Code Igniter. It provides methods for querying
tables with relations specified in a schema and then returning properly nested (hydrated) results instead of a plain 2D
table (which is how relational databases return results).

The way Hydrate achieves this is by selecting all the fields in the schema of the specified tables and their relation
tables that are being retrieved and then iterating over the result set to extract the unique rows (based on the primary
key columns) for each table and relation.

You can specify one-to-one, one-to-many or many-to-many (via a mapping table) relations.

Hydrate requires an instance of Code Igniter's database library to be passed to it and works on top of it.

The querying API is similar to the one of Code Igniter's database library, specifically the `where()`, `where_in()`,
`order_by()`, `limit()`, `resultArray()`, `rowArray()` methods.

## Examples

### Schema
The database schema is specified in JSON. This is the content of an example schema file:
```
{
    "children_table": {
        "columns": {
            "id": {},
            "parent_id": {},
            "date" : {
                "type": "datetime"
            },
            "a": {},
            "b": {}
        },
        "primary": "id",
        "relations": {
            "parent": {
                "table": "parents_table",
                "local": "parent_id"
            }
        }
    },
    "parents_table": {
        "columns": {
            "id": {},
            "c": {}
        },
        "primary": "id",
        "relations": {
            "children": {
                "type": "many",
                "table": "children_table",
                "foreign": "parent_id"
            }
        }
    },
    "many_to_many_table_a": {
        "columns": {
            "id": {}
        },
        "primary": "id",
        "relations": {
            "b": {
                "type" : "many",
                "table" : "many_to_many_table_b",
                "refTable" : "many_to_many_map",
                "local" : "a_id",
                "foreign" : "b_id"
            }
        }
    },
    "many_to_many_table_b": {
        "columns": {
            "id": {}
        },
        "primary": "id",
        "relations": {
            "a": {
                "type" : "many",
                "table" : "many_to_many_table_a",
                "refTable" : "many_to_many_map",
                "local" : "b_id",
                "foreign" : "a_id"
            }
        }
    },
    "many_to_many_map": {
        "columns": {
            "a_id": {},
            "b_id": {}
        },
        "primary": "id",
        "relations": {
            "a": {
                "table": "many_to_many_table_a",
                "local": "a_id"
            },
            "b": {
                "table": "many_to_many_table_b",
                "local": "b_id"
            }
        }
    }
}
```

### Queries

To run Hydrate queries you will need to create an instance of Hydrate. For that you need an instance of Code Igniter's
database library and the path to the schema file for your database:
```
$hydrate = new Hydrate([
    'db' => $code_igniter_database,
    'schemaPath' => 'path/to/schema',
]);
```

A simple query with no relations looks like this:
```
$result = $hydrate
    ->start("table_foo")
    ->where("a", 1)
    ->where("b >", 0)
    ->order_by("id")
    ->limit(10, 0)
    ->resultArray();
    
// The Hydrate call above runs a query like this:
//   SELECT * FROM table_foo
//   WHERE a = 1 AND b > 0
//   ORDER BY id
//   LIMIT 10 OFFSET 0
```

To return related data as well -  you specify required relations in the `start()` method:
```
$result = $hydrate
    ->start("children_table", ["parent"])
    ->resultArray();
    
// example $result:
// $result = [
//     [
//         "id" => 1,
//         "parent_id" => 2,
//         "parent" => [
//             "id" => 2,
//         ],
//     ],
// ]
```

You can use the columns of related tables in a `where()` clause like this:
```
$result = $hydrate
    ->start("children_table", ["parent"])
    ->where("parent.c", 15)
    ->resultArray();
    
// example $result:
// $result = [
//     [
//         "id" => 4,
//         "parent_id" => 6,
//         "parent" => [
//             "id" => 6,
//             "c" => 15,
//         ],
//     ],
// ]
```

And here's the most powerful part - you can retrieve nested relational data of arbitrary nesting depth, like this:
```
$result = $hydrate
    ->start("children_table", ["parent" => ["grand_parent" => "great_grand_parent"]])
    ->where("great_grand_parent.d", 100)
    ->resultArray();
    
// example $result:
// $result = [
//     [
//         "id" => 1,
//         "parent_id" => 2,
//         "parent" => [
//             "id" => 2,
//             "grand_parent_id" => 3,
//             "grand_parent" => [
//                 "id" => 3,
//                 "great_grand_parent_id" => 4,
//                 "great_grand_parent" => [
//                     "id" => 4,
//                     "d"  => 100,
//                 ],
//             ],
//         ],
//     ],
// ]
```

To return a `COUNT(*)` result you set the `start()` method's `$countQuery` parameter to `TRUE`. For this you should
also use `rawVal()` to return the scalar value instead of a table.

For example - you can fetch the number of rows in a table `child`, where the `c` column in a related `parent` table row
equals `10` like this:
```
$result = $hydrate
    ->start("child", ["parent"], TRUE)
    ->where("parent.c", 10)
    ->rawVal();
    
// example $result:
// $result = 5
```

Similarly you can use `rowArray()` to return a single row.
