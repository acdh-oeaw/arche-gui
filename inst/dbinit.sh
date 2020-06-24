echo "please stop the container!"

echo "init db"
sqlite3 backupdb.sql -init .ht.sqlite

echo "copy the new db to the drupal db dir"
cp .ht.sqlite ../../../../sites/default/files/.ht.sqlite

echo "done" 
