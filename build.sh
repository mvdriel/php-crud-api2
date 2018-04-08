#!/bin/bash
START=$(date +%s.%N)
if [ ! -e target ]; then
    mkdir target
fi
if [ -e target/api.php ]; then
    rm target/api.php
fi
echo "<?php" > target/api.php
tee -a target/api.php >/dev/null <<'EOF'
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/
EOF
echo 'namespace Com\Tqdev\CrudApi;' >> target/api.php
FILECOUNT=`find . -path ./tests -prune -o -path ./target -prune -o -iname '*.php' | grep '.php$' | wc -l`
find . -path ./tests -prune -o -path ./target -prune -o -iname '*.php' -exec cat {} \; -exec echo \; | grep -v "^<?php\|^namespace \|^use \|spl_autoload_register\|^\s*//" >> target/api.php
php -l target/api.php
END=$(date +%s.%N)
DIFF=$(echo "( $END - $START ) * 1000 / 1" | bc)
echo "$FILECOUNT files combined in $DIFF ms"