CODING GUIDELINE
-------------------

Fork the project, create a feature branch, and send us a pull request.
Preferably on a distinct branch.

To ensure a consistent code base, you should make sure the code follows
the [PSR-2 Coding Standards](http://www.php-fig.org/psr/psr-2/). You can also
run [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) with the
configuration file that can be found in the project root directory.

If you would like to help, take a look at the [list of open issues](https://github.com/PHPSocialNetwork/phpfastcache/issues).

PHPFASTCACHE's specialties 
-------------------
As of the V7 your contributions MUST comply the following standards:

- **PHP CORE FUNCTIONS**
  - To improve opcode efficiency, you MUST prefix core function by a '\\'
    - E.g: `$var = \str_replace('value', '', $var);`
- **PHP CORE CLASSES**
  - Do not imports non-namespaced classes, use an absolute path instead:  
    - E.g: `$date = new \DateTime();`

This list is non-exhaustive and will may subject to evolve at any time.