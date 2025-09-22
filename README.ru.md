# phpXtreamCodes
phpXtreamCodes — это форк движка оригинального Xtream Codes Player API для использования в домашней сети и на Linux-сервере, со 100% поддержкой [TiviMate](https://tivimate.com/) на [AndroidTV/GoogleTV](https://www.android.com/intl/ru_ru/tv/).

🌍 Available languages: [English](README.md) | [Русский](README.ru.md) | [Latviešu](README.lv.md)


* 100% работает с [TiviMate](https://tivimate.com/)  
* Только для домашнего использования – биллинг просмотров не предусмотрен  
* 100% работает на Linux + Apache + PHP 5.6–7.4 + MySQL/MariaDB  

#### Установка
* Закачать на сервер [LAMP](https://ru.wikipedia.org/wiki/LAMP) ([CentOs](https://www.centos.org/), [Ubuntu](https://ubuntu.com/), [Raspberry](https://www.raspberrypi.com/) и др.):
> ``git clone https://github.com/bmg1/phpXtreamCodes /var/www/html``

* Настроить **/etc/sudoers**:
> ``apache ALL=(ALL) NOPASSWD: ALL``

* Сделать линк на серевере на ваше хранилише дисков
> ``ln -s "/HDD" /var/www/html/HDD``

* Настроить **./config.php**:
   * Доступ к базе данных:
        > ``$pdo = new PDO('mysql:host=localhost;dbname=phpxtream', 'phpxtream', 'phpxtream');
    $pdo->exec("SET NAMES utf8mb4");``
   * Доступы к API:
        > ``$allUsers = [
                'demo' => ['password'=>'demo'],
            ];``
   * Указать директории с фильмами:
        > ``$rootPaths = [
                '/HDD/1TbWhite/DLNA',
                '/HDD/4Tb/DLNA/Video',
                '/HDD/1Tb/DLNA2/Video',
            ];``   
   * Указать папки – категории:
        > ``$folders = [1=>'Russian', 2=>'noRussian', 3=>'CCCP', 4=>'multi', 5=>'Doc'];``

* Импортировать таблицу в созданную базу:
> ``./install.sql``

* Добавить в **CRONTAB** или запускать вручную (каждые 10 минут):
> ``./cron_scan.php`` – сканирует каталоги  

> ``./cron_metadata.php`` – собирает информацию о фильмах  

> ``./cron_cleanup.php`` – удаляет записи о удалённых фильмах из базы  

* Настроить в TiviMate (или другом Xtream-плеере):

> Server: ``ваш домен или имя хоста``  

> Login: ``ваш логин из $allUsers``  

> Password: ``ваш пароль из $allUsers``  


## Обратная связь
Если у вас есть проблемы, вопросы или пожелания — пожалуйста, [создайте Issue](../../issues) или напишите нам.
