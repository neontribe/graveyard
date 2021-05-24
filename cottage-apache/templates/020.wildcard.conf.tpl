<VirtualHost *:443>
    UseCanonicalName Off

    ServerAdmin {{ admin_email }} 
    VirtualDocumentRoot {{ doc_root }}/%1

    RewriteEngine on
    RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
    RewriteRule .* - [F]

    IncludeOptional /etc/apache2/redirects.conf
    IncludeOptional /etc/apache2/expire_rules.conf

    <Directory {{ doc_root }}>
      AllowOverride All
      Options FollowSymLinks
      Require all granted
    </Directory>

    Header set Strict-Transport-Security "max-age=31536000" env=HTTPS
    Header always edit Set-Cookie ^(.*)$ $1;Secure
    Header always set X-XSS-Protection: "1; mode=block"
    Header always set X-Content-Type-Options nosniff

    ErrorLog ${APACHE_LOG_DIR}/vhost_error.log
    CustomLog ${APACHE_LOG_DIR}/vhost_ssl_access.log combined

    SSLEngine on
    SSLProtocol  ALL  -SSLv2 -SSLv3
    SSLHonorCipherOrder  On
    SSLCipherSuite  ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:ECDH+3DES:DH+3DES:RSA+AESGCM:RSA+AES:RSA+3DES:!aNULL:!MD5:!DSS
    #For Apache 2.2.24+ and 2.4.3+ uncomment below
    SSLCompression  Off

    SSLCertificateFile    /etc/apache2/ssl/ssl.crt
    SSLCertificateKeyFile /etc/apache2/ssl/ssl.key
    SSLCertificateChainFile /etc/apache2/ssl/chain.crt

    <FilesMatch "\.(cgi|shtml|phtml|php)$">
            SSLOptions +StdEnvVars
    </FilesMatch>
    <Directory /usr/lib/cgi-bin>
            SSLOptions +StdEnvVars
    </Directory>

    BrowserMatch "MSIE [2-6]" \
                    nokeepalive ssl-unclean-shutdown \
                    downgrade-1.0 force-response-1.0
    # MSIE 7 and newer should be able to use keepalive
    BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown

</VirtualHost>
# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
