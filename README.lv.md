# phpXtreamCodes
phpXtreamCodes ir oriģinālā Xtream Codes Player API dzinēja “fork” versija, paredzēta lietošanai mājas tīklā un Linux serverī, ar 100% atbalstu [TiviMate](https://tivimate.com/) uz [AndroidTV/GoogleTV](https://www.android.com/intl/lv_lv/tv/).

🌍 Available languages: [English](README.md) | [Русский](README.ru.md) | [Latviešu](README.lv.md)

* 100% darbojas ar [TiviMate](https://tivimate.com/)  
* Tikai mājas lietošanai – skatījumu norēķini nav paredzēti  
* 100% darbojas uz Linux + Apache + PHP 5.6–7.4 + MySQL/MariaDB  

#### Uzstādīšana
* Augšupielādēt uz [LAMP](https://en.wikipedia.org/wiki/LAMP_(software_bundle)) servera ([CentOs](https://www.centos.org/), [Ubuntu](https://ubuntu.com/), [Raspberry](https://www.raspberrypi.com/) u.c.):
> ``git clone https://github.com/bmg1/phpXtreamCodes /var/www/html``

* Konfigurēt **/etc/sudoers**:
> ``apache ALL=(ALL) NOPASSWD: ALL``

* Izveidot simbolisko saiti serverī uz jūsu disku glabātuvi
> ``ln -s "/HDD" /var/www/html/HDD``
  
* Konfigurēt **./config.php**:
   * Datubāzes piekļuve:
        > ``$pdo = new PDO('mysql:host=localhost;dbname=phpxtream', 'phpxtream', 'phpxtream');
    $pdo->exec("SET NAMES utf8mb4");``
   * API piekļuves dati:
        > ``$allUsers = [
                'demo' => ['password'=>'demo'],
            ];``
   * Norādīt filmu direktorijas:
        > ``$rootPaths = [
                '/HDD/1TbWhite/DLNA',
                '/HDD/4Tb/DLNA/Video',
                '/HDD/1Tb/DLNA2/Video',
            ];``   
   * Norādīt mapes – kategorijas:
        > ``$folders = [1=>'Russian', 2=>'noRussian', 3=>'CCCP', 4=>'multi', 5=>'Doc'];``

* Importēt tabulu izveidotajā datubāzē:
> ``./install.sql``

* Pievienot **CRONTAB** vai palaist manuāli (ik pēc 10 minūtēm):
> ``./cron_scan.php`` – skenē jūsu katalogus  

> ``./cron_metadata.php`` – savāc informāciju par filmām  

> ``./cron_cleanup.php`` – dzēš no datubāzes izdzēstās filmas  

* Konfigurēt TiviMate (vai citā Xtream atskaņotājā):

> Server: ``jūsu domēna nosaukums vai servera hostname``  

> Login: ``jūsu lietotājvārds no $allUsers``  

> Password: ``jūsu parole no $allUsers``  


## Atsauksmes un atbalsts
Ja jums ir kādas problēmas, jautājumi vai ierosinājumi — lūdzu, [izveidojiet Issue](../../issues) vai rakstiet mums.
