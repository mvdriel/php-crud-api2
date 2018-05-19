#!/bin/bash
START=$(date +%s.%N)
if [ ! -e target ]; then
    mkdir target
fi
if [ -e target/api.php ]; then
    rm target/api.php
fi
tee target/api.php >/dev/null <<'EOF'
<?php
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/

namespace Com\Tqdev\CrudApi;
EOF
find . -path ./tests -prune -o -path ./target -prune -o -iname '*.php' | grep '\.php$' | sort -r | xargs cat | grep -v "^<?php\|^namespace \|^use \|spl_autoload_register\|^\s*//" | cat -s >> target/api.php
FILECOUNT=`find . -path ./tests -prune -o -path ./target -prune -o -iname '*.php' | grep '\.php$' | wc -l`
ERRORS=`php -l target/api.php`
if [ $? != 0 ]; then
    echo $ERRORS
    exit 1
fi;
END=$(date +%s.%N)
DIFF=$(python -c "print int(( $END - $START ) * 1000)")
echo "$FILECOUNT files combined in $DIFF ms into 'target/api.php'"