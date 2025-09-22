# phpXtreamCodes
phpXtreamCodes is a forked engine of the original Xtream Codes Player API for use in a home network and on a Linux server, with 100% support for [TiviMate](https://tivimate.com/) on [AndroidTV/GoogleTV](https://www.android.com/intl/en_us/tv/).

🌍 Available languages: [English](README.md) | [Русский](README.ru.md) | [Latviešu](README.lv.md)


* 100% working with [TiviMate](https://tivimate.com/)
* For home use only – viewing billing is not provided
* 100% works on Linux + Apache + PHP 5.6–7.4 + MySQL/MariaDB
            

#### Installation
* Upload to [LAMP](https://en.wikipedia.org/wiki/LAMP_(software_bundle)) server ([CentOs](https://www.centos.org/), [Ubuntu](https://ubuntu.com/), [Raspberry](https://www.raspberrypi.com/) & etc.):
> ``git clone https://github.com/bmg1/phpXtreamCodes /var/www/html``

* Configure **/etc/sudoers**:
> ``apache ALL=(ALL) NOPASSWD: ALL``

* Create a symlink on the server to your disk storage
> ``ln -s "/HDD" /var/www/html/HDD``

* Configure **./config.php**:
   * Database access:
        > ``$pdo = new PDO('mysql:host=localhost;dbname=phpxtream', 'phpxtream', 'phpxtream');
    $pdo->exec("SET NAMES utf8mb4");``
   * API credentials:
        > ``$allUsers = [
                'demo' => ['password'=>'demo'],
            ];``
   * Specify movie directories:
        > ``$rootPaths = [
                '/HDD/1TbWhite/DLNA',
                '/HDD/4Tb/DLNA/Video',
                '/HDD/1Tb/DLNA2/Video',
            ];``   
   * Specify folders - categories:
        > ``$folders = [1=>'Russian', 2=>'noRussian', 3=>'CCCP', 4=>'multi', 5=>'Doc'];``

* Import the table into the created database:
> ``./install.sql``

* Add to **CRONTAB** or run manually (every 10 minutes):
> ``./cron_scan.php`` - scans your directories  

> ``./cron_metadata.php`` - collects information about your movies  

> ``./cron_cleanup.php`` - removes deleted movies from the database   

* Configure in TiviMate (or another Xtream player):

> Server: ``your domain name or host name server``  

> Login: ``your login from $allUsers``  

> Password: ``your password from $allUsers``

* Сделать линк на серевере на ваше хранилише дисков
> ``ln -s "/HDD" /var/www/html/HDD``

## Feedback & Support
If you have any problems, questions, or suggestions — feel free to [open an issue](../../issues) or write to us.
