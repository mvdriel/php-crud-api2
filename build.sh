#!/bin/bash
if [ ! -e target ]; then
    mkdir target
fi
if [ -e target/api.php ]; then
    rm target/api.php
fi
echo "<?php" > target/api.php
echo 'namespace Com\Tqdev\CrudApi;' >> target/api.php
find . -iname '*.php' -exec cat {} \; -exec echo \; | grep -v "^<?php\|^namespace \|^use \|spl_autoload_register" >> target/api.php
