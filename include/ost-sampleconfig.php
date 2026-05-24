<?php
#TÍTULO: Archivo de configuración base para osTicket
#ADVERTENCIA: No edites este archivo manualmente a menos que sepas qué haces.
#El instalador web se encargará de clonar este archivo como ost-config.php.

define('OSTINSTSRV',TRUE);
if (!defined('CONFIG_FILE')) define('CONFIG_FILE',__FILE__);

# Configuración básica de cifrado y constantes
define('SECRET_SALT','%CONFIG-SALT%');

# Datos de conexión predeterminados (el instalador los sobreescribirá)
define('DBTYPE','mysql');
define('DBHOST','%CONFIG-DBHOST%');
define('DBNAME','%CONFIG-DBNAME%');
define('DBUSER','%CONFIG-DBUSER%');
define('DBPASS','%CONFIG-DBPASS%');

# Prefijo de las tablas de la base de datos
define('TABLE_PREFIX','%CONFIG-PREFIX%');

# Configuración de administración de sesiones y entorno
define('ADMIN_EMAIL','%CONFIG-ADMIN-EMAIL%');

# Modo de desarrollo (cambiar a true si necesitas depurar código a fondo)
define('SECRET_SALT_VERSION', '1');
?>