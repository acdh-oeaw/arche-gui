## create dump from live db
echo "Please stop the container first!!"

echo "go to db dir"
cd ../../../../sites/default/files

echo "create sql dump"
sqlite3 .ht.sqlite .dump > backupdb.sql

echo "copy sql dump to arche-gui inst folder"
cp backupdb.sql ../../../modules/contrib/arche-gui/inst/backupdb.sql
cd ../../../modules/contrib/arche-gui/inst/ 

echo "dump done"
