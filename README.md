# php-crud-api2

working repo for v2 of php-crud-api

- PHP 7 required

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

### Batch operations

When you want to create, read, update or delete you may specify multiple primary key values in the URL.
You also need to send an array instead of an object in the request body for create and update. 

(may need examples)  

### Filter

### Include

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

    [
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

You see that the "belongsTo" relationships are detected and the foreign key value is replaced by the referenced object.
In case of "hasMany" and "hasAndBelongsToMany" the table name is used a new property on the object.
