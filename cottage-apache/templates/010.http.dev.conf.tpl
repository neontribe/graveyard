<VirtualHost 127.0.0.1:80>
     ServerAdmin {{ admin_email }}

     #no document should ever be served from here
     DocumentRoot {{ doc_root }}
</VirtualHost>

<VirtualHost *:80>
  ServerAdmin {{ admin_email }}
  DocumentRoot {{ doc_root }}
        
  {% set servernames = servername.split() %}
  {% for servername in servernames %}
  {% if loop.first %}
      ServerName {{ servername }}
  {% else %}
      ServerAlias {{ servername }}
  {% endif %}
  {% endfor %}
	
  RewriteEngine on
  RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
  RewriteRule .* - [F]

  <Directory {{ doc_root }}>
    AllowOverride All
    Options FollowSymLinks
    Require all granted
  </Directory>


  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
