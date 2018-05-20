# php-crud-api2

Single file PHP 7 script that adds a REST API to a MySQL 5.5 InnoDB database. PostgreSQL 9.1 and MS SQL Server 2012 are fully supported.

This is the working repo for v2 of [PHP-CRUD-API](https://github.com/mevdschee/php-crud-api).

## Requirements

  - PHP 7.0 or higher with PDO drivers for MySQL, PgSQL or SqlSrv enabled
  - MySQL 5.6 / MariaDB 10.0 or higher for spatial features in MySQL
  - PostGIS 2.0 or higher for spatial features in PostgreSQL 9.1 or higher
  - SQL Server 2012 or higher (2017 for Linux support)

## Installation

This is a single file application! Upload "api.php" somewhere and enjoy!

Contrary to v1 the code is in v2 actually structured in namespaces and files 
in the "src" directory. You may compile all files into a single "api.php"
using the "build.php" script.

## Limitations

These limitation were also present in v1:

  - Primary keys should either be auto-increment (from 1 to 2^53) or UUID
  - Composite primary or foreign keys are not supported
  - Complex writes (transactions) are not supported
  - Complex queries calling functions (like "concat" or "sum") are not supported
  - MySQL storage engine must be either InnoDB or XtraDB
  - Only MySQL, PostgreSQL and SQLServer support spatial/GIS functionality

## Features

These features match features in v1:

  - [x] Supports POST variables as input (x-www-form-urlencoded)
  - [x] Supports a JSON object as input
  - [x] Supports a JSON array as input (batch insert)
  - [ ] Supports file upload from web forms (multipart/form-data)
  - [ ] Optional condensed JSON: only first row contains field names
  - [ ] Sanitize and validate input using callbacks
  - [ ] Permission system for databases, tables, columns and records
  - [ ] Multi-tenant database layouts are supported
  - [x] Multi-domain CORS support for cross-domain requests
  - [x] Combined requests with support for multiple table names
  - [x] Search support on multiple criteria
  - [x] Pagination, seeking, sorting and column selection
  - [x] Relation detection nested results (belongsTo, hasMany and HABTM)
  - [x] Atomic increment support via PATCH (for counters)
  - [x] Binary fields supported with base64 encoding
  - [x] Spatial/GIS fields and filters supported with WKT
  - [ ] Unstructured data support through JSON/JSONB
  - [ ] Generate API documentation using OpenAPI tools
  - [ ] Authentication via JWT token or username/password

 NB: No checkmark means: not yet implemented.

### Extra Features

These features are new and were not included in v1.

  - [x] Does not reflect on every request (better performance)
  - [x] Complex filters (with both "and" & "or") are supported
  - [x] Support for input and output of database structure and records
  - [x] Support for all major database systems (thanks to jOOQ)
  - [x] Support for boolean and binary data in all database engines
  - [x] Support for relational data on read (not only on list operation)

## TreeQL, a pragmatic GraphQL

TreeQL allow you to create a Tree of JSON objects based on your SQL database structure (relations).

It is loosely based on the REST standard and also inspired by json:api.

### CRUD + List

The example posts table has only a a few fields:

    posts  
    =======
    id     
    title  
    content
    created

The CRUD + List operations below act on this table.

#### Create

If you want to create a record the request can be written in URL format as: 

    POST /data/posts

You have to send a body containing:

    {
        "title": "Black is the new red",
        "content": "This is the second post.",
        "created": "2018-03-06T21:34:01Z"
    }

And it will return the value of the primary key of the newly created record:

    2

#### Read

To read a record from this table the request can be written in URL format as:

    GET /data/posts/1

Where "1" is the value of the primary key of the record that you want to read. It will return:

    {
        "id": 1
        "title": "Hello world!",
        "content": "Welcome to the first post.",
        "created": "2018-03-05T20:12:56Z"
    }

On read operations you may apply includes.

#### Update

To update a record in this table the request can be written in URL format as:

    PUT /data/posts/1

Where "1" is the value of the primary key of the record that you want to update. Send as a body:

    {
        "title": "Adjusted title!"
    }

This adjusts the title of the post. And the return value is the number of rows that are set:

    1

#### Delete

If you want to delete a record from this table the request can be written in URL format as:

    DELETE /data/posts/1

And it will return the number of deleted rows:

    1

#### List

To list records from this table the request can be written in URL format as:

    GET /data/posts

It will return:

    {
        "records":[
            {
                "id": 1,
                "title": "Hello world!",
                "content": "Welcome to the first post.",
                "created": "2018-03-05T20:12:56Z"
            }
        ]
    }

On list operations you may apply filters and includes.

### Filters

Filters provide search functionality, on list calls, using the "filter" parameter. You need to specify the column
name, a comma, the match type, another commma and the value you want to filter on. These are supported match types:

    cs: contain string (string contains value)
    sw: start with (string starts with value)
    ew: end with (string end with value)
    eq: equal (string or number matches exactly)
    lt: lower than (number is lower than value)
    le: lower or equal (number is lower than or equal to value)
    ge: greater or equal (number is higher than or equal to value)
    gt: greater than (number is higher than value)
    bt: between (number is between two comma separated values)
    in: in (number or string is in comma separated list of values)
    is: is null (field contains "NULL" value)

You can negate all filters by prepending a 'n' character, so that 'eq' becomes 'neq'. 
Examples of filter usage are:

    GET /categories?filter=name,eq,Internet
    GET /categories?filter=name,sw,Inter
    GET /categories?filter=id,le,1
    GET /categories?filter=id,ngt,2
    GET /categories?filter=id,bt,1,1
    GET /categories?filter=categories.id,eq,1

Output:

    {
        "records":[
            {
                "id": 1
                "name": "Internet"
            }
        ]
    }

In the next section we dive deeper into how you can apply multiple filters on a single list call.

### Multiple filters

Filters can be a by applied by repeating the "filter" parameter in the URL. For example the following URL: 

    GET /categories?filter=id,gt,1&filter=id,lt,3

will request all categories "where id > 1 and id < 3". If you wanted "where id = 2 or id = 4" you should write:

    GET /categories?filter1=id,eq,2&filter2=id,eq,4
    
As you see we added a number to the "filter" parameter to indicate that "OR" instead of "AND" should be applied. Note that you can also repeat "filter1" and create an "AND" within an "OR". Since you can also go one level deeper by adding a letter (a-f) you can create almost any reasonably complex condition tree.

NB: You can only filter on the most top level table and filters are only applied on list calls.

### Includes

Let's say that you have a posts table that has comments (made by users) and the posts can have tags.

    posts    comments  users     post_tags  tags
    =======  ========  =======   =========  ======= 
    id       id        id        id         id
    title    post_id   username  post_id    name
    content  user_id   phone     tag_id
    created  message

When you want to list posts with their comments users and tags you can ask for two "tree" paths:

    posts -> comments  -> users
    posts -> post_tags -> tags

These paths have the same root and this request can be written in URL format as:

    GET /data/posts?include=comments,users&include=tags

Here you are allowed to leave out the intermediate table that binds posts to tags. In this example
you see all three table relation types (hasMany belongsTo and hasAndBelongsToMany) in effect:

- **post** has many **comments**
- **comment** belongs to **user**
- **post** has and belongs to many **tags**

This may lead to the following JSON data:

    {
        "records":[
            {
                "id": 1,
                "title": "Hello world!",
                "content": "Welcome to the first post.",
                "created": "2018-03-05T20:12:56Z",
                "comments": [
                    {
                        id: 1,
                        post_id: 1,
                        user_id: {
                            id: 1,
                            username: "mevdschee",
                            phone: null,
                        },
                        message: "Hi!"
                    },
                    {
                        id: 2,
                        post_id: 1,
                        user_id: {
                            id: 1,
                            username: "mevdschee",
                            phone: null,
                        },
                        message: "Hi again!"
                    }
                ],
                "tags": []
            },
            {
                "id": 2,
                "title": "Black is the new red",
                "content": "This is the second post.",
                "created": "2018-03-06T21:34:01Z",
                "comments": [],
                "tags": [
                    {
                        id: 1,
                        message: "Funny"
                    },
                    {
                        id: 2,
                        message: "Informational"
                    }
                ]
            }
        ]
    }

You see that the "belongsTo" relationships are detected and the foreign key value is replaced by the referenced object.
In case of "hasMany" and "hasAndBelongsToMany" the table name is used a new property on the object.

### Batch operations

When you want to create, read, update or delete you may specify multiple primary key values in the URL.
You also need to send an array instead of an object in the request body for create and update. 

To read a record from this table the request can be written in URL format as:

    GET /data/posts/1,2

The result may be:

    [
            {
                "id": 1,
                "title": "Hello world!",
                "content": "Welcome to the first post.",
                "created": "2018-03-05T20:12:56Z"
            },
            {
                "id": 2,
                "title": "Black is the new red",
                "content": "This is the second post.",
                "created": "2018-03-06T21:34:01Z"
            }
    ]

Similarly when you want to do a batch update the request in URL format is written as:

    PUT /data/posts/1,2

Where "1" and "2" are the values of the primary keys of the records that you want to update. The body should 
contain the same number of objects as there are primary keys in the URL:

    [   
        {
            "title": "Adjusted title for ID 1"
        },
        {
            "title": "Adjusted title for ID 2"
        }        
    ]

This adjusts the titles of the posts. And the return values are the number of rows that are set:

    1,1

Which means that there were two update operations and each of them had set one row. Batch operations use database
transactions, so they either all succeed or all fail (successful ones get roled back).

### Spatial support

For spatial support there is an extra set of filters that can be applied on geometry columns and that starting with an "s":

    sco: spatial contains (geometry contains another)
    scr: spatial crosses (geometry crosses another)
    sdi: spatial disjoint (geometry is disjoint from another)
    seq: spatial equal (geometry is equal to another)
    sin: spatial intersects (geometry intersects another)
    sov: spatial overlaps (geometry overlaps another)
    sto: spatial touches (geometry touches another)
    swi: spatial within (geometry is within another)
    sic: spatial is closed (geometry is closed and simple)
    sis: spatial is simple (geometry is simple)
    siv: spatial is valid (geometry is valid)

These filters are based on OGC standards and so is the WKT specification in which the geometry columns are represented.

## Cache

There are 4 cache engines that can be configured by the "cacheType" config parameter:

- TempFile (default)
- Redis
- Memcache
- Memcached

You can install the dependencies for the last three engines by running:

    sudo apt install php-redis redis
    sudo apt install php-memcache memcached
    sudo apt install php-memcached memcached

The default engine has no dependencies and will use temporary files in the system "temp" path.

You may use the "cachePath" config parameter to specify the file system path for the temporary files or
in case that you use a non-default "cacheType" the hostname (optionally with port) of the cache server.

## Types

These are the supported types with their default length/precision/scale:

character types
- varchar(255)
- clob

boolean types:
- boolean

integer types:
- integer
- bigint

floating point types:
- float
- double

decimal types:
- decimal(19,4)

date/time types:
- date
- time
- timestamp

binary types:
- varbinary(255)
- blob

other types:
- geometry /* non-jdbc type, extension with limited support */

## 64 bit integers in JavaScript

JavaScript does not support 64 bit integers. All numbers are stored as 64 bit floating point values. The mantissa of a 64 bit floating point number is only 53 bit and that is why all integer numbers bigger than 53 bit may cause problems in JavaScript.

## Tests

You may test the code using the "test.php" file, it will run the tests from the "tests" directory.