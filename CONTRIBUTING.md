# Contributing to php-crud-api

Pull requests are welcome.

## Use phpfmt

Please use "phpfmt" to ensure consistent formatting.

## Run the tests

Before you do a PR, you should ensure any new functionality has test cases and that all existing tests are succeeding.

## Run the build 

Since this project is a single file application, you must ensure that classes are loaded in the correct order. 
This is only important for the "extends" and "implements" relations. The build script appends the classes in 
alphabetical order (directories first). The path of the class that is extended or implemented must be alphabetically
first. The build will fail with a "Class not found" error message if you get this wrong.
