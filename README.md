# Proyecto para Prueba Técnica Drupal iZertis

### Obtener dependencias del proyecto.
- Descargar dependencias de composer ejecutando comando **composer install** en la raiz del proyecto

### Instalar y configurar servidor web.
- Es necesario instalar el servidor web de su preferencia que soporte php 7.4
- Configurar virtual host y apuntar a la carpeta web del proyecto.

### Montar la base de datos.
- Ir al archivo **/web/sites/default/default.settings.php** y renombrarlo por **settings.php**
- Dentro del archivo **settings.php** colocar la siguiente configuración de la base de datos:
  ```
    $databases['default']['default'] = [
      'database' => 'prueba_izertis',
      'username' => 'root',
      'password' => '',
      'prefix' => '',
      'host' => 'localhost',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
    ];
    $settings['config_sync_directory'] = '../config/sync';
    ```
- Abrir phpmyadmin o el cliente mysql de su preferencia y crear una base de datos **prueba_izertis** y montar la base de datos que se encuentra en la raiz del proyecto **prueba_izertis.sql**

### Funcionalidades
- Ir a la ruta **/admin/people** y visualizar la lista de usuarios.
- En esa vista podra apreciar el **Resúmen de rango por edad** y la **Exportacion de los usuarios**.
