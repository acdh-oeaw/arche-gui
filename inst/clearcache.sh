cd /var/www/html && drush cr > /dev/null 2>&1
##delete the old collections
if [ -d "/var/www/html/sites/default/files/collections/" ]; then
	find /var/www/html/sites/default/files/collections/ -mtime +5 -delete
fi


