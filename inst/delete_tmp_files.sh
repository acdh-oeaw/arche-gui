#!/bin/bash

echo "delete gui tmp files"

##delete the old collections
if [ -d "/home/www-data/gui/web/sites/default/files/collections/" ]; then
    if [ "$(ls -A /home/www-data/gui/web/sites/default/files/collections/)" ]; then
        echo "collection tmp files deleted"
        find /home/www-data/gui/web/sites/default/files/collections/* -mtime +3 -delete
    fi
fi

##delete the 3d files
if [ -d "/home/www-data/gui/web/sites/default/files/tmp_files/" ]; then
    if [ "$(ls -A /home/www-data/gui/web/sites/default/files/tmp_files/)" ]; then
        echo "tmp files deleted"
        find /home/www-data/gui/web/sites/default/files/tmp_files/* -mtime +3 -delete
    fi
fi

