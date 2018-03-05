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

### PHP CORE FUNCTIONS
- To improve opcode efficiency, you MUST prefix core function by a '\\'
  - E.g: `$var = \str_replace('value', '', $var);`

### PHP CORE CLASSES
- Do not imports non-namespaced classes, use an absolute path instead:  
  - E.g: `$date = new \DateTime();`

### CODE STYLE
- Unneeded/inconsistent `else` statements have to be shortened.
  - E.g: 
 ```php 
 <?php
function setAcme($acme)
{
    if ($acme instanceof Acme) {
        $this->acme = $acme;
        return $this;
    } else {
        throw new phpFastCacheInvalidArgumentException('Invalid acme instance');
    }
}
 ```
  - This example can be safely replaced by this one:
```php 
 <?php
function setAcme($acme)
{
    if ($acme instanceof Acme) {
        $this->acme = $acme;
        return $this;
    } 

    throw new phpFastCacheInvalidArgumentException('Invalid acme instance');
}
 ```
[Read this thread on StackExchange for more information](https://softwareengineering.stackexchange.com/questions/122485/elegant-ways-to-handle-ifif-else-else)
This list is non-exhaustive and will may subject to evolve at any time.
