#!/bin/bash
if [ ! -e target ]; then
    mkdir target
fi
if [ -e target/api.txt ]; then
    rm target/api.txt
fi
if [ -e target/api.php ]; then
    rm target/api.php
fi
echo "<?php" > target/api.txt
tee -a target/api.txt <<'EOF'
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/
EOF
echo 'namespace Com\Tqdev\CrudApi;' >> target/api.txt
find . -iname '*.php' -exec cat {} \; -exec echo \; | grep -v "^<?php\|^namespace \|^use \|spl_autoload_register\|^\s*//" >> target/api.txt
mv target/api.txt target/api.php