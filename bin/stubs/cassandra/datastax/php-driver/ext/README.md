# Installing Cassandra PHP Extension

You can build the extension yourself or use one of the provided scripts. Below
you'll find installation instructions for [Linux/OS X](#linuxos-x) and [Windows](#windows).

## Linux/OS X

### Install dependencies

The Cassandra PHP Extension depends on the following libraries:

* [The C/C++ driver and its dependencies](http://datastax.github.io/cpp-driver/topics/#installation).
* [The GNU Multiple Precision Arithmetic Library](https://gmplib.org/).
* [Libuv](http://libuv.org/)

#### homebrew

```bash
brew install libuv cmake gmp git
```

#### apt-get

```bash
sudo apt-get install g++ make cmake libuv-dev libssl-dev libgmp-dev php5 php5-dev openssl libpcre3-dev git
```

#### yum

```bash
sudo yum install automake cmake gcc gcc-c++ git libtool openssl-devel wget gmp gmp-devel boost php-devel pcre-devel git
pushd /tmp
wget http://dist.libuv.org/dist/v1.8.0/libuv-v1.8.0.tar.gz
tar xzf libuv-v1.8.0.tar.gz
pushd libuv-v1.8.0
sh autogen.sh
./configure
sudo make install
popd
popd
```

[Refer to the official documentation](http://datastax.github.io/cpp-driver/topics/building/)
for the DataStax C/C++ Driver for Apache Cassandra in case you're having issues
installing any of its dependencies.

### Install the PHP extension

#### Installing with pecl

The PHP driver is published to the official PECL repository, in order to install it,
you have to first [install the C/C++ driver v2.2.2+](http://datastax.github.io/cpp-driver/topics/building/)
and then run the following command.

```bash
pecl install cassandra
```

You can also use PECL to install the driver from source by specifying the
provided [package.xml](package.xml) file path as the argument to `pecl install` command.

```bash
git clone https://github.com/datastax/php-driver.git
cd php-driver
pecl install ext/package.xml
```

#### Installing with submoduled, statically compiled version of the C/C++ driver

```bash
git clone https://github.com/datastax/php-driver.git
cd php-driver
git submodule update --init
cd ext
./install.sh
```

**Note** [The install.sh script](install.sh#L25-L35) will also compile and
statically link into the extension a submoduled version of the DataStax C/C++
driver for Apache Cassandra. To use a version of cpp driver that you already
have on your system, run `phpize`, `./configure` and `make install`.

Don't forget to enable the PHP extension after running [install.sh](install.sh):

```bash
echo -e "; DataStax PHP Driver\nextension=cassandra.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
```

## Windows

We provide [a self-contained batch script](vc_build.bat) for building the PHP
driver and all of its dependencies. In order to run it, you have to install the
build dependencies and clone the repository with the DataStax PHP Driver for
Apache Cassandra.

### Obtaining Build Dependencies

- Download and install [Bison](http://gnuwin32.sourceforge.net/downlinks/bison.php).
 - Make sure Bison is in your system PATH and not installed in a directory with
   spaces (e.g. `%SYSTEMDRIVE%\GnuWin32`)
- Download and install [CMake](http://www.cmake.org/download).
 - Make sure to select the option "Add CMake to the system PATH for all users"
   or "Add CMake to the system PATH for current user".
- Download and install [Git](http://git-scm.com/download/win)
 - Make sure to select the option "Use Git from Windows Command Prompt" or
   manually add the git executable to the system PATH.
- Download and install [ActiveState Perl](https://www.perl.org/get.html#win32)
 - Make sure to select the option "Add Perl to PATH environment variable".
- Download and install [Python v2.7.x](https://www.python.org/downloads)
 - Make sure to select/install the feature "Add python.exe to Path"

### Building the Driver

The [batch script](vc_build.bat) detects installed versions of Visual Studio to
simplify the build process on Windows and select the correct version of Visual
Studio for the PHP version the driver is being built for.

First you will need to open a "Command Prompt" to execute the batch script.
Running the batch script without any arguments will build the driver for PHP
v7.0 with Zend thread safety (ZTS) and for the current system architecture
(e.g. x64).

To perform advanced build configuration, execute the batch script with the
`--HELP` argument to display the options available.

```dos
Usage: VC_BUILD.BAT [OPTION...]

    --DEBUG                           Enable debug build
    --RELEASE                         Enable release build (default)
    --DISABLE-CLEAN                   Disable clean build
    --DISABLE-THREAD-SAFETY           Disable thread safety
    --ENABLE-PACKAGES [version]       Enable package generation (5.5, 5.6, 7.0) (*)
    --ENABLE-TEST-CONFIGURATION       Enable test configuration build
    --PHP-VERSION [version]           PHP version 5.5, 5.6, 7.0 (**)
    --X86                             Target 32-bit build (***)
    --X64                             Target 64-bit build (***)

    C/C++ Driver Options
      --USE-BOOST-ATOMIC              Use Boost atomic

    --HELP                            Display this message

*   Minimum supported officially released PHP binary installations
**  Defaults to PHP v7.0 if --PHP-VERSION is not used
*** Default target architecture is determined based on system architecture
```

#### Manual/PHP Step-by-Step Windows Build

The PHP driver extension can also be built using the
[Build your own PHP on Windows](https://wiki.php.net/internals/windows/stepbystepbuild)
instructions followed by the
[Building PECL extensions](https://wiki.php.net/internals/windows/stepbystepbuild#building_pecl_extensions)
instruction where the driver can be statically linked into the PHP executable
or as an import (DLL) library. This process requires that the binary
dependencies of the PHP driver extension be included in the
`phpdev\vc##\x##\deps` directory along with the standard PHP library
dependencies. Use `--enable-cassandra` to built library into the PHP executable
and `--enable-cassandra=shared` for import (DLL) library.

The PHP driver extension dependencies that are not included with the
standard PHP library can be download [here](http://downloads.datastax.com/cpp-driver/windows/).

**Note** The binary libraries downloaded/used must be compatible with the
MSVC++ compiler and the PHP driver extension.

#### Enable Testing

Ensure the driver is built with --ENABLE-TEST-CONFIGURATION in order to execute
the [Behat](http://www.behat.org) test suite and PHPUnit unit tests. You will
also need to install CCM; detailed instructions can be found in this blog
[post](http://www.datastax.com/dev/blog/ccm-2-0-and-windows).
